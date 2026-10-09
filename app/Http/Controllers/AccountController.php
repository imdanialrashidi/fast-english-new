<?php

namespace App\Http\Controllers;

use App\Models\PaymentRequest;
use Illuminate\Http\Request;

/**
 * S6 account page (scope §6, §17): profile + subscription + payment state.
 *
 * Shows the learner's current subscription window (when one exists) and
 * the open payment request (if any) with one clear CTA, without leaking
 * staff-only or private fields. Private, no-store (scope §16).
 */
class AccountController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user === null || $user->disabled_at !== null) {
            abort(403);
        }

        $openRequest = PaymentRequest::where('user_id', $user->id)
            ->whereIn('status', PaymentRequest::OPEN_STATUSES)
            ->orderByDesc('id')
            ->first();

        $latestRequest = PaymentRequest::where('user_id', $user->id)
            ->orderByDesc('id')
            ->first();

        $subscription = $user->subscription;

        return response()
            ->view('account', [
                'openRequest' => $openRequest,
                'latestRequest' => $latestRequest,
                'subscription' => $subscription,
            ])
            ->withHeaders($this->noStore());
    }

    /** @return array<string, string> */
    private function noStore(): array
    {
        return ['Cache-Control' => 'private, no-store'];
    }
}
