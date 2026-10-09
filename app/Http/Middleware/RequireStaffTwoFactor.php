<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * S8 staff session gate (scope §5, §16): TOTP enforcement + idle timeout
 * for staff sessions. Runs in the Filament panel auth stack after
 * Authenticate (so guests never reach it there) and is also safe
 * standalone.
 *
 * - Non-staff or suspended accounts: 403 (defense in depth; Filament's
 *   own panel check already refuses them with the same status).
 * - Idle timeout (recorded: 30 minutes): a staff session idle longer
 *   than staff2fa.idle_minutes is logged out server-side and sent to
 *   login. Activity is refreshed on every passing staff request.
 * - Enforcement (staff2fa.enforce, ON in production): staff without a
 *   CONFIRMED TOTP secret cannot reach the panel — redirect to setup.
 *   An unconfirmed secret (unfinished setup) grants nothing.
 * - Challenge: staff WITH confirmed 2FA must hold a fresh
 *   `staff_2fa_passed_at` session flag (set at confirm time and at every
 *   successful challenge, expired by the same idle window); otherwise
 *   they are sent to the challenge page. Students never hold the flag:
 *   the challenge route itself is staff-only.
 */
class RequireStaffTwoFactor
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

        $idleSeconds = max(1, (int) config('staff2fa.idle_minutes', 30)) * 60;
        $now = time();

        $lastActivity = $request->session()->get('staff_last_activity');
        if (is_int($lastActivity) && ($now - $lastActivity) > $idleSeconds) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'نشست به‌دلیل عدم فعالیت پایان یافت. لطفاً دوباره وارد شوید.');
        }

        $request->session()->put('staff_last_activity', $now);

        if ($user->two_factor_confirmed_at === null) {
            if ((bool) config('staff2fa.enforce', false) === true) {
                return redirect()->route('staff.twofactor.setup');
            }

            return $next($request);
        }

        $passedAt = $request->session()->get('staff_2fa_passed_at');
        if (! is_int($passedAt) || ($now - $passedAt) > $idleSeconds) {
            return redirect()->guest(route('staff.twofactor.challenge'));
        }

        return $next($request);
    }
}
