<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\Plan;
use Illuminate\Http\Request;

/**
 * S8 landing (scope §6, PUB-01): short Persian page.
 *
 * - The promise, a link to the real S1 sample, how it works, the plan
 *   list from the database, install/download links, and an FAQ link.
 * - All Persian copy is DRAFT (labelled in the page: DRAFT — OWNER
 *   REVIEW REQUIRED). No claims about results or prices: plan cards show
 *   names + durations only, never amounts.
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

        return response()->view('landing.index', [
            'plans' => $plans,
            'sample' => $sample,
            'salesOn' => (bool) config('sales.enabled', false),
        ]);
    }
}
