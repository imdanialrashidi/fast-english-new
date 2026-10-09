<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * S8 staff boundary (scope §5): staff-only routes refuse everyone else
 * server-side. Students get 403 (never a redirect leak of staff content);
 * guests are sent to login. Suspended staff are refused like everyone
 * without access.
 */
class EnsureStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if ($user->disabled_at !== null || ! $user->is_staff) {
            abort(403);
        }

        return $next($request);
    }
}
