# Fast English — Coolify deployment (S9a)

Owner performs the Coolify deploy. This file lists names and purpose only;
no values, no secrets, no real payment data. All fixtures are labelled
TEST/FIXTURE and refuse production (S1/S3/S4/S5/S7 seeders throw when
`APP_ENV=production`; staging may run them).

Scope: sections 16 (private delivery), 19 (deploy/ops), 23 (AC-21, AC-22),
25 (launch inputs). S0–S8 accepted; S9a proves the production image locally.
The owner runs S9b (staging deploy + device run).

## 1. PostgreSQL 17 resource [untested — Coolify UI only]

- One PostgreSQL 17 service on the Coolify internal network.
- No public port. Only the application service reaches it via the internal
  hostname (e.g. `DB_HOST` = internal service name).
- Database, user, and password live in Coolify env (secret store), never in
  the repo. `DB_SSLMODE` defaults to `prefer`; keep SCRAM auth.
- Local emulation [tested]: `postgres:17` container on private Docker
  network `s9a-net`, no published port, `pg_dump 17.11` drill in S9a-7.

## 2. Application built from the Dockerfile [tested — local build]

- Coolify application type: Dockerfile at repository root (`Dockerfile`).
  `Dockerfile.pi` is unrelated and unchanged.
- Build stages (pinned exactly):
  - `node:22.23.2-bookworm-slim` builds Vite assets.
  - `php:8.4.26-cli-bookworm` runs
    `composer install --no-dev --optimize-autoloader`.
  - Runtime `php:8.4.26-fpm-bookworm` with `pdo_pgsql, gd, exif, intl,
    opcache`, plus `nginx`, `supervisord`, `postgresql-client-17`, `curl`.
- Build-time caches (dummy env, no secrets in image):
  `package:discover`, `config:cache`, `route:cache`, `view:cache`,
  `event:cache`, `filament:optimize`, `storage:link`.
  Verified: no `.env` in image, no `fe-secret` marker in cache.
- Runtime user is non-root `www-data`, port `8080`.
  Processes under supervisord: `nginx`, `php-fpm`,
  `php artisan schedule:work`, `php artisan queue:work`.
  No Redis/Octane/other queue backend (scope section 2).
- `HEALTHCHECK` calls `/up`. Migrations NEVER run on container start;
  release runs one explicit `php artisan migrate --force` after a fresh
  backup (RUNBOOK section 1 step 4).
- Local proof [tested]: image builds, runs as non-root, `/up` 200 minimal,
  forced error shows no trace, `ops:status` prints no secret.

## 3. Environment variables (names, purpose, secret?) [tested — local containers]

| Name | Purpose | Secret? |
|---|---|---|
| `APP_ENV` | `production` enables X-Accel delivery + refuses fixtures; `staging` may run labelled fixtures | no |
| `APP_DEBUG` | Must be `false` in production (no trace) | no |
| `APP_URL` | Owner HTTPS origin; canonical/storage URL generation | no |
| `APP_KEY` | Laravel encryption key (stable, separate backup) | YES |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE` | `en` stubs; UI is Persian | no |
| `BCRYPT_ROUNDS` | Hash cost (12 locally) | no |
| `LOG_CHANNEL`, `LOG_STACK` | `stderr` in production (never file) | no |
| `LOG_LEVEL` | `debug` dev, `warning`/`error` prod typical | no |
| `DB_CONNECTION` | `pgsql` only; no SQLite | no |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | Internal PG17 host/db/user; password secret | password YES, rest no |
| `SESSION_DRIVER` | `database` | no |
| `SESSION_LIFETIME`, `SESSION_ENCRYPT`, `SESSION_PATH`, `SESSION_DOMAIN` | Session behaviour | no |
| `SESSION_SECURE_COOKIE` | `true` in production (HTTPS-only cookie) | no |
| `SESSION_HTTP_ONLY`, `SESSION_SAME_SITE` | `true`/`lax` defaults | no |
| `FILESYSTEM_DISK` | `local` (private `storage/app/private`) | no |
| `QUEUE_CONNECTION` | `database` (no Redis) | no |
| `CACHE_STORE` | `database` | no |
| `MAIL_MAILER` | `smtp` when configured; else `log`/`array` (reset disabled in prod) | no |
| `MAIL_HOST`, `MAIL_PORT` | SMTP host/port | no |
| `MAIL_USERNAME`, `MAIL_PASSWORD` | SMTP credentials | YES |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Sender identity | no |
| `SALES_ENABLED` | Off by default; on only with real prices/destination/terms | no |
| `STAFF_2FA_ENFORCE`, `STAFF_2FA_IDLE_MINUTES` | Staff TOTP enforcement (on in prod) | no |
| `RELEASE_*` | Real APK metadata only when measured; else empty (download shows «هنوز منتشر نشده») | no |
| `SW_VERSION`, `TWA_PACKAGE_NAME`, `TWA_SHA256_FINGERPRINT` | PWA/TWA; fingerprint secret-adjacent, lives in Coolify env | fingerprint YES-ish, rest no |

Trusted proxies: code trusts `*` (`bootstrap/app.php`) so Laravel sees
HTTPS via `X-Forwarded-Proto` from Coolify's proxy. No env name needed.

Private delivery: `APP_ENV=production` serves audio/receipts via nginx
`X-Accel-Redirect` from internal `/_private/` backed by
`storage/app/private` after the Laravel policy check. External
`/_private/` returns 404. Dev/tests keep the PHP file response. Covers
serve only from the public disk (`/storage` → `storage/app/public`).

Password reset: when `MAIL_MAILER` is not `smtp` or `MAIL_HOST` is empty
in production, `POST /forgot-password` and `POST /reset-password`
short-circuit with the neutral Persian message, send nothing, and log no
token. Staging/dev keep the standard flow.

Logs: `stderr` only; never contain receipts, tokens, or secrets
(`ops:status` prints counts/ages/bytes only).

## 4. Persistent mounts [untested — Coolify UI only, paths tested locally]

- `storage/app/private` → audio (`lessons/*.mp3`) + receipts (`receipts/*`);
  private disk, never public. [tested: files survive restarts locally]
- `storage/app/public` → covers (`covers/*`); served via `/storage`.
  [tested]
- Logs go to `stderr` (Docker/Coolify log driver); no file mount needed.
  If a file log path is added later, mount it persistently and rotate.
  [tested: `LOG_CHANNEL=stderr` in local prod container]

## 5. Health check [tested]

- Coolify health check: `GET http://<host>:8080/up` expects 200.
  Body is minimal (status only, no version/env/secret/trace).
  Local proof: `curl -f http://127.0.0.1:8080/up` 200, no `APP_KEY`,
  `DB_PASSWORD`, version, or trace. Forced 500 page shows no stack.

## 6. Domain and HTTPS [untested — Coolify UI only]

- Coolify owns the domain, TLS certificate, and HTTPS redirect.
  `APP_URL` must match the public HTTPS origin.
  `SESSION_SECURE_COOKIE=true` requires this; Laravel sees HTTPS because
  the proxy is trusted. No cert files in the repo.

## 7. Backup and retention [tested — local drill, schedule untested on Coolify]

- Nightly (Coolify scheduled task or cron, [untested]): 
  `pg_dump -Fc` (17) to a mounted backup path + filesystem snapshot of
  `storage/app/private` + minimal config (names, not values).
- Retention (scheduler, [tested] locally via `schedule:list` + dry run):
  `receipts:retain-reviewed` daily (90-day reviewed/cancelled file delete,
  row kept; pending never touched) + `receipts:cleanup` daily (orphans).
  `schedule:work` and `queue:work` stay running under supervisord.
- Restore drill [tested locally]: `pg_restore` into empty PG17 DB, verify
  login (302→200), one audio byte size, one receipt row+file. Lab RPO/RTO
  recorded in the S9 plan entry (not a guarantee).
- Keep one encrypted copy off the VPS; rotate ≤30 days (proposal).
  After restore, run `receipts:retain-reviewed` before reopening.

## 8. Blocked launch inputs (scope section 25, by name — all untested)

Real prices + plan durations sign-off, bank destination
(card/holder/bank), approved terms text, approved privacy text, support
contact/channel, brand/logo/domain ownership, SMTP credentials, APK
signing identity/keystore + package-ID finalization, staging host,
hosting/VPS + backup destination, staff roster, teacher-approved
placement questions + cut scores, pilot content + producer, refund policy
sign-off, SLA/review-hours text.

No commercial placeholder ships in public sale; sales stay off until the
owner provides the inputs above.
