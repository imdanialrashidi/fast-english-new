<?php

use App\Models\PaymentDestination;
use App\Models\PaymentRequest;
use App\Models\Plan;
use Database\Seeders\S5PaymentFixtureSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\ContentFixtures;

// Pre-slice item 3 + S5 scope guard: every money column is integer toman
// (scope §8) with a positive check for paid plans. Floats and rial
// conversion are forbidden. Subscriptions stay S6 — the tables must not
// exist after this slice.

beforeEach(function () {
    $this->seed(S5PaymentFixtureSeeder::class);
});

test('money columns are integer toman with positive checks', function () {
    // Column types are integer (not float/decimal) on PostgreSQL.
    // Doctrine reports PostgreSQL integer as int4 — both mean integer.
    expect(Schema::getColumnType('plans', 'price_toman'))->toContain('int')
        ->and(Schema::getColumnType('payment_requests', 'amount_toman_snapshot'))->toContain('int');

    // Zero and negative amounts are refused by the CHECK constraints.
    // Each bad insert runs in a savepoint so the outer RefreshDatabase
    // transaction survives the expected violation.
    foreach ([0, -1000] as $bad) {
        $failed = false;
        try {
            DB::transaction(fn () => DB::table('plans')->insert([
                'slug' => 's5-bad-'.$bad.'-'.uniqid(),
                'name_fa' => 'بد',
                'price_toman' => $bad,
                'duration_days' => 30,
                'is_active' => true,
                'display_order' => 99,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        } catch (QueryException $e) {
            $failed = true;
        }
        expect($failed)->toBeTrue("price_toman={$bad} must be refused");
    }

    $user = ContentFixtures::student();
    $plan = Plan::where('slug', 's5-test-30')->firstOrFail();
    $destination = PaymentDestination::where('is_active', true)->firstOrFail();
    $failed = false;
    try {
        DB::transaction(function () use ($user, $plan, $destination) {
            $row = new PaymentRequest;
            $row->forceFill([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'destination_id' => $destination->id,
                'plan_name_snapshot' => $plan->name_fa,
                'amount_toman_snapshot' => 0,
                'duration_days_snapshot' => 30,
                'destination_snapshot' => ['card_number' => 'x', 'holder_name' => 'y', 'bank_name' => 'z'],
                'status' => PaymentRequest::STATUS_AWAITING_RECEIPT,
            ])->save();
        });
    } catch (QueryException $e) {
        $failed = true;
    }
    expect($failed)->toBeTrue('zero snapshot amount must be refused');

    // Fixture amounts are positive integers (labelled TEST values).
    foreach (Plan::all() as $fixturePlan) {
        expect((int) $fixturePlan->price_toman)->toBeGreaterThan(0)
            ->and((int) $fixturePlan->duration_days)->toBeGreaterThan(0);
    }
});

// S6 adaptation (recorded): S5 proved these tables absent; S6 creates
// them (migration 000011). Presence is now asserted here so the old guard
// cannot silently rot, with shape covered by S6SubscriptionTablesTest.
test('subscriptions tables exist after S6', function () {
    expect(Schema::hasTable('subscriptions'))->toBeTrue()
        ->and(Schema::hasTable('subscription_events'))->toBeTrue();
});
