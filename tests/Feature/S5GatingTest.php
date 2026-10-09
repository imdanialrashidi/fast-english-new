<?php

use App\Models\PaymentRequest;
use App\Models\Plan;
use Database\Seeders\S5PaymentFixtureSeeder;
use Illuminate\Http\UploadedFile;
use Tests\Support\ContentFixtures;

// S5 gating: every protected route denies guests and enforces ownership.
// Two users throughout; complements the S4 gating suite.

beforeEach(function () {
    $this->seed(S5PaymentFixtureSeeder::class);
    $this->studentA = ContentFixtures::student();
    $this->studentB = ContentFixtures::student();
    $this->plan = Plan::where('slug', 's5-test-30')->firstOrFail();
});

test('guests are refused on every S5 route', function () {
    $this->get(route('subscribe.index'))->assertRedirect(route('login'));
    $this->post(route('payments.store'), ['plan_id' => $this->plan->id])->assertRedirect(route('login'));

    $this->actingAs($this->studentA)->post(route('payments.store'), ['plan_id' => $this->plan->id]);
    $row = PaymentRequest::where('user_id', $this->studentA->id)->firstOrFail();
    // actingAs() persists in the same test: log out before the guest legs.
    auth()->logout();

    $this->get(route('payments.show', $row))->assertRedirect(route('login'));
    $this->post(route('payments.cancel', $row))->assertRedirect(route('login'));
    $this->post(route('payments.receipt.store', $row), [
        'receipt' => UploadedFile::fake()->createWithContent('r.jpg', 'x'),
    ])->assertRedirect(route('login'));
    $this->get(route('payments.receipt.show', $row))->assertRedirect(route('login'));
});

test('a second student cannot touch the first student request', function () {
    $this->actingAs($this->studentA)->post(route('payments.store'), ['plan_id' => $this->plan->id]);
    $row = PaymentRequest::where('user_id', $this->studentA->id)->firstOrFail();

    $this->actingAs($this->studentB)->get(route('payments.show', $row))->assertForbidden();
    $this->actingAs($this->studentB)->post(route('payments.cancel', $row))->assertForbidden();
    $this->actingAs($this->studentB)->post(route('payments.receipt.store', $row), [
        'receipt' => UploadedFile::fake()->createWithContent(
            'receipt.jpg',
            (string) file_get_contents(database_path('seeders/fixtures/s3-cover-fixture.jpg'))
        ),
    ])->assertForbidden();
});

test('disabled accounts are refused on every S5 route', function () {
    $this->actingAs($this->studentA)->post(route('payments.store'), ['plan_id' => $this->plan->id]);
    $row = PaymentRequest::where('user_id', $this->studentA->id)->firstOrFail();

    $this->studentA->forceFill(['disabled_at' => now()])->save();

    $this->get(route('subscribe.index'))->assertRedirect(route('login'));
    $this->get(route('payments.show', $row))->assertRedirect(route('login'));
    $this->assertGuest();
});
