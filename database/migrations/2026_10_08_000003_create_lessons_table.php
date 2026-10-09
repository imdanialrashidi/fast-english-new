<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * S1: one lesson row per (topic, level). Only the S1 slice fields are
     * added here -- no progress, bookmarks, subscriptions, or glossary.
     * Scope §14 constraints: unique(topic_id, level), allowed level/status
     * sets (enums compile to CHECK on PostgreSQL), positive durations.
     */
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->enum('level', ['A1', 'A2', 'B1', 'B2', 'C1', 'C2']);
            $table->string('title_en');
            $table->text('body_en');
            $table->string('audio_path');
            $table->unsignedInteger('audio_revision')->default(1);
            $table->unsignedInteger('duration_seconds');
            $table->unsignedInteger('estimated_minutes');
            $table->boolean('is_public_sample')->default(false);
            $table->enum('status', ['draft', 'published', 'archived']);
            $table->timestamps();

            $table->unique(['topic_id', 'level']);
            $table->index(['status']);
        });

        DB::statement('alter table lessons add constraint lessons_duration_seconds_positive check (duration_seconds > 0)');
        DB::statement('alter table lessons add constraint lessons_estimated_minutes_positive check (estimated_minutes > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
