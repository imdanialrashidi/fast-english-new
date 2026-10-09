# Device Matrix — ماتریس دستگاه (S9b, owner-run)

Owner runs on real devices. The agent does not touch devices, signing
keys, or remote hosts. No secrets or real values in this file — fill
results in the rows below and copy the summary into `STAGING_EVIDENCE.md`.

مالک روی دستگاه‌های واقعی اجرا می‌کند. ایجنت به دستگاه، کلید امضا یا
هاست ریموت دست نمی‌زند. بدون secret و مقدار واقعی — نتایج را در ردیف‌های
زیر پر کنید و خلاصه را در `STAGING_EVIDENCE.md` کپی کنید.

Scope: scope §18 (PWA/APK), AC-19/20. Minimum pilot matrix: one real
Android with the release APK, one Android browser/PWA, one iPhone
Safari/PWA (§18.2). Missing device → UNPROVEN, never passed.

## APK signing note / یادداشت امضای APK

- The test keystore must live OUTSIDE the repository with an independent
  backup and a named owner (scope §18.2). Never commit keystore files,
  passwords, or fingerprints to Git.
- فایل keystore آزمایشی باید خارج از مخزن با بکاپ مستقل و مسئول مشخص
  باشد. هرگز فایل keystore، رمز یا fingerprint را در Git commit نکنید.
- Record the test keystore SHA-256 fingerprint in the exec plan (not in
  this file, not in Git) once the owner generates it outside the repo.
- Fingerprint تست را پس از تولید خارج از مخزن، در exec plan ثبت کنید
  (نه در این فایل، نه در Git).
- `TWA_PACKAGE_NAME` proposal: `com.fastenglishpodcast.app` (finalize
  before the first release). The staging `assetlinks.json` must match the
  real release package + fingerprint.
- Release APK metadata (versionName/versionCode/date/size/SHA-256) comes
  only from the measured signed build on the `/download` page.

## Digital Asset Links check / بررسی Digital Asset Links

Run from the owner's machine (not from the agent, not from the device
browser). از ماشین مالک اجرا شود (نه ایجنت، نه مرورگر دستگاه).

- Command / دستور:
  `curl -s https://digitalassetlinks.googleapis.com/v1/statements:list?source.web.site=<STAGING_URL>&relation=delegate_permission%2Fcommon.handle_all_urls | head -c 2000`
- Also fetch the staging file directly / همچنین فایل استیجینگ:
  `curl -s <STAGING_URL>/.well-known/assetlinks.json`
- Expected / نتیجه موردانتظار: the Google endpoint lists the staging
  statement with the exact `package_name` (`<TWA_PACKAGE>`) and the exact
  `sha256_cert_fingerprints` (`<TWA_FINGERPRINT>`); the staging file
  serves the same JSON with `Content-Type: application/json`.
- Fail action / اقدام خرابی: fix `TWA_PACKAGE_NAME` /
  `TWA_SHA256_FINGERPRINT` in Coolify env, confirm
  `<STAGING_URL>/.well-known/assetlinks.json` serves the pair, wait for
  propagation, and re-query the Google endpoint. Document any
  verification failure / toolbar behavior in `STAGING_EVIDENCE.md`.
  مقادیر را در Coolify اصلاح، فایل را تأیید، منتظر انتشار بمانید و دوباره
  کوئری کنید. هر خطای verification و رفتار toolbar را مستند کنید.

## How to run each row / روش اجرای هر ردیف

1. Install (PWA via browser install prompt, or APK via the versioned file
   from `<STAGING_URL>/download` after verifying its SHA-256) — or open
   the browser row directly at `<STAGING_URL>/app`.
   نصب (PWA از مرورگر، یا APK از فایل نسخه‌دار `/download` پس از تطبیق
   SHA-256) — یا ردیف مرورگر را مستقیم در `<STAGING_URL>/app` باز کنید.
2. Log in as staging user 1, play the sample audio, background/relaunch
   the app, confirm the session persists and audio plays.
   با کاربر ۱ وارد شوید، صوت نمونه را پخش کنید، اپ را پس‌زمینه/بازگشایی
   کنید، ماندن session و پخش صوت را تأیید کنید.
3. Log out (real POST logout, not just closing). Confirm the player stops
   and the page no longer shows account data. Then log in as staging
   user 2, press back/forward, and confirm no user-1 data appears.
   logout واقعی کنید. توقف player و پاک‌شدن صفحه را تأیید کنید. سپس با
   کاربر ۲ وارد شوید، back/forward بزنید و مطمئن شوید داده کاربر ۱ نیست.
4. Record date + PASS/FAIL + notes in the row. تاریخ + نتیجه + یادداشت را
   در ردیف ثبت کنید.

Expected checks per row (all four must hold):
بررسی‌های موردانتظار هر ردیف (هر چهار مورد باید برقرار باشند):
- (a) login persists after relaunch / ماندن login پس از بازگشایی
- (b) audio plays / پخش صوت
- (c) logout clears the page (player stopped, no account data) /
  پاک‌شدن صفحه پس از logout (توقف player، بدون داده حساب)
- (d) back navigation shows no previous account's data /
  نبود داده حساب قبلی در back navigation

| # | Device model / مدل دستگاه | OS version / نسخه OS | Browser or installed app / مرورگر یا اپ نصب‌شده | Build type / نوع بیلد | Date / تاریخ | Result + notes / نتیجه + یادداشت |
|---|---|---|---|---|---|---|
| 1 | `<ANDROID_MODEL_APK>` (e.g. owner Pixel/Samsung model) | `<ANDROID_OS_APK>` | Installed app from `<STAGING_URL>/download` (TWA/APK) | APK (release-signed test build) | `<DATE>` | TODO — (a) TODO / (b) TODO / (c) TODO / (d) TODO |
| 2 | `<ANDROID_MODEL_BROWSER>` | `<ANDROID_OS_BROWSER>` | `<ANDROID_BROWSER>` (Chrome/Firefox, installed PWA if tested) | Installed PWA or browser (state which) | `<DATE>` | TODO — (a) TODO / (b) TODO / (c) TODO / (d) TODO |
| 3 | `<IPHONE_MODEL>` | `<IOS_VERSION>` | Safari (installed PWA if tested) | Installed PWA or Safari (state which) | `<DATE>` | TODO — (a) TODO / (b) TODO / (c) TODO / (d) TODO |

Required rows / ردیف‌های الزامی: row 1 = one Android with the APK; row 2 =
one Android browser or PWA; row 3 = one iPhone Safari or installed PWA.
ردیف ۱ = یک Android با APK؛ ردیف ۲ = یک Android مرورگر یا PWA؛ ردیف ۳ =
یک iPhone سافاری یا PWA نصب‌شده.

Add extra rows below for repeats; never delete a required row.
ردیف‌های تکراری را زیر اضافه کنید؛ ردیف الزامی را حذف نکنید.
