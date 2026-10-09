<?php

use App\Models\PaymentRequest;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// S6 data contract (scope §14, AC-16 audit leg): subscriptions holds one
// current window per user; subscription_events is the immutable audit with
// a unique-when-not-null source key. Money stays integer toman (no money
// columns here — only exact-day windows); timestamps are UTC.

test('subscriptions and subscription_events have the contracted shape', function () {
    expect(Schema::hasTable('subscriptions'))->toBeTrue()
        ->and(Schema::hasTable('subscription_events'))->toBeTrue();

    foreach (['id', 'user_id', 'starts_at', 'expires_at', 'revoked_at', 'created_at', 'updated_at'] as $column) {
        expect(Schema::hasColumn('subscriptions', $column))->toBeTrue("subscriptions.{$column} missing");
    }

    foreach (['id', 'user_id', 'subscription_id', 'source_payment_request_id', 'type', 'before_starts_at', 'before_expires_at', 'after_starts_at', 'after_expires_at', 'duration_days', 'actor_id', 'reason', 'created_at'] as $column) {
        expect(Schema::hasColumn('subscription_events', $column))->toBeTrue("subscription_events.{$column} missing");
    }

    // Immutable audit: no updated_at on events.
    expect(Schema::hasColumn('subscription_events', 'updated_at'))->toBeFalse();
});

test('one window per user and one event per payment request', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $subscription = new Subscription;
    $subscription->forceFill([
        'user_id' => $user->id,
        'starts_at' => now(),
        'expires_at' => now()->addDays(30),
        'revoked_at' => null,
    ]);
    $subscription->save();

    // Second window for the same user is refused by the unique key.
    $duplicate = false;
    try {
        DB::transaction(function () use ($user) {
            $row = new Subscription;
            $row->forceFill([
                'user_id' => $user->id,
                'starts_at' => now(),
                'expires_at' => now()->addDays(30),
            ]);
            $row->save();
        });
    } catch (QueryException) {
        $duplicate = true;
    }
    expect($duplicate)->toBeTrue('second subscription for one user must be refused');

    $event = function (?int $source) use ($user, $subscription) {
        $row = new SubscriptionEvent;
        $row->forceFill([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'source_payment_request_id' => $source,
            'type' => SubscriptionEvent::TYPE_GRANTED,
            'before_starts_at' => null,
            'before_expires_at' => null,
            'after_starts_at' => now(),
            'after_expires_at' => now()->addDays(30),
            'duration_days' => 30,
            'actor_id' => null,
            'reason' => 'test',
            'created_at' => now(),
        ]);
        $row->save();

        return $row;
    };

    // Manual (NULL-source) events may repeat; a request source may not.
    $event(null);
    $event(null);

    $request = PaymentRequest::factory()->create(['user_id' => $other->id]);
    $otherSub = new Subscription;
    $otherSub->forceFill([
        'user_id' => $other->id,
        'starts_at' => now(),
        'expires_at' => now()->addDays(30),
    ]);
    $otherSub->save();

    $row = new SubscriptionEvent;
    $row->forceFill([
        'user_id' => $other->id,
        'subscription_id' => $otherSub->id,
        'source_payment_request_id' => $request->id,
        'type' => SubscriptionEvent::TYPE_APPROVED,
        'before_starts_at' => null,
        'before_expires_at' => null,
        'after_starts_at' => now(),
        'after_expires_at' => now()->addDays(30),
        'duration_days' => 30,
        'actor_id' => null,
        'reason' => null,
        'created_at' => now(),
    ]);
    $row->save();

    $second = false;
    try {
        DB::transaction(function () use ($other, $otherSub, $request) {
            $row = new SubscriptionEvent;
            $row->forceFill([
                'user_id' => $other->id,
                'subscription_id' => $otherSub->id,
                'source_payment_request_id' => $request->id,
                'type' => SubscriptionEvent::TYPE_APPROVED,
                'after_starts_at' => now(),
                'after_expires_at' => now()->addDays(30),
                'created_at' => now(),
            ]);
            $row->save();
        });
    } catch (QueryException) {
        $second = true;
    }
    expect($second)->toBeTrue('second event for one request must be refused');
});

test('window and event checks refuse invalid rows', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    // Expiry must be after start.
    $badWindow = false;
    try {
        DB::transaction(function () use ($user) {
            $row = new Subscription;
            $row->forceFill([
                'user_id' => $user->id,
                'starts_at' => now(),
                'expires_at' => now(),
            ]);
            $row->save();
        });
    } catch (QueryException) {
        $badWindow = true;
    }
    expect($badWindow)->toBeTrue('expires_at <= starts_at must be refused');

    $subscription = new Subscription;
    $subscription->forceFill([
        'user_id' => $other->id,
        'starts_at' => now(),
        'expires_at' => now()->addDays(30),
    ]);
    $subscription->save();

    $badType = false;
    try {
        DB::transaction(function () use ($other, $subscription) {
            $row = new SubscriptionEvent;
            $row->forceFill([
                'user_id' => $other->id,
                'subscription_id' => $subscription->id,
                'type' => 'edited-by-hand',
                'after_starts_at' => now(),
                'after_expires_at' => now()->addDays(30),
                'created_at' => now(),
            ]);
            $row->save();
        });
    } catch (QueryException) {
        $badType = true;
    }
    expect($badType)->toBeTrue('unknown event type must be refused');

    $badDuration = false;
    try {
        DB::transaction(function () use ($other, $subscription) {
            $row = new SubscriptionEvent;
            $row->forceFill([
                'user_id' => $other->id,
                'subscription_id' => $subscription->id,
                'type' => SubscriptionEvent::TYPE_GRANTED,
                'after_starts_at' => now(),
                'after_expires_at' => now()->addDays(30),
                'duration_days' => 0,
                'created_at' => now(),
            ]);
            $row->save();
        });
    } catch (QueryException) {
        $badDuration = true;
    }
    expect($badDuration)->toBeTrue('non-positive duration must be refused');
});
