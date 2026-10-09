<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use Illuminate\Http\Request;

/**
 * S4 saved page (SAVE-01): the user's bookmarked topics, rendered with the
 * same card component as the library. Only published topics with at least
 * one published lesson appear — archived topics vanish from the list while
 * the bookmark row persists (see BookmarkController proposal).
 *
 * A bookmark never grants access: opening a premium topic still goes
 * through the S1 reader policy (403 for ineligible readers).
 */
class SavedTopicsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user === null || $user->disabled_at !== null) {
            abort(403);
        }

        $topics = Topic::query()
            ->where('topics.status', 'published')
            ->whereHas('lessons', fn ($lessons) => $lessons->where('status', 'published'))
            ->whereHas('bookmarks', fn ($bookmarks) => $bookmarks->where('user_id', $user->id))
            ->with([
                'lessons' => fn ($lessons) => $lessons
                    ->where('status', 'published')
                    ->select(['id', 'topic_id', 'level', 'estimated_minutes']),
            ])
            ->orderByDesc('topics.published_at')
            ->orderByDesc('topics.id')
            ->paginate(12)
            ->withQueryString();

        return response()
            ->view('saved.index', ['topics' => $topics])
            ->withHeaders($this->noStore());
    }

    /** @return array<string, string> */
    private function noStore(): array
    {
        return ['Cache-Control' => 'private, no-store'];
    }
}
