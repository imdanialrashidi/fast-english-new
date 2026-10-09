<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * S6 review + entitlement (scope §14): subscriptions current window per
     * user plus the immutable subscription_events audit.
     *
     * Money stays integer toman (scope §8): durations are exact days, no
     * floats, no rial conversion. Timestamps are UTC (app timezone).
     * Financial history is never cascade-deleted (scope §14).
     */
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            // One current window per user (scope §11.1, §14).
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        DB::statement('alter table subscriptions add constraint subscriptions_expiry_after_start check (expires_at > starts_at)');

        Schema::create('subscription_events', function (Blueprint $table) {
            // Immutable audit (scope §11.1, §14): every approve or manual
            // grant/revoke writes exactly one row. A payment request is the
            // source of at most one event — the unique-when-not-null key on
            // source_payment_request_id is the replay/race backstop.
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('subscription_id')->constrained()->restrictOnDelete();
            $table->foreignId('source_payment_request_id')->nullable()->constrained('payment_requests')->restrictOnDelete();
            // approved (via request), granted / revoked (manual, no request).
            $table->string('type', 32);
            $table->timestamp('before_starts_at')->nullable();
            $table->timestamp('before_expires_at')->nullable();
            $table->timestamp('after_starts_at');
            $table->timestamp('after_expires_at');
            $table->integer('duration_days')->nullable();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            // Immutable: created once, never updated (no updated_at).
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });

        // One event per payment request; manual events carry NULL and may
        // repeat (scope §14: unique-when-not-null).
        DB::statement('create unique index subscription_events_source_unique on subscription_events (source_payment_request_id) where source_payment_request_id is not null');
        DB::statement("alter table subscription_events add constraint subscription_events_type_allowed check (type in ('approved', 'granted', 'revoked'))");
        DB::statement('alter table subscription_events add constraint subscription_events_after_after_start check (after_expires_at > after_starts_at)');
        DB::statement('alter table subscription_events add constraint subscription_events_duration_positive check (duration_days is null or duration_days > 0)');
    }

    public function down(): void
    {
        DB::statement('drop index if exists subscription_events_source_unique');
        Schema::dropIfExists('subscription_events');
        Schema::dropIfExists('subscriptions');
    }
};
