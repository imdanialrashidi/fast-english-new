<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * R3 immersive reader (READ-03): per-lesson sentence timing cues.
     *
     * audio_cues is a validated JSON list of {sentence_index, start_seconds,
     * end_seconds} timed against audio_cues_revision. The reader only uses
     * cues when audio_cues_revision equals the lesson's audio_revision;
     * otherwise it falls back to the plain reader. Structure is validated
     * in App\Support\AudioCues on every write (model hook + Filament form);
     * the CHECK below guards the revision floor only.
     */
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->jsonb('audio_cues')->nullable()->after('glossary');
            $table->unsignedInteger('audio_cues_revision')->nullable()->after('audio_cues');
        });

        DB::statement('alter table lessons add constraint lessons_audio_cues_revision_positive check (audio_cues_revision is null or audio_cues_revision >= 1)');
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['audio_cues', 'audio_cues_revision']);
        });
    }
};
