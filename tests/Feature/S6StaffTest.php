<?php

use App\Actions\ApprovePayment;
use App\Filament\Resources\PaymentRequests\PaymentRequestResource;
use App\Models\PaymentRequest;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Models\User;
use Database\Seeders\S5PaymentFixtureSeeder;
use Illuminate\Support\Facades\Log;
use Tests\Support\S6Payments;

// S6 staff surface (scope §5, AC-17): the payment queue is staff-only,
// receipts travel the staff-only no-store route, staff accounts come from
// the CLI bootstrap (which logs its creator), and the CLI review path
// shares the same ApprovePayment action as the panel.

beforeEach(function () {
    $this->seed(S5PaymentFixtureSeeder::class);
    $this->studentA = S6Payments::student();
    $this->studentB = S6Payments::student();
    $this->staff = S6Payments::staff();
    $this->plan = Plan::where('slug', 's5-test-30')->firstOrFail();
});

test('payment queue routes refuse students and suspended staff', function () {
    $pending = S6Payments::pendingRequest($this, $this->studentA, $this->plan);

    // Guests are sent to the panel login (before any actingAs leaks).
    auth()->guard('web')->logout();
    $this->get('/admin/payment-requests')->assertRedirect('/admin/login');

    // Students get 403 on the queue and on the record page (negative path).
    $this->actingAs($this->studentA)->get('/admin/payment-requests')->assertForbidden();
    $this->actingAs($this->studentA)->get("/admin/payment-requests/{$pending->id}")->assertForbidden();

    // Active staff reaches both.
    $this->actingAs($this->staff)->get('/admin/payment-requests')->assertOk();
    $this->actingAs($this->staff)->get("/admin/payment-requests/{$pending->id}")->assertOk();

    // Suspended staff loses the panel via canAccessPanel (negative path).
    $this->staff->forceFill(['disabled_at' => now()])->save();
    $this->actingAs($this->staff->fresh())->get('/admin/payment-requests')->assertForbidden();
});

test('queue lists pending first and exposes no expires_at editor', function () {
    // An old approved row must sort after a newer pending row.
    $old = PaymentRequest::factory()->create([
        'user_id' => $this->studentA->id,
        'plan_name_snapshot' => 's6-old-approved-aaa (TEST)',
        'status' => PaymentRequest::STATUS_APPROVED,
        'created_at' => now()->subDays(9),
        'updated_at' => now()->subDays(9),
    ]);
    $new = PaymentRequest::factory()->create([
        'user_id' => $this->studentB->id,
        'plan_name_snapshot' => 's6-new-pending-zzz (TEST)',
        'status' => PaymentRequest::STATUS_PENDING,
        'receipt_path' => 'receipts/s6-test.jpg',
    ]);

    $html = $this->actingAs($this->staff)->get('/admin/payment-requests')->assertOk()->getContent() ?? '';
    expect(strpos($html, 's6-new-pending-zzz (TEST)'))->toBeLessThan(strpos($html, 's6-old-approved-aaa (TEST)'));

    // No create/edit pages anywhere on the queue: expires_at has no
    // free-text editor (all window changes go through audited actions).
    expect(array_keys(PaymentRequestResource::getPages()))->toBe(['index', 'view']);

    $old->delete();
    $new->delete();
});

test('receipt bytes serve staff and owner with no-store, never the other student', function () {
    $pending = S6Payments::pendingRequest($this, $this->studentA, $this->plan);

    foreach ([$this->studentA, $this->staff] as $viewer) {
        $response = $this->actingAs($viewer)->get(route('payments.receipt.show', $pending));
        $response->assertOk();
        expect($response->headers->get('Cache-Control'))->toContain('no-store');
    }

    // The other student is refused; guests are redirected (negative paths).
    $this->actingAs($this->studentB)->get(route('payments.receipt.show', $pending))->assertForbidden();
    auth()->guard('web')->logout();
    $this->get(route('payments.receipt.show', $pending))->assertRedirect();
});

test('staff bootstrap creates staff and logs its creator', function () {
    Log::spy();

    $this->artisan('staff:bootstrap', [
        'email' => 'reviewer@example.com',
        '--by' => 'ops-lead (TEST)',
        '--password' => 'secret123',
    ])->assertOk();

    $created = User::where('email', 'reviewer@example.com')->firstOrFail();
    expect((bool) $created->is_staff)->toBeTrue();
    Log::shouldHaveReceived('info')->with('staff.bootstrap', Mockery::on(
        fn ($context) => ($context['email'] ?? null) === 'reviewer@example.com'
            && ($context['by'] ?? null) === 'ops-lead (TEST)'
    ));

    // The creator is mandatory (negative path).
    $this->artisan('staff:bootstrap', ['email' => 'nobody@example.com'])->assertFailed();
    expect(User::where('email', 'nobody@example.com')->exists())->toBeFalse();
});

test('payment review CLI approves through the shared action', function () {
    $pending = S6Payments::pendingRequest($this, $this->studentA, $this->plan);

    $this->artisan('payment:review', [
        'action' => 'approve',
        'target' => (string) $pending->id,
        '--by' => $this->staff->email,
    ])->assertOk();

    expect($pending->fresh()->status)->toBe(PaymentRequest::STATUS_APPROVED);
    expect(Subscription::where('user_id', $this->studentA->id)->exists())->toBeTrue();

    // Replaying through the CLI is a no-op success, not a second event.
    $this->artisan('payment:review', [
        'action' => 'approve',
        'target' => (string) $pending->id,
        '--by' => $this->staff->email,
    ])->assertOk();
    expect(SubscriptionEvent::where('source_payment_request_id', $pending->id)->count())->toBe(1);

    // The CLI refuses the same states the panel refuses (negative paths).
    $this->artisan('payment:review', [
        'action' => 'approve',
        'target' => (string) $pending->id,
        '--by' => $this->studentB->email,
    ])->assertFailed();
});
