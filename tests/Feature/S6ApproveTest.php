<?php

use App\Actions\ApprovePayment;
use App\Models\PaymentRequest;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use Carbon\Carbon;
use Database\Seeders\S5PaymentFixtureSeeder;
use Illuminate\Validation\ValidationException;
use Tests\Support\S6Payments;

// S6 approve (scope §11.2, AC-12): one shared action, one event, one
// extension, fixed window math, idempotent replay, and every refusal.
// Each behavior uses two users plus a negative path. The clock is passed
// explicitly so the math is deterministic (UTC).

beforeEach(function () {
    $this->seed(S5PaymentFixtureSeeder::class);
    $this->studentA = S6Payments::student();
    $this->studentB = S6Payments::student();
    $this->staff = S6Payments::staff();
    $this->plan = Plan::where('slug', 's5-test-30')->firstOrFail();
    $this->now = Carbon::parse('2026-10-01 12:00:00', 'UTC');
});

test('approve creates one event and a fresh window from the approval time', function () {
    $pending = S6Payments::pendingRequest($this, $this->studentA, $this->plan);

    $outcome = ApprovePayment::approve($pending->id, $this->staff, $this->now);

    expect($outcome['replayed'])->toBeFalse();

    $request = $outcome['request'];
    expect($request->status)->toBe(PaymentRequest::STATUS_APPROVED)
        ->and((int) $request->reviewed_by)->toBe((int) $this->staff->id)
        ->and($request->reviewed_at)->not->toBeNull();

    $subscription = Subscription::where('user_id', $this->studentA->id)->firstOrFail();
    expect($subscription->starts_at->equalTo($this->now))->toBeTrue()
        ->and($subscription->expires_at->equalTo($this->now->copy()->addDays(30)))->toBeTrue()
        ->and($subscription->revoked_at)->toBeNull();

    // Exactly one event, keyed by the request, with before/after windows.
    $events = SubscriptionEvent::where('source_payment_request_id', $pending->id)->get();
    expect($events)->toHaveCount(1);
    $event = $events->first();
    expect($event->type)->toBe(SubscriptionEvent::TYPE_APPROVED)
        ->and($event->before_starts_at)->toBeNull()
        ->and($event->after_expires_at->equalTo($subscription->expires_at))->toBeTrue()
        ->and((int) $event->duration_days)->toBe(30)
        ->and((int) $event->actor_id)->toBe((int) $this->staff->id);

    // The second student is untouched (isolation).
    expect(Subscription::where('user_id', $this->studentB->id)->exists())->toBeFalse();
});

test('active renewal extends from the current expiry and keeps starts_at', function () {
    $first = S6Payments::pendingRequest($this, $this->studentA, $this->plan);
    ApprovePayment::approve($first->id, $this->staff, $this->now);
    $starts = Subscription::where('user_id', $this->studentA->id)->firstOrFail()->starts_at;

    // A second request is possible only after the first leaves the open
    // states; the approval itself closes it.
    $second = S6Payments::pendingRequest($this, $this->studentA, $this->plan);
    $renewAt = $this->now->copy()->addDays(10);
    $outcome = ApprovePayment::approve($second->id, $this->staff, $renewAt);

    expect($outcome['replayed'])->toBeFalse();
    $subscription = Subscription::where('user_id', $this->studentA->id)->firstOrFail();
    expect($subscription->starts_at->equalTo($starts))->toBeTrue('active renewal preserves starts_at')
        ->and($subscription->expires_at->equalTo($this->now->copy()->addDays(60)))->toBeTrue('30 days extend the live expiry');

    expect(SubscriptionEvent::where('source_payment_request_id', $second->id)->count())->toBe(1);
    expect(SubscriptionEvent::where('user_id', $this->studentA->id)->count())->toBe(2);
});

test('post-expiry purchase starts a new window from the approval time', function () {
    $first = S6Payments::pendingRequest($this, $this->studentA, $this->plan);
    ApprovePayment::approve($first->id, $this->staff, $this->now);

    $late = $this->now->copy()->addDays(45);
    $second = S6Payments::pendingRequest($this, $this->studentA, $this->plan);
    ApprovePayment::approve($second->id, $this->staff, $late);

    $subscription = Subscription::where('user_id', $this->studentA->id)->firstOrFail();
    expect($subscription->starts_at->equalTo($late))->toBeTrue()
        ->and($subscription->expires_at->equalTo($late->copy()->addDays(30)))->toBeTrue();
});

test('replayed approve returns the same result with no new event and no extension', function () {
    $pending = S6Payments::pendingRequest($this, $this->studentA, $this->plan);
    ApprovePayment::approve($pending->id, $this->staff, $this->now);
    $expiry = Subscription::where('user_id', $this->studentA->id)->firstOrFail()->expires_at;

    $replay = ApprovePayment::approve($pending->id, $this->staff, $this->now->copy()->addDays(5));

    expect($replay['replayed'])->toBeTrue()
        ->and($replay['request']->status)->toBe(PaymentRequest::STATUS_APPROVED);
    expect(SubscriptionEvent::where('source_payment_request_id', $pending->id)->count())->toBe(1);
    expect(Subscription::where('user_id', $this->studentA->id)->firstOrFail()->expires_at->equalTo($expiry))->toBeTrue();
});

test('staff cannot approve their own request', function () {
    $staffRequest = S6Payments::pendingRequest($this, $this->staff, $this->plan);

    try {
        ApprovePayment::approve($staffRequest->id, $this->staff, $this->now);
        $approved = true;
    } catch (ValidationException $e) {
        $approved = false;
        expect(collect($e->errors())->flatten()->implode(' '))->toContain('own');
    }
    expect($approved)->toBeFalse('self-approval must be refused');
    expect($staffRequest->fresh()->status)->toBe(PaymentRequest::STATUS_PENDING);
    expect(Subscription::where('user_id', $this->staff->id)->exists())->toBeFalse();
});

test('non-staff cannot approve and guests cannot reach the action', function () {
    $pending = S6Payments::pendingRequest($this, $this->studentA, $this->plan);

    // Another student has no review power (negative path).
    try {
        ApprovePayment::approve($pending->id, $this->studentB, $this->now);
        $approved = true;
    } catch (ValidationException) {
        $approved = false;
    }
    expect($approved)->toBeFalse();
    expect($pending->fresh()->status)->toBe(PaymentRequest::STATUS_PENDING);

    // A suspended staff account loses review power too.
    $this->staff->forceFill(['disabled_at' => $this->now])->save();
    try {
        ApprovePayment::approve($pending->id, $this->staff, $this->now);
        $approvedBySuspended = true;
    } catch (ValidationException) {
        $approvedBySuspended = false;
    }
    expect($approvedBySuspended)->toBeFalse();
});

test('rejected, cancelled, awaiting, and receipt-less requests are refused', function () {
    // Awaiting (no receipt yet) is not approvable.
    $this->actingAs($this->studentA)->post(route('payments.store'), ['plan_id' => $this->plan->id]);
    $awaiting = PaymentRequest::where('user_id', $this->studentA->id)->firstOrFail();
    try {
        ApprovePayment::approve($awaiting->id, $this->staff, $this->now);
        $awaitingApproved = true;
    } catch (ValidationException) {
        $awaitingApproved = false;
    }
    expect($awaitingApproved)->toBeFalse();

    // Receipt-less pending (backfilled state) is refused, not granted.
    $pending = S6Payments::pendingRequest($this, $this->studentB, $this->plan);
    $pending->forceFill(['receipt_path' => null])->save();
    try {
        ApprovePayment::approve($pending->id, $this->staff, $this->now);
        $receiptlessApproved = true;
    } catch (ValidationException $e) {
        $receiptlessApproved = false;
        expect(collect($e->errors())->flatten()->implode(' '))->toContain('receipt');
    }
    expect($receiptlessApproved)->toBeFalse();

    // Rejected and cancelled rows are terminal (fresh users: the
    // single-open rule keeps one open request per user).
    $studentC = S6Payments::student();
    $rejected = S6Payments::pendingRequest($this, $studentC, $this->plan);
    ApprovePayment::reject($rejected->id, $this->staff, 'ناخوانا (TEST)', 'internal (TEST)');
    try {
        ApprovePayment::approve($rejected->id, $this->staff, $this->now);
        $rejectedApproved = true;
    } catch (ValidationException) {
        $rejectedApproved = false;
    }
    expect($rejectedApproved)->toBeFalse();

    $studentD = S6Payments::student();
    $cancelled = S6Payments::pendingRequest($this, $studentD, $this->plan);
    ApprovePayment::cancel($cancelled->id, $this->staff, 'duplicate (TEST)');
    try {
        ApprovePayment::approve($cancelled->id, $this->staff, $this->now);
        $cancelledApproved = true;
    } catch (ValidationException) {
        $cancelledApproved = false;
    }
    expect($cancelledApproved)->toBeFalse('cancelled requests must stay terminal');
});
