<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * S7 placement (scope §12, §14): versioned 20-question tests, pinned
     * attempts, and per-question answers.
     *
     * - placement_tests: version unique, status draft/published, single
     *   is_current among published (partial unique + check), server-only
     *   scoring_rules JSON (FIXTURE mapping, never sent to clients).
     * - placement_questions: test_id + position unique, prompt + options
     *   JSON (FIXTURE rows only, never real exam content), correct_option
     *   server-only (0–3, hidden from serialization and views).
     * - placement_attempts: pinned test_id, status
     *   in_progress/completed/abandoned, single in_progress per user
     *   (partial unique), score 0–20 + recommended_level written only at
     *   completion. Browsing never writes preferred/recommended levels.
     * - placement_answers: unique (attempt_id, question_id), selected
     *   option 0–3, server-side correctness filled at submit only.
     *
     * Published versions and their questions are immutable (model guards
     * + PublishPlacementTest action); editing creates a new draft.
     * Financial history rules do not apply here, but test/question rows
     * with attempts/answers are restrict-deleted (archive, never cascade).
     */
    public function up(): void
    {
        Schema::create('placement_tests', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique();
            $table->string('status', 32)->default('draft');
            $table->boolean('is_current')->default(false);
            // Server-only fixture scoring map (never serialized to clients).
            $table->json('scoring_rules')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'is_current']);
        });

        DB::statement("alter table placement_tests add constraint placement_tests_status_allowed check (status in ('draft', 'published', 'archived'))");
        // Current implies published: only a published version may be current.
        DB::statement('alter table placement_tests add constraint placement_tests_current_implies_published check (is_current = false or status = \'published\')');
        // Single current published version (scope §14).
        DB::statement('create unique index placement_tests_single_current on placement_tests (is_current) where is_current and status = \'published\'');

        Schema::create('placement_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_id')->constrained('placement_tests')->restrictOnDelete();
            $table->integer('position');
            // Every seeded prompt/option is marked FIXTURE (never real exam).
            $table->text('prompt');
            $table->json('options');
            // Server-only answer key (0–3); hidden + never rendered.
            $table->smallInteger('correct_option');
            $table->timestamps();

            $table->unique(['test_id', 'position']);
            $table->index(['test_id', 'position']);
        });

        DB::statement('alter table placement_questions add constraint placement_questions_position_range check (position >= 1 and position <= 20)');
        DB::statement('alter table placement_questions add constraint placement_questions_correct_range check (correct_option >= 0 and correct_option <= 3)');

        Schema::create('placement_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_id')->constrained('placement_tests')->restrictOnDelete();
            $table->string('status', 32)->default('in_progress');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->integer('score')->nullable();
            $table->string('recommended_level', 8)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'started_at']);
            $table->index(['user_id', 'status']);
        });

        DB::statement("alter table placement_attempts add constraint placement_attempts_status_allowed check (status in ('in_progress', 'completed', 'abandoned'))");
        DB::statement('alter table placement_attempts add constraint placement_attempts_score_range check (score is null or (score >= 0 and score <= 20))');
        // Single open attempt per user (scope §12).
        DB::statement("create unique index placement_attempts_single_open on placement_attempts (user_id) where status = 'in_progress'");

        Schema::create('placement_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attempt_id')->constrained('placement_attempts')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('placement_questions')->restrictOnDelete();
            $table->smallInteger('selected_option');
            // Server-side correctness, filled at submit only (hidden).
            $table->boolean('is_correct')->nullable();
            $table->timestamps();

            $table->unique(['attempt_id', 'question_id']);
            $table->index(['attempt_id', 'question_id']);
        });

        DB::statement('alter table placement_answers add constraint placement_answers_selected_range check (selected_option >= 0 and selected_option <= 3)');
    }

    public function down(): void
    {
        DB::statement('drop index if exists placement_attempts_single_open');
        DB::statement('drop index if exists placement_tests_single_current');
        Schema::dropIfExists('placement_answers');
        Schema::dropIfExists('placement_attempts');
        Schema::dropIfExists('placement_questions');
        Schema::dropIfExists('placement_tests');
    }
};
