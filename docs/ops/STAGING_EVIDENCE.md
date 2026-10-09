# Staging Evidence — قالب شواهد استیجینگ (S9b, owner fills in)

Owner fills every TODO after running `STAGING_CHECKLIST.md` + `DEVICE_MATRIX.md`.
The agent pre-fills no result. Placeholders only — no secrets, no real values.

مالک پس از اجرای `STAGING_CHECKLIST.md` و `DEVICE_MATRIX.md` همه TODOها را پر می‌کند.
ایجنت هیچ نتیجه‌ای را پیش‌پر نمی‌کند. فقط placeholder — بدون secret و مقدار واقعی.

## Run identity / مشخصات اجرا

- Commit SHA: `TODO`
- Image tag: `TODO` (e.g. `<IMAGE_TAG>`)
- Dockerfile sha256: `TODO`
- Host: `TODO` (`<COOLIFY_HOST>`)
- Domain: `TODO` (`<STAGING_DOMAIN>`)
- Staging URL: `TODO` (`<STAGING_URL>`)
- Date (UTC): `TODO` (`<DATE>`)
- Executed by: `TODO` (`<OWNER_NAME>`)

## A. Deploy / استقرار

| Step | Expected | Result | Notes |
|---|---|---|---|
| A1 PostgreSQL 17 healthy, no public port | healthy, `pg_dump` 17.x | TODO (PASS/FAIL) | TODO |
| A2 App from `Dockerfile` at recorded commit, user `www-data`, port `8080` | build stages match | TODO | TODO |
| A3 Env (`staging`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `APP_URL=<STAGING_URL>`, `SALES_ENABLED=false`, `stderr` logs) | `about` staging/Debug OFF, `ops:status` secret-free | TODO | TODO |
| A4 Mounts (`storage/app/private`, `storage/app/public` survive restart) | files survive restart | TODO | TODO |
| A5 Domain + HTTPS (`http` → `https`, `Secure` cookie) | redirect + Secure | TODO | TODO |
| A6 Health `GET /up` | `200` minimal, no secret/version/trace | TODO | TODO |
| A7 `schedule:work` + `queue:work` alive; `schedule:list` has both retention jobs | both present | TODO | TODO |
| A8 Backup task runs on demand, exit 0 | artifacts produced | TODO | TODO |

## B. Host verification / راستی‌آزمایی هاست

| Step | Expected | Result | Notes |
|---|---|---|---|
| B1 `php artisan migrate --force` after backup | exit 0, all `Ran` | TODO | TODO |
| B2 Staging seeders (`S1`/`S3`/`S5`/`S7`) exit 0; refuse on `production` | 0 on staging | TODO | TODO |
| B3 Audio: `200` + `Accept-Ranges`, `206` + exact `Content-Range`, `HEAD 200`, `416` + `bytes */<SIZE>` | all four hold | TODO | size `<SIZE>` = TODO |
| B4 `GET /_private/...` | `404` | TODO | TODO |
| B5 Receipt owner `200` + no-store; other student `403`; guest redirect; no path in HTML | all hold | TODO | TODO |
| B6 Reset neutral message identical for known/unknown, 0 new tokens, nothing sent/logged | identical + 0 | TODO | TODO |
| B7 Sales off: `در دست آماده‌سازی` + creation refused, nothing stored | holds | TODO | TODO |
| B8 `GET /up` repeat | `200` minimal | TODO | TODO |

## C. Backup + restore / بکاپ + restore

- Dump file: `TODO` (`<DUMP_FILE>`), bytes: `TODO`
- Private-files snapshot: `TODO`, bytes: `TODO`
- Source counts: users `TODO`, lessons `TODO`, payment_requests `TODO`
- Restore DB: `TODO` (`<EMPTY_DB>`)
- Restored counts match source: TODO (PASS/FAIL)
- Copy login `302 → 200`: TODO
- Copy audio `200`, bytes: `TODO` (expected `<SIZE>`)
- Copy receipt `200` (+ file bytes `TODO`) or `404` after retention (state which): TODO
- `receipts:retain-reviewed --dry-run` (pending never listed): TODO

## D. Devices / دستگاه‌ها

| Row | Device | OS | Browser/app | Build | Date | (a) relaunch | (b) audio | (c) logout-clear | (d) back-clean | Overall |
|---|---|---|---|---|---|---|---|---|---|---|
| 1 Android APK | TODO | TODO | TODO | APK | TODO | TODO | TODO | TODO | TODO | TODO |
| 2 Android browser/PWA | TODO | TODO | TODO | TODO | TODO | TODO | TODO | TODO | TODO | TODO |
| 3 iPhone Safari/PWA | TODO | TODO | TODO | TODO | TODO | TODO | TODO | TODO | TODO | TODO |

- Digital Asset Links (Google endpoint + staging file): TODO (PASS/FAIL), notes: TODO
- APK signing: package `TODO` (`<TWA_PACKAGE>`), fingerprint recorded in plan (not here): TODO (yes/no), `/download` metadata (version/code/date/size/sha256) verified: TODO
- Any verification failure / toolbar behavior: TODO (none/describe)

## E. Owner sign-off / تأیید مالک

- S9b-1 staging deploy: TODO (UNPROVEN/PASS/FAIL)
- S9b-2 HTTPS + Digital Asset Links: TODO
- S9b-3 device matrix (3 required rows): TODO
- S9b-4 installed PWA session + audio: TODO
- S9b-5 APK login + audio + logout: TODO
- S9b-6 backup job + restore into empty DB: TODO
- Remaining issues / مشکلات باقی‌مانده: TODO (none/describe)
- Signature + date: TODO
