<?php

namespace App\Http\Middleware;

use App\Http\Responses\NeutralPasswordResetLinkResponse;
use App\Support\SmtpStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * S9a production reset guard (scope sections 19, 25).
 *
 * When APP_ENV=production and SMTP is not configured (default mailer is
 * not smtp, or smtp host is empty), POST /forgot-password and
 * POST /reset-password short-circuit with the same neutral response as a
 * normal request: nothing is created, nothing is sent, and no token or
 * link is logged. GET forms still render so the disabled state is
 * visible; the POST never reaches Fortify's broker.
 *
 * In testing/local/staging this middleware does nothing, so the S8 reset
 * suite keeps its meaning.
 */
class DisablePasswordResetWhenSmtpMissing
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! SmtpStatus::isPasswordResetDisabled()) {
            return $next($request);
        }

        $route = $request->route();
        $name = $route ? (string) $route->getName() : '';

        if ($request->isMethod('post') && in_array($name, ['password.email', 'password.update'], true)) {
            $message = NeutralPasswordResetLinkResponse::MESSAGE;

            if ($request->wantsJson() || $request->expectsJson()) {
                return response()->json(['status' => $message], 200);
            }

            return redirect()->back()->with('status', $message);
        }

        return $next($request);
    }
}
