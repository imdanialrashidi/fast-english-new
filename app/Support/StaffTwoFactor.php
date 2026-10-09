<?php

namespace App\Support;

use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Google2FAQRCode\Google2FA as Google2FAQr;

/**
 * S8 staff TOTP (scope §5): staff accounts only, never students.
 *
 * - Secrets are generated with the same library Fortify uses
 *   (pragmarx/google2fa, already in vendor) and stored encrypted via the
 *   Eloquent `encrypted` cast on the User model.
 * - Recovery codes are shown ONCE (the confirm response) and only their
 *   SHA-256 hashes persist. Verification compares hashes with hash_equals;
 *   a used code's hash is removed so every code works at most once.
 * - Plain secrets and plain codes never enter logs or error messages.
 */
final class StaffTwoFactor
{
    public static function engine(): Google2FA
    {
        return new Google2FA;
    }

    public static function generateSecret(): string
    {
        return self::engine()->generateSecretKey();
    }

    public static function verify(string $secret, string $code): bool
    {
        $code = trim($code);

        if ($code === '' || ! ctype_digit($code)) {
            return false;
        }

        try {
            return self::engine()->verifyKey(
                $secret,
                $code,
                (int) config('staff2fa.window', 1)
            );
        } catch (\Throwable) {
            return false;
        }
    }

    public static function currentOtp(string $secret): string
    {
        return self::engine()->getCurrentOtp($secret);
    }

    /**
     * Inline QR image (data URI) for the setup page, plus the otpauth URL
     * as a manual-entry fallback. Returns null when no QR backend is
     * available — the otpauth URL still works.
     *
     * @return array{image: ?string, url: string}
     */
    public static function provisioning(string $email, string $secret): array
    {
        $qr = new Google2FAQr;
        $url = $qr->getQRCodeUrl('Fast English', $email, $secret);

        $image = null;
        try {
            $service = $qr->qrCodeServiceFactory();
            if ($service !== null) {
                $qr->setQRCodeService($service);
                $image = $qr->getQRCodeInline('Fast English', $email, $secret);
            }
        } catch (\Throwable) {
            $image = null;
        }

        return ['image' => $image, 'url' => $url];
    }

    /**
     * @return list<string> plain codes (show once, then discard)
     */
    public static function generateRecoveryCodes(): array
    {
        $count = max(1, (int) config('staff2fa.recovery_codes', 8));
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(substr(bin2hex(random_bytes(4)), 0, 4).'-'.substr(bin2hex(random_bytes(4)), 0, 4));
        }

        return $codes;
    }

    /**
     * @param  list<string>  $codes
     * @return list<string> SHA-256 hashes for storage
     */
    public static function hashCodes(array $codes): array
    {
        return array_values(array_map(
            fn (string $code): string => hash('sha256', strtoupper(trim($code))),
            $codes
        ));
    }

    /**
     * @param  list<string>  $hashes
     */
    public static function matchesCode(array $hashes, string $code): bool
    {
        $candidate = hash('sha256', strtoupper(trim($code)));
        foreach ($hashes as $hash) {
            if (hash_equals((string) $hash, $candidate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $hashes
     * @return list<string> hashes with the used code removed
     */
    public static function consumeCode(array $hashes, string $code): array
    {
        $candidate = hash('sha256', strtoupper(trim($code)));

        return array_values(array_filter(
            $hashes,
            fn ($hash): bool => ! hash_equals((string) $hash, $candidate)
        ));
    }
}
