<?php

namespace App\Http\Controllers;

use App\Actions\PublishLesson;
use App\Models\Category;
use App\Models\Topic;
use Illuminate\Http\Request;

/**
 * S3 topic library (scope §17.2): one card per topic.
 *
 * Browse state (level, category, query, page) lives in the URL query string,
 * so reload, back/forward, and sharing preserve the filter state. Only
 * published topics with at least one published lesson ever appear; drafts,
 * archived lessons, and lesson-less topics are excluded at the query level.
 * The response carries metadata only — never bodies, glossaries, or
 * private paths (the S1 reader policy still guards the full content).
 */
class TopicLibraryController extends Controller
{
    public function index(Request $request)
    {
        $level = strtoupper((string) $request->query('level', ''));
        $level = in_array($level, PublishLesson::LEVELS, true) ? $level : null;

        $categorySlug = trim((string) $request->query('category', ''));
        $categorySlug = $categorySlug !== '' ? $categorySlug : null;

        $query = trim((string) $request->query('q', ''));
        $query = $query !== '' ? mb_substr($query, 0, 120) : null;

        $topics = Topic::query()
            ->where('topics.status', 'published')
            ->whereHas('lessons', fn ($lessons) => $lessons->where('status', 'published'))
            ->when($level !== null, fn ($q) => $q->whereHas(
                'lessons',
                fn ($lessons) => $lessons->where('status', 'published')->where('level', $level)
            ))
            ->when($categorySlug !== null, fn ($q) => $q->whereHas(
                'category',
                fn ($categories) => $categories->where('slug', $categorySlug)
            ))
            ->when($query !== null, fn ($q) => $q->where(
                'topics.title_en', 'ilike', '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query).'%'
            ))
            ->with([
                'category',
                'lessons' => fn ($lessons) => $lessons
                    ->where('status', 'published')
                    ->orderByRaw($this->levelOrder())
                    ->select(['id', 'topic_id', 'level', 'estimated_minutes']),
            ])
            ->orderByDesc('topics.published_at')
            ->orderByDesc('topics.id')
            ->paginate(12)
            ->withQueryString();

        // One small query for the category dropdown; constant regardless of
        // the topic count, so the page stays at a fixed query budget.
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name_fa')
            ->select(['id', 'name_fa', 'slug'])
            ->get();

        return response()
            ->view('library.index', [
                'topics' => $topics,
                'categories' => $categories,
                'levels' => PublishLesson::LEVELS,
                'activeLevel' => $level,
                'activeCategory' => $categorySlug,
                'activeQuery' => $query,
                'hasFilters' => $level !== null || $categorySlug !== null || $query !== null,
            ])
            ->withHeaders($this->noStore());
    }

    private function levelOrder(): string
    {
        $cases = [];
        foreach (PublishLesson::LEVELS as $i => $level) {
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
