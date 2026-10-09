<?php

use App\Models\PaymentRequest;
use App\Models\Plan;
use Database\Seeders\S5PaymentFixtureSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\Support\ContentFixtures;

// S5-4 (idempotency + single open): double submit → one pending; double
// create → one open; timeout retry → same request; the partial unique
// index leaves exactly one open row under a race.

beforeEach(function () {
    $this->seed(S5PaymentFixtureSeeder::class);
    $this->studentA = ContentFixtures::student();
    $this->studentB = ContentFixtures::student();
    $this->plan30 = Plan::where('slug', 's5-test-30')->firstOrFail();
    $this->plan90 = Plan::where('slug', 's5-test-90')->firstOrFail();
});

function s5ReceiptFile(): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        'receipt.jpg',
        (string) file_get_contents(database_path('seeders/fixtures/s3-cover-fixture.jpg'))
    );
}

test('a double create creates one open request', function () {
    $this->actingAs($this->studentA)->post(route('payments.store'), ['plan_id' => $this->plan30->id]);
    $this->actingAs($this->studentA)->post(route('payments.store'), ['plan_id' => $this->plan90->id]);

    $rows = PaymentRequest::where('user_id', $this->studentA->id)->get();
    expect($rows)->toHaveCount(1)
        ->and($rows->first()->status)->toBe(PaymentRequest::STATUS_AWAITING_RECEIPT)
        // The first plan wins; changing the plan needs cancel + new.
        ->and((int) $rows->first()->plan_id)->toBe((int) $this->plan30->id);
});

test('a double receipt submit creates one pending request', function () {
    $this->actingAs($this->studentA)->post(route('payments.store'), ['plan_id' => $this->plan30->id]);
    $row = PaymentRequest::where('user_id', $this->studentA->id)->firstOrFail();

    $this->actingAs($this->studentA)
        ->post(route('payments.receipt.store', $row), ['receipt' => s5ReceiptFile()])
        ->assertRedirect();
    // Retry after a timeout: same pending request, no new row.
    $this->actingAs($this->studentA)
        ->post(route('payments.receipt.store', $row->fresh()), ['receipt' => s5ReceiptFile()])
        ->assertRedirect();

    $rows = PaymentRequest::where('user_id', $this->studentA->id)->get();
    expect($rows)->toHaveCount(1)
        ->and($rows->first()->status)->toBe(PaymentRequest::STATUS_PENDING);
});

test('a timeout retry via JSON returns the same pending request', function () {
    $this->actingAs($this->studentA)
        ->postJson(route('payments.store'), ['plan_id' => $this->plan30->id])
        ->assertOk();
    $row = PaymentRequest::where('user_id', $this->studentA->id)->firstOrFail();

    $first = $this->actingAs($this->studentA)
        ->postJson(route('payments.receipt.store', $row), ['receipt' => s5ReceiptFile()])
        ->assertOk();
    $second = $this->actingAs($this->studentA)
        ->postJson(route('payments.receipt.store', $row->fresh()), ['receipt' => s5ReceiptFile()])
        ->assertOk();

    expect($first->json('id'))->toBe($row->id)
        ->and($second->json('id'))->toBe($row->id)
        ->and(PaymentRequest::where('user_id', $this->studentA->id)->count())->toBe(1);
});

test('the partial unique index leaves exactly one open request under a race', function () {
    $this->actingAs($this->studentA)->post(route('payments.store'), ['plan_id' => $this->plan30->id]);

    // A direct second open row for the same user must hit the partial
    // unique index. forceFill bypasses the mass-assignment guard so the
    // DB constraint itself is exercised (server code never mass-assigns
    // these fields).
    $existing = PaymentRequest::where('user_id', $this->studentA->id)->firstOrFail();
    $failed = false;
    try {
        // Nested transaction = savepoint: the unique violation aborts only
        // the savepoint, not the outer RefreshDatabase test transaction.
        DB::transaction(function () use ($existing) {
            $race = new PaymentRequest;
            $race->forceFill([
                'user_id' => $existing->user_id,
                'plan_id' => $this->plan90->id,
                'destination_id' => $existing->destination_id,
                'plan_name_snapshot' => 'race',
                'amount_toman_snapshot' => 100000,
                'duration_days_snapshot' => 30,
                'destination_snapshot' => ['card_number' => 'x', 'holder_name' => 'y', 'bank_name' => 'z'],
                'status' => PaymentRequest::STATUS_AWAITING_RECEIPT,
            ])->save();
        });
    } catch (QueryException $e) {
        $failed = true;
        expect((string) $e->getMessage())->toContain('payment_requests_single_open');
    }

    if (! $failed) {
        $this->fail('Expected the second open row to hit payment_requests_single_open.');
    }

    expect(PaymentRequest::where('user_id', $this->studentA->id)
        ->whereIn('status', PaymentRequest::OPEN_STATUSES)->count())->toBe(1);

    // A second student is unaffected (per-user index).
    $this->actingAs($this->studentB)->post(route('payments.store'), ['plan_id' => $this->plan90->id]);
    expect(PaymentRequest::where('user_id', $this->studentB->id)->count())->toBe(1);
});
