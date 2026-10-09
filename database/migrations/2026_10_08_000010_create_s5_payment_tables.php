<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * S5 payment intake (scope §14): plans, payment_destinations, and
     * payment_requests with a server-written snapshot.
     *
     * Money is integer toman everywhere (scope §8): no floats, no rial
     * conversion. Positive checks guard every money/duration column.
     * No subscriptions or subscription_events here — those are S6, and the
     * subscriptions table must not exist after this slice.
     */
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_fa');
            // Integer toman (scope §8). Positive check below.
            $table->integer('price_toman');
            $table->integer('duration_days');
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'display_order']);
        });

        DB::statement('alter table plans add constraint plans_price_toman_positive check (price_toman > 0)');
        DB::statement('alter table plans add constraint plans_duration_days_positive check (duration_days > 0)');

        Schema::create('payment_destinations', function (Blueprint $table) {
            $table->id();
            $table->string('card_number');
            $table->string('holder_name');
            $table->string('bank_name');
            $table->text('instructions')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        // At most one active destination (scope §14): a partial unique
        // index admits only a single row with is_active = true.
        DB::statement('create unique index payment_destinations_single_active on payment_destinations (is_active) where is_active');

        Schema::create('payment_requests', function (Blueprint $table) {
            $table->id();
            // Financial history is never cascade-deleted (scope §14):
            // deleting a user/plan/destination with history is refused;
            // archive/disable is the supported path.
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('destination_id')->constrained('payment_destinations')->restrictOnDelete();
            // Server-written snapshot (scope §10.2): immutable plan name,
            // integer toman amount, duration days, and destination JSON.
            $table->string('plan_name_snapshot');
            $table->integer('amount_toman_snapshot');
            $table->integer('duration_days_snapshot');
            $table->json('destination_snapshot');
            // Private receipt (scope §10.3): random path on the private
            // disk, nullable until the receipt is submitted.
            $table->string('receipt_path')->nullable();
            $table->timestamp('receipt_deleted_at')->nullable();
            // Optional transfer details (scope §10.2): tracking code, last
            // four digits of the source card, transfer time. Never CVV2,
            // PIN, expiry, the full source number, or a card image.
            $table->string('bank_reference', 64)->nullable();
            $table->string('sender_last4', 4)->nullable();
            $table->timestamp('transferred_at')->nullable();
            // Status vocabulary from scope §10.1 exactly.
            $table->enum('status', ['awaiting_receipt', 'pending', 'approved', 'rejected', 'cancelled'])
                ->default('awaiting_receipt');
            $table->text('public_reason')->nullable();
            // Staff-only (never rendered to learners in S5).
            $table->text('internal_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'status']);
        });

        DB::statement('alter table payment_requests add constraint payment_requests_amount_positive check (amount_toman_snapshot > 0)');
        DB::statement('alter table payment_requests add constraint payment_requests_duration_positive check (duration_days_snapshot > 0)');
        DB::statement("alter table payment_requests add constraint payment_requests_sender_last4_format check (sender_last4 is null or sender_last4 ~ '^[0-9]{4}$')");

        // One open request per user (scope §10.1): partial unique on
        // user_id for the two open states. Concurrent double-creates hit
        // this index and leave exactly one open request.
        DB::statement("create unique index payment_requests_single_open on payment_requests (user_id) where status in ('awaiting_receipt', 'pending')");
    }

    public function down(): void
    {
        DB::statement('drop index if exists payment_requests_single_open');
        DB::statement('drop index if exists payment_destinations_single_active');
        Schema::dropIfExists('payment_requests');
        Schema::dropIfExists('payment_destinations');
        Schema::dropIfExists('plans');
    }
};
