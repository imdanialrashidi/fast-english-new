<?php

use App\Models\PaymentDestination;
use App\Models\PaymentRequest;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

// S6 races (scope §11.2–11.3, AC-12/13): two REAL database connections —
// two separate `php artisan payment:review` OS processes, each booting its
// own Laravel with an independent PostgreSQL connection — run approve ×
// approve and approve × reject concurrently. Exactly one final result and
// (for approval) exactly one event with one extension must survive.
//
// Isolation: Pest wraps this file's default connection in a transaction,
// which other connections cannot see. Every fixture here is therefore
// created through a committed `pgsql_race` connection (same credentials,
// autocommit) and deleted afterwards, so the suite stays hermetic.

function s6RaceConnection(): string
{
    config(['database.connections.pgsql_race' => config('database.connections.pgsql')]);
    DB::purge('pgsql_race');

    return 'pgsql_race';
}

function s6RaceEnv(): array
{
    $pgsql = config('database.connections.pgsql');

    return [
        'APP_ENV' => 'testing',
        'APP_KEY' => config('app.key'),
        'APP_DEBUG' => 'false',
        'DB_CONNECTION' => 'pgsql',
        'DB_HOST' => $pgsql['host'],
        'DB_PORT' => (string) ($pgsql['port'] ?? '5432'),
        'DB_DATABASE' => $pgsql['database'],
        'DB_USERNAME' => $pgsql['username'],
        'DB_PASSWORD' => $pgsql['password'],
        'CACHE_STORE' => 'array',
        'SESSION_DRIVER' => 'array',
        'QUEUE_CONNECTION' => 'sync',
    ];
}

/**
 * @return array{owner: User, staff: User, request: PaymentRequest, receipt: string}
 */
function s6RaceFixture(string $tag): array
{
    $conn = s6RaceConnection();
    $uniq = $tag.'-'.uniqid();

    $staff = User::on($conn)->forceCreate([
        'name' => 'Race Staff '.$uniq,
        'email' => "race-staff-{$uniq}@example.com",
        'password' => 'password',
        'is_staff' => true,
        'email_verified_at' => now(),
    ]);

    $owner = User::on($conn)->forceCreate([
        'name' => 'Race Owner '.$uniq,
        'email' => "race-owner-{$uniq}@example.com",
        'password' => 'password',
        'is_staff' => false,
        'email_verified_at' => now(),
    ]);

    $plan = Plan::on($conn)->forceCreate([
        // Inactive: even if cleanup failed, the learner subscribe page
        // (active plans only) would be unaffected.
        'slug' => "race-{$uniq}",
        'name_fa' => "پلن مسابقه (TEST) {$uniq}",
        'price_toman' => 100000,
        'duration_days' => 30,
        'is_active' => false,
        'display_order' => 99,
    ]);

    $destination = PaymentDestination::on($conn)->forceCreate([
        'card_number' => 'race-'.$uniq,
        'holder_name' => 'holder (TEST)',
        'bank_name' => 'bank (TEST)',
        'is_active' => false,
    ]);

    // A real receipt file on the shared filesystem (both processes see it).
    $receipt = "receipts/race-{$uniq}.jpg";
    Storage::disk('local')->put($receipt, (string) file_get_contents(database_path('seeders/fixtures/s3-cover-fixture.jpg')));

    $request = PaymentRequest::on($conn)->forceCreate([
        'user_id' => $owner->id,
        'plan_id' => $plan->id,
        'destination_id' => $destination->id,
        'plan_name_snapshot' => $plan->name_fa,
        'amount_toman_snapshot' => 100000,
        'duration_days_snapshot' => 30,
        'destination_snapshot' => ['card_number' => 'x', 'holder_name' => 'y', 'bank_name' => 'z'],
        'receipt_path' => $receipt,
        'status' => PaymentRequest::STATUS_PENDING,
    ]);

    return ['owner' => $owner, 'staff' => $staff, 'request' => $request, 'receipt' => $receipt];
}

function s6RaceCleanup(array $fixture): void
{
    $conn = s6RaceConnection();
    $ownerId = (int) $fixture['owner']->id;
    $staffId = (int) $fixture['staff']->id;

    SubscriptionEvent::on($conn)->where('user_id', $ownerId)->delete();
    Subscription::on($conn)->where('user_id', $ownerId)->delete();
    PaymentRequest::on($conn)->where('user_id', $ownerId)->delete();
    Plan::on($conn)->where('slug', 'like', 'race-%')->where('name_fa', 'like', '%(TEST)%')->delete();
    User::on($conn)->whereIn('id', [$ownerId, $staffId])->delete();
    Storage::disk('local')->delete($fixture['receipt']);
}

/** @return array{Process, Process} */
function s6RaceProcesses(string $firstAction, string $secondAction, int $requestId, string $staffEmail, array $extra = []): array
{
    $make = function (string $action) use ($requestId, $staffEmail, $extra) {
        $command = array_merge(
            [PHP_BINARY, base_path('artisan'), 'payment:review', $action, (string) $requestId, "--by={$staffEmail}"],
            $extra[$action] ?? []
        );

        return new Process($command, base_path(), s6RaceEnv());
    };

    $first = $make($firstAction);
    $second = $make($secondAction);
    $first->start();
    $second->start();

    return [$first, $second];
}

test('parallel approve and approve leaves one result and one event', function () {
    $fixture = s6RaceFixture('aa');
    try {
        [$first, $second] = s6RaceProcesses('approve', 'approve', (int) $fixture['request']->id, $fixture['staff']->email);
        $first->wait();
        $second->wait();

        // Both report success: the loser replays idempotently (exit 0 with
        // "already approved"), the winner approves. Exactly one effect.
        expect($first->isSuccessful())->toBeTrue($first->getErrorOutput().' '.$first->getOutput());
        expect($second->isSuccessful())->toBeTrue($second->getErrorOutput().' '.$second->getOutput());

        $conn = s6RaceConnection();
        $request = PaymentRequest::on($conn)->findOrFail($fixture['request']->id);
        expect($request->status)->toBe(PaymentRequest::STATUS_APPROVED);

        $events = SubscriptionEvent::on($conn)->where('source_payment_request_id', $request->id)->get();
        expect($events)->toHaveCount(1);
        expect($events->first()->type)->toBe(SubscriptionEvent::TYPE_APPROVED);

        // One extension only: the window is exactly the 30-day snapshot.
        $subscription = Subscription::on($conn)->where('user_id', $fixture['owner']->id)->firstOrFail();
        $days = $subscription->starts_at->diffInDays($subscription->expires_at);
        expect(abs((float) $days - 30.0) < 0.001)->toBeTrue("single 30-day extension, got {$days}");
    } finally {
        s6RaceCleanup($fixture);
    }
});

test('parallel approve and reject leaves a single final outcome', function () {
    $fixture = s6RaceFixture('ar');
    try {
        [$approve, $reject] = s6RaceProcesses('approve', 'reject', (int) $fixture['request']->id, $fixture['staff']->email, [
            'reject' => ['--public-reason=ناخوانا (TEST)', '--internal-note=race (TEST)'],
        ]);
        $approve->wait();
        $reject->wait();

        // Mutual exclusion: exactly one side succeeds.
        expect($approve->isSuccessful() !== $reject->isSuccessful())->toBeTrue(
            "approve says: {$approve->getOutput()} {$approve->getErrorOutput()} / reject says: {$reject->getOutput()} {$reject->getErrorOutput()}"
        );

        $conn = s6RaceConnection();
        $request = PaymentRequest::on($conn)->findOrFail($fixture['request']->id);
        expect($request->status)->toBeIn([PaymentRequest::STATUS_APPROVED, PaymentRequest::STATUS_REJECTED]);

        // At most one subscription-changing event, matching the winner:
        // approval writes its one event, rejection writes none.
        $events = SubscriptionEvent::on($conn)->where('source_payment_request_id', $request->id)->count();
        if ($request->status === PaymentRequest::STATUS_APPROVED) {
            expect($events)->toBe(1);
        } else {
            expect($events)->toBe(0);
        }

        // The loser is refused, never half-applied.
        $loser = $approve->isSuccessful() ? $reject : $approve;
        expect($loser->getExitCode())->not->toBe(0);
    } finally {
        s6RaceCleanup($fixture);
    }
});

test('parallel approve and grant leaves no lost update and one consistent window', function () {
    // AC-13 gap (S7 pre-slice 1): approve × manual grant on the same user
    // run as two real OS processes with independent PostgreSQL
    // connections. Both lock User first (User → Request → Subscription vs
    // User → Subscription), so they serialize on the owner row: both
    // effects must survive, with one consistent window.
    $fixture = s6RaceFixture('ag');
    try {
        $env = s6RaceEnv();
        $approve = new Process(
            [PHP_BINARY, base_path('artisan'), 'payment:review', 'approve', (string) $fixture['request']->id, "--by={$fixture['staff']->email}"],
            base_path(),
            $env
        );
        $grant = new Process(
            [PHP_BINARY, base_path('artisan'), 'payment:review', 'grant', (string) $fixture['owner']->id, "--by={$fixture['staff']->email}", '--duration=30', '--reason=race grant (TEST)'],
            base_path(),
            $env
        );
        $approve->start();
        $grant->start();
        $approve->wait();
        $grant->wait();

        // Both sides succeed: unlike approve × reject, grant and approve
        // are compatible — each writes its own event.
        expect($approve->isSuccessful())->toBeTrue($approve->getErrorOutput().' '.$approve->getOutput());
        expect($grant->isSuccessful())->toBeTrue($grant->getErrorOutput().' '.$grant->getOutput());

        $conn = s6RaceConnection();
        $request = PaymentRequest::on($conn)->findOrFail($fixture['request']->id);
        expect($request->status)->toBe(PaymentRequest::STATUS_APPROVED);

        // No lost update: both events exist for the same user.
        $approved = SubscriptionEvent::on($conn)
            ->where('source_payment_request_id', $request->id)->get();
        expect($approved)->toHaveCount(1);
        expect($approved->first()->type)->toBe(SubscriptionEvent::TYPE_APPROVED);

        $granted = SubscriptionEvent::on($conn)
            ->where('user_id', $fixture['owner']->id)
            ->where('type', SubscriptionEvent::TYPE_GRANTED)->get();
        expect($granted)->toHaveCount(1);

        expect(SubscriptionEvent::on($conn)->where('user_id', $fixture['owner']->id)->count())->toBe(2);

        // One consistent window: the two 30-day effects stack to 60 days
        // from the first writer's start, revocation stays clear, and the
        // window is internally consistent (expiry after start).
        $subscription = Subscription::on($conn)->where('user_id', $fixture['owner']->id)->firstOrFail();
        expect($subscription->revoked_at)->toBeNull();
        expect($subscription->expires_at->greaterThan($subscription->starts_at))->toBeTrue();
        $days = $subscription->starts_at->diffInDays($subscription->expires_at);
        expect(abs((float) $days - 60.0) < 0.01)->toBeTrue("stacked 30+30-day window, got {$days}");
    } finally {
        s6RaceCleanup($fixture);
    }
});
