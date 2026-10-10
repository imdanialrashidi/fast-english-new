<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Plan;
use App\Models\Topic;
use Illuminate\Http\Request;

/**
 * S8 landing (scope §6, PUB-01): the public introduction to Fast English.
 *
 * - The promise, a link to the real S1 sample, how it works, the plan
 *   list from the database, install/download links, and an FAQ link.
 * - Plan cards show names + durations only, never amounts; the 299000
 *   toman single-package note is the owner-approved commercial input
 *   (limited-time label, no countdown, no end date). Fixture amounts
 *   never appear on the public landing (no price claims from TEST).
 * - Hero preview stages the real sample (title, level, cover, first three
 *   sentences) with a link into /sample — no mock player, no
 *   fabricated testimonials, counts, ratings, or claims.
 * - Sales switch (config sales.enabled, off by default): when off, the
 *   plan list shows «در دست آماده‌سازی» and no purchase CTA is rendered.
 */
class LandingController extends Controller
{
    public function index(Request $request)
    {
        $plans = Plan::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get(['id', 'name_fa', 'duration_days']);

        $sample = Lesson::query()
            ->where('is_public_sample', true)
            ->where('status', 'published')
            ->whereHas('topic', fn ($topics) => $topics->where('status', 'published'))
            ->with('topic')
            ->orderBy('id')
            ->first();

        // A small editorial selection of representative lessons.
        $featured = Topic::query()
            ->where('status', 'published')
            ->whereHas('lessons', fn ($lessons) => $lessons->where('status', 'published'))
            ->with(['lessons' => fn ($lessons) => $lessons
                ->where('status', 'published')
                ->orderBy('id'),
            ])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        return response()->view('landing.index', [
            'plans' => $plans,
            'sample' => $sample,
            'sampleSentences' => $sample ? $this->previewSentences($sample->body_en) : [],
            'featured' => $featured,
            'salesOn' => (bool) config('sales.enabled', false),
        ]);
    }

    /**
     * Public sample entry (scope §6, /sample): no login, no premium leak.
     *
     * Finds the single published public sample (topic + lesson published
     * with is_public_sample=true) and redirects to its reader URL. Draft,
     * archived, or premium lessons never resolve here — 404 with the
     * neutral preparing message when no public sample exists.
     */
    public function sample(Request $request)
    {
        $sample = Lesson::query()
            ->where('is_public_sample', true)
            ->where('status', 'published')
            ->whereHas('topic', fn ($topics) => $topics->where('status', 'published'))
            ->orderBy('id')
            ->first();

        if ($sample === null) {
            abort(404);
        }

        return redirect()->to(route('reader.show', $sample->topic).'?level='.$sample->level);
    }

    /**
     * First three real sentences of the sample body for the hero
     * preview. Splits on sentence boundaries so lines end cleanly;
     * falls back to paragraphs. Real lesson text only — never
     * placeholder copy.
     *
     * @return list<string>
     */
    private function previewSentences(string $body): array
    {
        $chunks = preg_split('/(?<=[.!?])\s+/', $body) ?: [];
        $sentences = [];
        foreach ($chunks as $chunk) {
            $line = trim((string) $chunk);
            if ($line === '') {
                continue;
            }
            $sentences[] = $line;
            if (count($sentences) === 3) {
                break;
            }
        }
        if (count($sentences) < 3) {
            $paragraphs = preg_split('/\R{2,}|\R/', $body) ?: [];
            foreach ($paragraphs as $paragraph) {
                $line = trim((string) $paragraph);
                if ($line === '' || in_array($line, $sentences, true)) {
                    continue;
                }
                $sentences[] = mb_strimwidth($line, 0, 140, '…');
                if (count($sentences) === 3) {
                    break;
                }
            }
        }

        return $sentences;
    }
}
