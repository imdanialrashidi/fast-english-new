<?php

namespace App\Actions;

use App\Models\Lesson;
use App\Models\Topic;
use App\Support\AudioFile;
use App\Support\TopicLicense;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * S3 single owner of every content state transition (scope §9.3).
 *
 * The Filament resources and the `content:transition` CLI both call this
 * action; the logic is never copied into callers. Lifecycle:
 * draft → published → archived, with a controlled return archived → draft.
 * Publishing is explicit; a repeated publish of a still-valid lesson is
 * idempotent (no state change, no error).
 *
 * Refusal throws ValidationException with a specific per-field error and
 * changes nothing (validation runs before any write, inside a transaction).
 */
final class PublishLesson
{
    public const LEVELS = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    // -- lessons -----------------------------------------------------------

    /**
     * @return array<string, string> field => reason; empty when publishable.
     */
    public static function lessonProblems(Lesson $lesson): array
    {
        $lesson->loadMissing('topic');
        $problems = [];

        $topic = $lesson->topic;
        if ($topic === null) {
            $problems['topic'] = 'The lesson has no topic.';
        } else {
            if ($topic->status !== 'published') {
                $problems['topic'] = 'The parent topic must be published first.';
            }
            if (trim((string) $topic->title_en) === '' || trim((string) $topic->summary_public) === '') {
                $problems['topic'] = 'The parent topic is missing its title or summary.';
            }
        }

        if (! in_array($lesson->level, self::LEVELS, true)) {
            $problems['level'] = 'The lesson level must be one of '.implode(', ', self::LEVELS).'.';
        }

        if (trim((string) $lesson->title_en) === '') {
            $problems['title_en'] = 'The lesson title must not be empty.';
        }

        if (trim((string) $lesson->body_en) === '') {
            $problems['body_en'] = 'The lesson body must not be empty.';
        }

        $audioProblem = AudioFile::problem($lesson->audio_path);
        if ($audioProblem !== null) {
            $problems['audio'] = 'The lesson audio is unusable: '.$audioProblem.'.';
        }

        if ((int) $lesson->duration_seconds <= 0) {
            $problems['duration_seconds'] = 'The lesson needs a valid positive duration.';
        }

        if ($lesson->reviewed_by === null || $lesson->reviewed_at === null) {
            $problems['review'] = 'The lesson needs a recorded content review (reviewer and time).';
        }

        return $problems;
    }

    /** @throws ValidationException */
    public static function publish(Lesson $lesson): Lesson
    {
        return DB::transaction(function () use ($lesson) {
            $lesson->refresh();

            if ($lesson->status === 'archived') {
                throw ValidationException::withMessages([
                    'status' => 'An archived lesson must return to draft before it can publish.',
                ]);
            }

            $problems = self::lessonProblems($lesson);
            if ($problems !== []) {
                throw ValidationException::withMessages($problems);
            }

            if ($lesson->status === 'published') {
                return $lesson; // idempotent: still valid, nothing to change.
            }

            $lesson->status = 'published';
            $lesson->published_at = $lesson->published_at ?? now();
            $lesson->save();

            return $lesson;
        });
    }

    /** @throws ValidationException */
    public static function archive(Lesson $lesson): Lesson
    {
        return DB::transaction(function () use ($lesson) {
            $lesson->refresh();

            if ($lesson->status === 'archived') {
                return $lesson; // idempotent.
            }

            $lesson->status = 'archived';
            $lesson->save();

            return $lesson;
        });
    }

    /** @throws ValidationException */
    public static function returnToDraft(Lesson $lesson): Lesson
    {
        return DB::transaction(function () use ($lesson) {
            $lesson->refresh();

            if ($lesson->status === 'draft') {
                return $lesson; // idempotent.
            }

            if ($lesson->status === 'published') {
                throw ValidationException::withMessages([
                    'status' => 'A published lesson must be archived before it can return to draft.',
                ]);
            }

            $lesson->status = 'draft';
            $lesson->save();

            return $lesson;
        });
    }

    // -- topics ------------------------------------------------------------

    /** @return array<string, string> */
    public static function topicProblems(Topic $topic): array
    {
        $problems = [];

        if (trim((string) $topic->slug) === '') {
            $problems['slug'] = 'The topic slug must not be empty.';
        }
        if (trim((string) $topic->title_en) === '') {
            $problems['title_en'] = 'The topic title must not be empty.';
        }
        if (trim((string) $topic->summary_public) === '') {
            $problems['summary_public'] = 'The topic summary must not be empty.';
        }

        // D4: every published topic shows a real cover, never empty.
        if (trim((string) $topic->cover_path) === '') {
            $problems['cover_path'] = 'The topic needs a cover before it can publish.';
        } else {
            // D4: a staff-uploaded cover (anything outside covers/gd-*.jpg)
            // needs a license record (source + license + date checked + notes).
            // GD default covers are exempt. No runtime third-party fetch.
            $licenseProblem = TopicLicense::problem($topic->cover_path, $topic->source_note);
            if ($licenseProblem !== null) {
                $problems['cover_path'] = $licenseProblem;
            }
        }

        return $problems;
    }

    /** @throws ValidationException */
    public static function publishTopic(Topic $topic): Topic
    {
        return DB::transaction(function () use ($topic) {
            $topic->refresh();

            if ($topic->status === 'archived') {
                throw ValidationException::withMessages([
                    'status' => 'An archived topic must return to draft before it can publish.',
                ]);
            }

            $problems = self::topicProblems($topic);
            if ($problems !== []) {
                throw ValidationException::withMessages($problems);
            }

            if ($topic->status === 'published') {
                return $topic; // idempotent.
            }

            $topic->status = 'published';
            $topic->published_at = $topic->published_at ?? now();
            $topic->save();

            return $topic;
        });
    }

    /** @throws ValidationException */
    public static function archiveTopic(Topic $topic): Topic
    {
        return DB::transaction(function () use ($topic) {
            $topic->refresh();

            if ($topic->status === 'archived') {
                return $topic; // idempotent.
            }

            $topic->status = 'archived';
            $topic->save();

            return $topic;
        });
    }

    /** @throws ValidationException */
    public static function returnTopicToDraft(Topic $topic): Topic
    {
        return DB::transaction(function () use ($topic) {
            $topic->refresh();

            if ($topic->status === 'draft') {
                return $topic; // idempotent.
            }

            if ($topic->status === 'published') {
                throw ValidationException::withMessages([
                    'status' => 'A published topic must be archived before it can return to draft.',
                ]);
            }

            $topic->status = 'draft';
            $topic->save();

            return $topic;
        });
    }
}
