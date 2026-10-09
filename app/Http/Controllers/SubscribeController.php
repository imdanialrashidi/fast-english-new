<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

/**
 * S5 subscribe page (scope §6, §17): the student plan list.
 *
 * Reuses the S4 learner layout, card styling, and top navigation. Each
 * active plan shows its Persian name and its integer-toman price rendered
 * with Persian digits (App\Support\Toman). Inactive plans never appear.
 * The response is private, no-store (scope §16).
 *
 * S8 sales switch (scope §25): when sales are off, the page shows
 * «در دست آماده‌سازی» with no purchase forms; creation is refused
 * server-side in PaymentRequestController as well.
 */
class SubscribeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user === null || $user->disabled_at !== null) {
            abort(403);
        }

        $salesOn = (bool) config('sales.enabled', false);

        $plans = $salesOn
            ? Plan::query()
                ->where('is_active', true)
                ->orderBy('display_order')
                ->orderBy('id')
                ->get()
            : collect();

        return response()
            ->view('subscribe.index', ['plans' => $plans, 'salesOn' => $salesOn])
            ->withHeaders($this->noStore());
    }

    /** @return array<string, string> */
    private function noStore(): array
    {
        return ['Cache-Control' => 'private, no-store'];
    }
}
