<?php

use App\Models\PaymentRequest;
use App\Models\Plan;
use Database\Seeders\S5PaymentFixtureSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ContentFixtures;

// S5-2 (receipt validation): only a real JPEG/PNG/WebP ≤5 MB and
// ≤6000×6000 px is accepted. SVG, HTML, PDF, executables, oversized, and
// over-dimension files are 422 with no file stored and no row change. An
// accepted receipt lands on the private disk under a random name with its
// metadata stripped; the public disk never holds it.

beforeEach(function () {
    $this->seed(S5PaymentFixtureSeeder::class);
    $this->studentA = ContentFixtures::student();
    $this->studentB = ContentFixtures::student();
    $this->plan = Plan::where('slug', 's5-test-30')->firstOrFail();
});

function s5AwaitingRequest($test, $user, $plan): PaymentRequest
{
    $test->actingAs($user)->post(route('payments.store'), ['plan_id' => $plan->id]);

    return PaymentRequest::where('user_id', $user->id)->firstOrFail();
}

function s5ValidReceipt(): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        'receipt.jpg',
        (string) file_get_contents(database_path('seeders/fixtures/s3-cover-fixture.jpg'))
    );
}

test('an accepted receipt is stored private with metadata stripped', function () {
    $row = s5AwaitingRequest($this, $this->studentA, $this->plan);

    $this->actingAs($this->studentA)
        ->post(route('payments.receipt.store', $row), ['receipt' => s5ValidReceipt()])
        ->assertRedirect();

    $fresh = $row->fresh();
    expect($fresh->status)->toBe(PaymentRequest::STATUS_PENDING)
        ->and($fresh->receipt_path)->not->toBeNull();

    // Private disk, random name under receipts/, never the public disk.
    expect(Storage::disk('local')->exists($fresh->receipt_path))->toBeTrue()
        ->and(Storage::disk('public')->exists($fresh->receipt_path))->toBeFalse()
        ->and($fresh->receipt_path)->toStartWith('receipts/')
        ->and(basename($fresh->receipt_path))->not->toContain('receipt');

    // Reading back the stored file: decodable, still an image, no EXIF.
    $absolute = Storage::disk('local')->path($fresh->receipt_path);
    expect(@getimagesize($absolute))->not->toBeFalse();
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    expect($finfo->file($absolute))->toBe('image/jpeg');
    $exif = @exif_read_data($absolute);
    expect($exif['Orientation'] ?? null)->toBeNull()
        ->and($exif['EXIF'] ?? null)->toBeNull();
});

test('non-image, SVG, HTML, PDF, and executable receipts are refused', function () {
    $cases = [
        'plain text' => UploadedFile::fake()->createWithContent('receipt.jpg', 'not an image at all'),
        'svg' => UploadedFile::fake()->createWithContent(
            'receipt.svg',
            (string) file_get_contents(database_path('seeders/fixtures/s5-rejected-fixture.svg'))
        ),
        'html' => UploadedFile::fake()->createWithContent('receipt.jpg', '<html><body>forged receipt</body></html>'),
        'pdf' => UploadedFile::fake()->createWithContent('receipt.pdf', "%PDF-1.4 forged receipt\n%%EOF"),
        'executable' => UploadedFile::fake()->createWithContent('receipt.exe', "MZ\x90\x00forged executable"),
    ];

    foreach ($cases as $label => $file) {
        $user = ContentFixtures::student();
        $row = s5AwaitingRequest($this, $user, $this->plan);
        $beforeFiles = Storage::disk('local')->allFiles('receipts');

        $this->actingAs($user)
            ->post(route('payments.receipt.store', $row), ['receipt' => $file])
            ->assertStatus(302); // redirect back with validation errors
        $this->actingAs($user)
            ->post(route('payments.receipt.store', $row), ['receipt' => $file]);

        // Retried via JSON for an exact 422 assertion on a fresh draft.
        $user2 = ContentFixtures::student();
        $row2 = s5AwaitingRequest($this, $user2, $this->plan);
        $this->actingAs($user2)
            ->postJson(route('payments.receipt.store', $row2), ['receipt' => $file])
            ->assertStatus(422);

        expect($row->fresh()->status)->toBe(PaymentRequest::STATUS_AWAITING_RECEIPT)
            ->and($row->fresh()->receipt_path)->toBeNull()
            ->and(Storage::disk('local')->allFiles('receipts'))->toBe($beforeFiles);
    }
});

test('an oversized receipt is refused with nothing stored', function () {
    $row = s5AwaitingRequest($this, $this->studentA, $this->plan);
    $beforeFiles = Storage::disk('local')->allFiles('receipts');

    $big = UploadedFile::fake()->createWithContent(
        'receipt.jpg',
        file_get_contents(database_path('seeders/fixtures/s3-cover-fixture.jpg')).str_repeat("\0", 6 * 1024 * 1024)
    );

    $this->actingAs($this->studentA)
        ->postJson(route('payments.receipt.store', $row), ['receipt' => $big])
        ->assertStatus(422);

    expect($row->fresh()->status)->toBe(PaymentRequest::STATUS_AWAITING_RECEIPT)
        ->and($row->fresh()->receipt_path)->toBeNull()
        ->and(Storage::disk('local')->allFiles('receipts'))->toBe($beforeFiles);
});

test('dimensions above 6000px are refused', function () {
    $row = s5AwaitingRequest($this, $this->studentA, $this->plan);
    $beforeFiles = Storage::disk('local')->allFiles('receipts');

    // 6100×100 solid JPEG: over the 6000 cap on one side, small on disk.
    $wide = imagecreatetruecolor(6100, 100);
    $tmp = tempnam(sys_get_temp_dir(), 's5wide').'.jpg';
    imagejpeg($wide, $tmp, 80);
    imagedestroy($wide);
    $file = new UploadedFile($tmp, 'receipt.jpg', 'image/jpeg', null, true);

    $this->actingAs($this->studentA)
        ->postJson(route('payments.receipt.store', $row), ['receipt' => $file])
        ->assertStatus(422);

    @unlink($tmp);

    expect($row->fresh()->status)->toBe(PaymentRequest::STATUS_AWAITING_RECEIPT)
        ->and(Storage::disk('local')->allFiles('receipts'))->toBe($beforeFiles);
});

test('a guest cannot submit a receipt', function () {
    $row = s5AwaitingRequest($this, $this->studentA, $this->plan);
    auth()->logout();

    $this->postJson(route('payments.receipt.store', $row), ['receipt' => s5ValidReceipt()])
        ->assertUnauthorized();
    expect($row->fresh()->status)->toBe(PaymentRequest::STATUS_AWAITING_RECEIPT);
});
