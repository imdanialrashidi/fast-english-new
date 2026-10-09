<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * R4 vocabulary notebook (VOCAB-01): one row per saved word per user.
     *
     * word_key is the case-folded word for the unique (user_id, word_key)
     * pair. status is learning (due or scheduled) or known (student-marked,
     * excluded from review). Scheduling columns carry a simple deterministic
     * SM-2-style rule owned by App\Support\SpacedRepetition; the database
     * only constrains ranges and the grade vocabulary.
     */
    public function up(): void
    {
        Schema::create('vocabulary_words', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('word', 120);
            $table->string('word_key', 120);
            $table->string('meaning_fa', 500)->nullable();
            $table->string('example_en', 500)->nullable();
            $table->foreignId('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 16)->default('learning');
            $table->float('ease_factor')->default(2.5);
            $table->unsignedInteger('interval_days')->default(0);
            $table->unsignedInteger('repetitions')->default(0);
            $table->unsignedInteger('lapses')->default(0);
            $table->timestamp('due_at')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'word_key']);
            $table->index(['user_id', 'due_at']);
            $table->index(['user_id', 'status']);
        });

        DB::statement("alter table vocabulary_words add constraint vocabulary_words_status_allowed check (status in ('learning','known'))");
        DB::statement('alter table vocabulary_words add constraint vocabulary_words_ease_floor check (ease_factor >= 1.3)');
    }

    public function down(): void
    {
        Schema::dropIfExists('vocabulary_words');
    }
};
