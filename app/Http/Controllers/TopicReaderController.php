<?php

namespace App\Http\Controllers;

use App\Models\Bookmark;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Topic;
use App\Support\Sentences;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * S1 reader: /app/topics/{slug}?level=XX.
 *
 * The level comes from the URL only (plain link/GET). S4 keeps the rule:
 * browsing a level never writes users.preferred_level (only the explicit
 * settings form does). A missing level is never silently substituted.
 *
 * S4 adds resume data for the signed-in owner only: when the stored
 * revision matches the current lesson revision the saved position is
 * injected as the initial position (no autoplay); otherwise zero. Guests
 * receive zero and their listening is never saved server-side.
 */
class TopicReaderController extends Controller
{
    private const LEVELS = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    public function show(Request $request, Topic $topic)
    {
        if ($topic->status !== 'published') {
            abort(404);
        }

        $publishedLessons = $topic->lessons()
            ->where('status', 'published')
            ->orderByRaw($this->levelOrder())
            ->get();

        if ($publishedLessons->isEmpty()) {
            abort(404);
        }

        $availableLevels = $publishedLessons->pluck('level')->all();

        $rawLevel = $request->query('level', '');
        $requested = is_string($rawLevel) ? strtoupper($rawLevel) : '';
        if ($requested === '') {
            // S4: an explicit preferred level is honored as the default
            // when it exists for this topic; otherwise the first available
            // level. Browsing never writes preferred_level.
            $userLevel = $request->user()?->preferred_level;
            if (is_string($userLevel) && in_array($userLevel, $availableLevels, true)) {
                $requested = $userLevel;
            } else {
                $requested = $availableLevels[0];
            }
        }

        $lesson = $publishedLessons->firstWhere('level', $requested);

        if ($lesson === null) {
            return response()
                ->view('reader.not-ready', [
                    'topic' => $topic,
                    'requestedLevel' => $requested,
                    'availableLevels' => $availableLevels,
                ])
                ->withHeaders($this->noStore());
        }

        $this->authorizeLesson($lesson);

        // R3 sentence cues: only the cues timed against the current audio
        // revision are exposed, and only when their count matches the
        // reader's sentence split. Otherwise the plain reader stays with
        // an understandable fallback — the lesson remains completable.
        $sentences = Sentences::split((string) $lesson->body_en);
        $cues = is_array($lesson->audio_cues) ? $lesson->audio_cues : [];
        $cuesUsable = $cues !== []
            && (int) ($lesson->audio_cues_revision ?? 0) === (int) $lesson->audio_revision
            && count($cues) === count($sentences);
        if (! $cuesUsable) {
            $cues = [];
        }

        // S4 resume: owner-only, revision-isolated. No user write happens
        // here — the level in the URL never touches preferred_level.
        $initialPosition = 0.0;
        $completedAt = null;
        $bookmarked = false;
        $user = $request->user();
        if ($user !== null && $user->disabled_at === null) {
            $stored = LessonProgress::where('user_id', $user->id)
                ->where('lesson_id', $lesson->id)
                ->first();
            if ($stored !== null && (int) $stored->audio_revision === (int) $lesson->audio_revision) {
                $initialPosition = max(0.0, (float) $stored->position_seconds);
                $completedAt = $stored->completed_at;
            }
            $bookmarked = Bookmark::where('user_id', $user->id)
                ->where('topic_id', $topic->id)
                ->exists();
        }

        return response()
            ->view('reader.show', [
                'topic' => $topic,
                'lesson' => $lesson,
                'availableLevels' => $availableLevels,
                'initialPosition' => $initialPosition,
                'completedAt' => $completedAt,
                'bookmarked' => $bookmarked,
                'sentences' => $sentences,
                'cues' => $cues,
                'cuesUsable' => $cuesUsable,
            ])
            ->withHeaders($this->noStore());
    }

    private function authorizeLesson(Lesson $lesson): void
    {
        if ($lesson->status !== 'published' || $lesson->topic->status !== 'published') {
            abort(404);
        }

        if (Gate::allows('view', $lesson) !== true) {
            abort(403);
        }
    }

    private function levelOrder(): string
    {
        $cases = [];
        foreach (self::LEVELS as $i => $level) {
            $cases[] = "when level = '{$level}' then {$i}";
        }

        return 'case '.implode(' ', $cases).' else 99 end';
    }

    /** @return array<string, string> */
    private function noStore(): array
    {
        return ['Cache-Control' => 'private, no-store'];
    }
}
