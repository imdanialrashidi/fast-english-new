<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

/**
 * S0: a suspended account (disabled_at set) must not reach authenticated
 * routes. The session is destroyed server-side and the visitor is sent back
 * to login with a generic error. Covers sessions created before suspension.
 */
class RejectDisabledUsers
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->disabled_at !== null) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                Fortify::username() => __('auth.failed'),
            ]);
        }

        return $next($request);
    }
}
