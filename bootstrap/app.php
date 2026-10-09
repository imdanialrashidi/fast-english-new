<?php

use App\Http\Middleware\DisablePasswordResetWhenSmtpMissing;
use App\Http\Middleware\EnsureStaff;
use App\Http\Middleware\PrivateNoStore;
use App\Http\Middleware\RejectDisabledUsers;
use App\Http\Middleware\ThrottleUploads;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // S9a: trust Coolify's TLS-terminating proxy so Laravel sees HTTPS
        // (SESSION_SECURE_COOKIE + APP_URL require it; scope section 19.1).
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'reject.disabled' => RejectDisabledUsers::class,
            // S8: staff-only routes (2FA setup/challenge, panel extras).
            'staff' => EnsureStaff::class,
        ]);
        // S2 scope §16: private HTML/Livewire responses are uncacheable.
        $middleware->appendToGroup('web', PrivateNoStore::class);
        // S3 pre-slice scope §16: five upload requests per minute per user.
        $middleware->appendToGroup('web', ThrottleUploads::class);
        // S9a: production without SMTP disables reset (neutral, no send/log).
        $middleware->appendToGroup('web', DisablePasswordResetWhenSmtpMissing::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
