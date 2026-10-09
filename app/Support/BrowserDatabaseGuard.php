<?php

namespace App\Support;

/**
 * S2 guard: the browser-lane database (`fe_browser`) is seeded once via
 * S2BrowserSeeder and must never be wiped by the Pest suite
 * (RefreshDatabase → migrate:fresh) or by a destructive artisan command.
 *
 * Both entry points funnel through here: tests/Pest.php (every Feature/Unit
 * test fails fast before touching the DB) and AppServiceProvider (destructive
 * console commands abort when the default connection points at fe_browser).
 */
final class BrowserDatabaseGuard
{
    /** @var list<string> */
    public const BROWSER_DATABASES = ['fe_browser'];

    /**
     * @throws \RuntimeException when $database is a browser-lane database.
     */
    public static function ensureNotBrowserDatabase(?string $database): void
    {
        if (in_array($database, self::BROWSER_DATABASES, true)) {
            throw new \RuntimeException(
                "Refusing to run against the browser-lane database [{$database}]: "
                .'Pest and migrate:fresh must never touch fe_browser.'
            );
        }
    }
}
