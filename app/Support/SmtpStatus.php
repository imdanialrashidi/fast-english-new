<?php

namespace App\Support;

/**
 * S9a production guards (scope sections 16, 19.1, 25).
 *
 * - SMTP is "configured" only when the default mailer is smtp with a
 *   non-empty host. Anything else (log/array/null, empty host) means
 *   password reset must stay disabled in production so no token or link
 *   is ever created, sent, or logged.
 * - Password reset is disabled only in production without SMTP. Testing,
 *   local, and staging keep the standard Fortify flow (array/log mailers
 *   in tests, log mailer locally).
 */
class SmtpStatus
{
    public static function isConfigured(): bool
    {
        $default = (string) config('mail.default', '');

        if ($default !== 'smtp') {
            return false;
        }

        $host = (string) config('mail.mailers.smtp.host', '');

        return trim($host) !== '';
    }

    public static function isPasswordResetDisabled(): bool
    {
        return app()->environment('production') && ! self::isConfigured();
    }
}
