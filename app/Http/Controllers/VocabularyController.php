<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\VocabularyWord;
use App\Support\SpacedRepetition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * R4 vocabulary notebook (VOCAB-01): save lesson words, review with
 * Again/Hard/Good/Easy, track due/learning/known, remove. Every mutation
 * is owner-scoped server-side (VocabularyWordPolicy); a student can never
 * inspect or modify another student's words.
 *
 * Saving reuses the lesson glossary when a matching entry exists (same
 * meaning/example prefilled); manual notes are the student's own, never
 * presented as authoritative translations.
 */
class VocabularyController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user === null || $user->disabled_at !== null) {
            abort(403);
        }

        $tab = (string) $request->query('tab', 'all');
        $tab = in_array($tab, ['all', 'due', 'learning', 'known'], true) ? $tab : 'all';
        $query = trim((string) $request->query('q', ''));
        $query = $query !== '' ? mb_substr($query, 0, 120) : null;

        $words = VocabularyWord::query()
            ->where('user_id', $user->id)
            ->with(['lesson', 'topic'])
            ->when($tab === 'due', fn ($q) => $q
                ->where('status', 'learning')
                ->where('due_at', '<=', now()))
            ->when($tab === 'learning', fn ($q) => $q->where('status', 'learning'))
            ->when($tab === 'known', fn ($q) => $q->where('status', 'known'))
            ->when($query !== null, fn ($q) => $q->where('word', 'ilike', '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query).'%'))
            ->orderByRaw("case when status = 'learning' and due_at <= now() then 0 else 1 end")
            ->orderBy('due_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $dueCount = VocabularyWord::where('user_id', $user->id)
            ->where('status', 'learning')
            ->where('due_at', '<=', now())
            ->count();
        $learningCount = VocabularyWord::where('user_id', $user->id)
            ->where('status', 'learning')
            ->count();
        $knownCount = VocabularyWord::where('user_id', $user->id)
            ->where('status', 'known')
            ->count();

        return response()
            ->view('words.index', [
                'words' => $words,
                'tab' => $tab,
                'activeQuery' => $query,
                'dueCount' => $dueCount,
                'learningCount' => $learningCount,
                'knownCount' => $knownCount,
            ])
            ->withHeaders($this->noStore());
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if ($user === null || $user->disabled_at !== null) {
            abort(403);
        }

        $data = $request->validate([
            'word' => ['required', 'string', 'max:120'],
            'meaning_fa' => ['nullable', 'string', 'max:500'],
            'example_en' => ['nullable', 'string', 'max:500'],
            'lesson_id' => ['nullable', 'integer', 'exists:lessons,id'],
        ]);

        $word = trim($data['word']);
        if ($word === '') {
            return response()->json(['message' => 'واژه نمی‌تواند خالی باشد.'], 422)
                ->withHeaders($this->noStore());
        }

        $lesson = null;
        if (! empty($data['lesson_id'])) {
            $lesson = Lesson::with('topic')->findOrFail($data['lesson_id']);
            if ($lesson->status !== 'published' || $lesson->topic->status !== 'published') {
                abort(404);
            }
            if (Gate::allows('view', $lesson) !== true) {
                abort(403);
            }
        }

        $meaning = isset($data['meaning_fa']) ? trim((string) $data['meaning_fa']) : '';
        $example = isset($data['example_en']) ? trim((string) $data['example_en']) : '';

        // Reuse the lesson glossary when a matching entry exists.
        if ($lesson !== null && is_array($lesson->glossary)) {
            foreach ($lesson->glossary as $entry) {
                if (mb_strtolower(trim((string) ($entry['word'] ?? ''))) === mb_strtolower($word)) {
                    if ($meaning === '') {
                        $meaning = trim((string) ($entry['meaning_fa'] ?? ''));
                    }
                    if ($example === '' && ! empty($entry['example_en'])) {
                        $example = trim((string) $entry['example_en']);
                    }
                    break;
                }
            }
        }

        $existing = VocabularyWord::where('user_id', $user->id)
            ->where('word_key', mb_strtolower($word))
            ->first();
        if ($existing !== null) {
            return response()->json([
                'message' => 'این واژه قبلاً در دفترچه شما ذخیره شده است.',
                'id' => $existing->id,
            ], 422)->withHeaders($this->noStore());
        }

        $initial = SpacedRepetition::initial(now());

        $entry = VocabularyWord::create([
            'user_id' => $user->id,
            'word' => mb_substr($word, 0, 120),
            'meaning_fa' => $meaning !== '' ? mb_substr($meaning, 0, 500) : null,
            'example_en' => $example !== '' ? mb_substr($example, 0, 500) : null,
            'lesson_id' => $lesson?->id,
            'topic_id' => $lesson?->topic_id,
            'status' => 'learning',
            'ease_factor' => $initial['ease_factor'],
            'interval_days' => $initial['interval_days'],
            'repetitions' => $initial['repetitions'],
            'lapses' => $initial['lapses'],
            'due_at' => $initial['due_at'],
            'last_reviewed_at' => null,
        ]);

        return response()->json([
            'id' => $entry->id,
            'word' => $entry->word,
            'due_at' => $entry->due_at?->toIso8601String(),
        ], 201)->withHeaders($this->noStore());
    }

    public function update(Request $request, VocabularyWord $word)
    {
        $user = $request->user();
        if ($user === null || Gate::allows('update', $word) !== true) {
            abort(403);
        }

        $data = $request->validate([
            'meaning_fa' => ['nullable', 'string', 'max:500'],
            'example_en' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'in:learning,known'],
        ]);

        $updates = [];
        if (array_key_exists('meaning_fa', $data)) {
            $meaning = trim((string) $data['meaning_fa']);
            $updates['meaning_fa'] = $meaning !== '' ? $meaning : null;
        }
        if (array_key_exists('example_en', $data)) {
            $example = trim((string) $data['example_en']);
            $updates['example_en'] = $example !== '' ? $example : null;
        }
        if (! empty($data['status'])) {
            $updates['status'] = $data['status'];
            if ($data['status'] === 'learning' && $word->last_reviewed_at === null) {
                $updates['due_at'] = now();
            }
        }

        $word->forceFill($updates)->save();

        return response()->json([
            'id' => $word->id,
            'status' => $word->status,
            'meaning_fa' => $word->meaning_fa,
            'example_en' => $word->example_en,
        ])->withHeaders($this->noStore());
    }

    public function destroy(Request $request, VocabularyWord $word)
    {
        $user = $request->user();
        if ($user === null || Gate::allows('delete', $word) !== true) {
            abort(403);
        }

        $word->delete();

        return response()->json(['deleted' => true])->withHeaders($this->noStore());
    }

    public function review(Request $request)
    {
        $user = $request->user();
        if ($user === null || $user->disabled_at !== null) {
            abort(403);
        }

        $remaining = VocabularyWord::where('user_id', $user->id)
            ->where('status', 'learning')
            ->where('due_at', '<=', now())
            ->count();

        $card = VocabularyWord::where('user_id', $user->id)
            ->where('status', 'learning')
            ->where('due_at', '<=', now())
            ->with(['lesson', 'topic'])
            ->orderBy('due_at')
            ->orderBy('id')
            ->first();

        return response()
            ->view('words.review', [
                'card' => $card,
                'remaining' => $remaining,
                'grades' => SpacedRepetition::GRADES,
            ])
            ->withHeaders($this->noStore());
    }

    public function grade(Request $request, VocabularyWord $word)
    {
        $user = $request->user();
        if ($user === null || Gate::allows('update', $word) !== true) {
            abort(403);
        }

        $data = $request->validate([
            'grade' => ['required', 'in:again,hard,good,easy'],
        ]);

        if ($word->status !== 'learning') {
            return response()->json(['message' => 'این واژه در برنامه مرور نیست.'], 422)
                ->withHeaders($this->noStore());
        }

        $next = SpacedRepetition::schedule([
            'ease_factor' => (float) $word->ease_factor,
            'interval_days' => (int) $word->interval_days,
            'repetitions' => (int) $word->repetitions,
            'lapses' => (int) $word->lapses,
        ], $data['grade'], now());

        $word->forceFill([
            'ease_factor' => $next['ease_factor'],
            'interval_days' => $next['interval_days'],
            'repetitions' => $next['repetitions'],
            'lapses' => $next['lapses'],
            'due_at' => $next['due_at'],
            'last_reviewed_at' => $next['last_reviewed_at'],
        ])->save();

        $remaining = VocabularyWord::where('user_id', $user->id)
            ->where('status', 'learning')
            ->where('due_at', '<=', now())
            ->count();

        return response()->json([
            'id' => $word->id,
            'due_at' => $word->due_at?->toIso8601String(),
            'interval_days' => $word->interval_days,
            'repetitions' => $word->repetitions,
            'remaining' => $remaining,
        ])->withHeaders($this->noStore());
    }

    /** @return array<string, string> */
    private function noStore(): array
    {
        return ['Cache-Control' => 'private, no-store'];
    }
}
