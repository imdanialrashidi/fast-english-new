<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * S2 privacy boundary (scope §16): account, admin, and Livewire HTML
 * responses — and every Livewire update response — are private and
 * uncacheable. Media responses set the same header in their own
 * controller. Public fingerprinted assets are untouched (non-HTML).
 *
 * Index discipline (scope §19.1; S8-4): non-production HTML carries
 * X-Robots-Tag noindex so a staging host is never indexed. In every
 * environment — including production — the private surfaces stay out of
 * indexes: /app, /admin, /login + register + password routes, /media,
 * and /staff. Only the public marketing pages (/, trust pages,
 * /download) are indexable, with canonical tags + sitemap. Robots and
 * noindex are index discipline, not a security control.
 */
class PrivateNoStore
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $contentType = (string) $response->headers->get('Content-Type', '');
        $isHtml = str_contains($contentType, 'text/html');

        if ($isHtml || $this->isLivewireUpdate($request)) {
            $response->headers->set('Cache-Control', 'private, no-store');
        }

        if ($isHtml && ! app()->environment('production')) {
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        } elseif ($isHtml && self::isPrivateSurface($request)) {
            // S8-4: these paths are noindex even in production.
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }

    /**
     * S8-4: app, admin, login/auth, media, and staff surfaces are never
     * indexed. Public marketing pages are matched by exclusion.
     */
    public static function isPrivateSurface(Request $request): bool
    {
        $path = '/'.trim($request->path(), '/');

        foreach (['/app', '/admin', '/login', '/register', '/forgot-password', '/reset-password', '/media', '/staff', '/account'] as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Livewire 4 serves its XHR endpoint under a hashed prefix
     * (livewire-XXXXXXXX/update), so match the route name and the URI
     * shape instead of a fixed path.
     */
    private function isLivewireUpdate(Request $request): bool
    {
        $route = $request->route();
        $name = $route ? (string) $route->getName() : '';

        if (str_starts_with($name, 'livewire') || str_starts_with($name, 'default-livewire')) {
            return true;
        }

        return (bool) preg_match('#^livewire-[^/]+/(update|upload-file|preview-file)#', trim($request->path(), '/'));
    }
}
