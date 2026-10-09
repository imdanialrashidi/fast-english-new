<?php

namespace App\Models;

use App\Support\AudioCues;
use App\Support\Glossary;
use App\Support\Sentences;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

#[Hidden(['audio_path'])]
class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    protected $fillable = [
        'topic_id',
        'level',
        'title_en',
        'body_en',
        'glossary',
        'audio_path',
        'audio_revision',
        'audio_cues',
        'audio_cues_revision',
        'duration_seconds',
        'estimated_minutes',
        'is_public_sample',
        'status',
        'reviewed_by',
        'reviewed_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'glossary' => 'array',
            'audio_cues' => 'array',
            'audio_cues_revision' => 'integer',
            'audio_revision' => 'integer',
            'duration_seconds' => 'integer',
            'estimated_minutes' => 'integer',
            'is_public_sample' => 'boolean',
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    protected static function booted(): void
    {
        static::saving(function (Lesson $lesson) {
            // Glossary is stored as validated JSON: normalize on write.
            if ($lesson->isDirty('glossary') && $lesson->glossary !== null) {
                $lesson->glossary = Glossary::validate($lesson->glossary);
            }

            // R3 cues are validated against the lesson's measured duration
            // and pinned to the revision they were timed against. A stale
            // revision never applies: the reader ignores mismatched cues.
            if ($lesson->isDirty('audio_cues') && $lesson->audio_cues !== null) {
                $lesson->audio_cues = AudioCues::validate($lesson->audio_cues, (int) ($lesson->duration_seconds ?? 0));
                if ($lesson->audio_cues === []) {
                    // No timing data: nothing to pin to a revision.
                    $lesson->audio_cues_revision = null;
                } elseif ($lesson->audio_cues_revision === null) {
                    $lesson->audio_cues_revision = max(1, (int) $lesson->audio_revision);
                }
                // Cue indexes must match the reader's sentence split.
                // Only enforced while cues are being edited, so a plain
                // typo fix never gets blocked by stale timing data.
                if ($lesson->audio_cues !== [] && is_string($lesson->body_en) && trim($lesson->body_en) !== '') {
                    $count = count(Sentences::split($lesson->body_en));
                    if ($count !== count($lesson->audio_cues)) {
                        throw ValidationException::withMessages([
                            'audio_cues' => 'تعداد زمان‌بندی‌ها ('.count($lesson->audio_cues).') با تعداد جمله‌های متن ('.$count.') برابر نیست.',
                        ]);
                    }
                }
            }

            // Scope §9.3: replacing the audio file increments the revision;
            // a title change or typo fix leaves it untouched.
            if ($lesson->exists && $lesson->isDirty('audio_path')) {
                $lesson->audio_revision = ((int) $lesson->getOriginal('audio_revision')) + 1;
            }
        });

        // Same archive-instead-of-delete rule as topics: only a draft that
        // was never published may be deleted; its private audio is removed.
        static::deleting(function (Lesson $lesson) {
            if ($lesson->status !== 'draft' || $lesson->published_at !== null) {
                throw new \RuntimeException(
                    'A lesson that was published cannot be deleted; archive it instead.'
                );
            }

            if (is_string($lesson->audio_path) && $lesson->audio_path !== '') {
                Storage::disk('local')->delete($lesson->audio_path);
            }
        });
    }
}
