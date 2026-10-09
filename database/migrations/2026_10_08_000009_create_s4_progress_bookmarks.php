<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * S4 returning user (scope §14): lesson_progress with unique
     * (user_id, lesson_id) — audio_revision is a plain column, never part
     * of the key — plus bookmarks with unique (user_id, topic_id) and the
     * nullable users.preferred_level constrained to A1–C2.
     *
     * No subscriptions, payments, placement, or receipts in this slice.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('preferred_level', ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'])
                ->nullable()
                ->after('disabled_at');
        });

        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('audio_revision')->default(1);
            $table->decimal('position_seconds', 10, 2)->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'lesson_id']);
            $table->index(['user_id', 'updated_at']);
        });

        // Position is never negative; the upper bound (server duration) is
        // enforced in the controller, not here, because durations live on
        // lessons and change with fixtures.
        DB::statement('alter table lesson_progress add constraint lesson_progress_position_non_negative check (position_seconds >= 0)');
        DB::statement('alter table lesson_progress add constraint lesson_progress_audio_revision_positive check (audio_revision >= 1)');

        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'topic_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookmarks');
        Schema::dropIfExists('lesson_progress');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('preferred_level');
        });
    }
};
