<?php

use App\Models\PaymentRequest;
use App\Models\Plan;
use Database\Seeders\S5PaymentFixtureSeeder;
use Illuminate\Http\UploadedFile;
use Tests\Support\ContentFixtures;

// Pre-slice item 4 (S5): the receipt upload goes through the S4 throttle —
// the sixth request in a minute returns 429. Same bucket, same limit as
// the Livewire upload throttle (scope §16).

beforeEach(function () {
    $this->seed(S5PaymentFixtureSeeder::class);
    $this->student = ContentFixtures::student();
    $this->other = ContentFixtures::student();
    $this->plan = Plan::where('slug', 's5-test-30')->firstOrFail();
});

test('the sixth receipt upload in a minute returns 429', function () {
    $this->actingAs($this->student)->post(route('payments.store'), ['plan_id' => $this->plan->id]);
    $row = PaymentRequest::where('user_id', $this->student->id)->firstOrFail();
    $url = route('payments.receipt.store', $row);

    $file = fn () => UploadedFile::fake()->createWithContent(
        'receipt.jpg',
        (string) file_get_contents(database_path('seeders/fixtures/s3-cover-fixture.jpg'))
    );

    // First submit moves awaiting_receipt → pending; the next four are
    // idempotent retries returning the same pending request.
    for ($i = 0; $i < 5; $i++) {
        $response = $this->actingAs($this->student)->post($url, ['receipt' => $file()]);
        expect($response->status())->toBeIn([302, 200]);
    }

    $this->actingAs($this->student)->post($url, ['receipt' => $file()])->assertStatus(429);
});

test('the receipt throttle is per user, not global', function () {
    $this->actingAs($this->student)->post(route('payments.store'), ['plan_id' => $this->plan->id]);
    $row = PaymentRequest::where('user_id', $this->student->id)->firstOrFail();
    $url = route('payments.receipt.store', $row);
    $file = fn () => UploadedFile::fake()->createWithContent(
        'receipt.jpg',
        (string) file_get_contents(database_path('seeders/fixtures/s3-cover-fixture.jpg'))
    );

    for ($i = 0; $i < 5; $i++) {
        $this->actingAs($this->student)->post($url, ['receipt' => $file()]);
    }
    $this->actingAs($this->student)->post($url, ['receipt' => $file()])->assertStatus(429);

    // A different user still has a fresh bucket.
    $this->actingAs($this->other)->post(route('payments.store'), ['plan_id' => $this->plan->id]);
    $otherRow = PaymentRequest::where('user_id', $this->other->id)->firstOrFail();
    $response = $this->actingAs($this->other)
        ->post(route('payments.receipt.store', $otherRow), ['receipt' => $file()]);
    expect($response->status())->not->toBe(429);
});
