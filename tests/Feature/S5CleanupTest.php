<?php

use App\Models\PaymentRequest;
use App\Models\Plan;
use Database\Seeders\S5PaymentFixtureSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ContentFixtures;

// S5 orphan cleanup (scope §10.3): files written before a failed DB write
// must not linger. The `receipts:cleanup` command deletes unreferenced
// files and keeps referenced receipts.

beforeEach(function () {
    $this->seed(S5PaymentFixtureSeeder::class);
    $this->student = ContentFixtures::student();
    $this->plan = Plan::where('slug', 's5-test-30')->firstOrFail();
});

test('receipts cleanup removes orphans and keeps referenced files', function () {
    $this->actingAs($this->student)->post(route('payments.store'), ['plan_id' => $this->plan->id]);
    $row = PaymentRequest::where('user_id', $this->student->id)->firstOrFail();
    $this->actingAs($this->student)->post(route('payments.receipt.store', $row), [
        'receipt' => UploadedFile::fake()->createWithContent(
            'receipt.jpg',
            (string) file_get_contents(database_path('seeders/fixtures/s3-cover-fixture.jpg'))
        ),
    ]);
    $referenced = $row->fresh()->receipt_path;
    expect(Storage::disk('local')->exists($referenced))->toBeTrue();

    // An orphaned file (simulating a crashed DB write).
    Storage::disk('local')->put('receipts/s5-orphan-test.jpg', 'orphan bytes');
    expect(Storage::disk('local')->exists('receipts/s5-orphan-test.jpg'))->toBeTrue();

    $this->artisan('receipts:cleanup')->assertSuccessful();

    expect(Storage::disk('local')->exists('receipts/s5-orphan-test.jpg'))->toBeFalse()
        ->and(Storage::disk('local')->exists($referenced))->toBeTrue();
});
