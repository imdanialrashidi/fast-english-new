# ADR-0001 — Assemble Laravel 13 + Fortify instead of the Livewire starter kit

Status: accepted (S0, 2026-10-08).
Scope: Fast English rebuild scaffold only; revisit per trigger below.

## Context

S0 needed a fresh Laravel scaffold with Blade + Livewire 4 + Fortify auth
(scope §2: "starter kit resmi Livewire با Fortify"). The official
`laravel/livewire-starter-kit` v1.0.1 requires `laravel/framework ^12.0`
(verified against Packagist metadata 2026-10-08), so it cannot install on
the Laravel 13.x baseline the scope pins for this rebuild.

## Forces

- Scope pins Laravel 13.x majors; majors never auto-change per session.
- Auth must be real (register/login/logout, session refresh, suspension via
  `disabled_at`) and proven by Pest tests on PostgreSQL (S0-2).
- Scaffold must stay minimal: no second frontend, no finance code in S0.

## Options considered

1. **Downgrade to Laravel 12 to use the starter kit.** Rejected: violates
   the Laravel 13 baseline and trades a known-good major for scaffolding
   convenience.
2. **Assemble Laravel 13 from `laravel/laravel` + `livewire/livewire` +
   `laravel/fortify` (selected).** Keeps the pinned major; auth behavior is
   owned by our `FortifyServiceProvider` + `CreateNewUser` and proven by
   S0 tests. Cost: we own the small wiring the kit would have provided.
3. **Wait for a Laravel-13-compatible kit release.** Rejected: blocks S0 on
   an external release with no date.

## Decision

S0 assembles the equivalent of the starter kit on Laravel 13 from Fortify
directly (register/login/logout views + `authenticateUsing` with the same
generic failure for bad credentials and suspended accounts, login
rate-limit 5/min per account+IP). No starter-kit package is installed;
`composer.json` requires `livewire/livewire ^4.4` and
`laravel/fortify ^1.41` explicitly.

## Consequences

- Auth surface is small and fully covered by `tests/Feature/S0AuthTest.php`
  and `S0SecurityTest.php`; any future kit feature (password reset, 2FA,
  passkeys) is added deliberately per slice, not inherited silently.
- `composer.json` `config.platform.php` is pinned to the resolved PHP
  8.4.26 patch; the PHP pin is recorded in the active exec plan.

## Rejected complexity

No kit-style extras (profile management, API tokens, teams, 2FA UI) were
added to "match" the kit. They arrive only with their owning slice.

## Revisit trigger

If the starter kit publishes a release supporting Laravel 13 AND the
project needs kit-owned UI beyond our slices (e.g. standardized 2FA
recovery flows), re-evaluate: adopt the kit only when its auth surface can
replace ours without changing S0-2/S0-3 behavior or weakening the §16
rate-limit/cookie posture. Record the outcome here before migrating.
