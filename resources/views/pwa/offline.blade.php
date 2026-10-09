<!DOCTYPE html>
{{-- S2 public offline page: fully self-contained (inline styles only) so it
     renders from the service-worker cache with no network at all. It is the
     ONLY navigation fallback: premium or account pages are never served
     offline (scope §18.1). --}}
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1D4ED8">
    <title>قطع اتصال — Fast English</title>
    <style>
        :root { --fe-canvas: #F8FAFC; --fe-surface: #FFFFFF; --fe-text: #0F172A; --fe-muted: #475569; --fe-primary: #1D4ED8; --fe-on-primary: #FFFFFF; }
        body { margin: 0; background: var(--fe-canvas); color: var(--fe-text); font-family: system-ui, Tahoma, sans-serif; }
        main { max-width: 40rem; margin: 0 auto; padding: 3rem 1.25rem; text-align: center; }
        .fe-card { background: var(--fe-surface); border: 1px solid #E2E8F0; border-radius: .75rem; padding: 2rem 1.5rem; }
        h1 { font-size: 1.25rem; margin: 0 0 .75rem; }
        p { color: var(--fe-muted); font-size: 1rem; line-height: 1.75; }
        .fe-actions { display: flex; gap: .75rem; justify-content: center; margin-top: 1.5rem; flex-wrap: wrap; }
        button, a.fe-link { min-height: 44px; min-width: 44px; font-size: 1rem; border-radius: .5rem; padding: .625rem 1.5rem; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
        button { background: var(--fe-primary); color: var(--fe-on-primary); border: none; }
        a.fe-link { background: transparent; color: var(--fe-primary); border: 1px solid var(--fe-primary); }
    </style>
</head>
<body>
<main>
    <div class="fe-card">
        <h1>اتصال اینترنت برقرار نیست</h1>
        <p id="fe-offline-message">برای ادامه به اینترنت وصل شوید</p>
        <div class="fe-actions">
            <button id="fe-offline-retry" type="button" onclick="window.location.reload()">تلاش دوباره</button>
            <a class="fe-link" href="/app">بازگشت به برنامه</a>
        </div>
    </div>
</main>
</body>
</html>
