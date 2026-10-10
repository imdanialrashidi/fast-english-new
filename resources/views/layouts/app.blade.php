<!DOCTYPE html>
{{-- Guest/auth shell: same modern hybrid product language as the learner app.
     A quiet brand panel beside the form — never a generic centered card
     on a gradient. Theme resolves before first paint, same as learner. --}}
<html lang="fa" dir="rtl" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f7f5ef">
    <script>
        (function () {
            try {
                var raw = (localStorage.getItem('fe-theme') || 'system').toLowerCase();
                var theme = raw === 'light' ? 'light' : raw === 'dark' ? 'dark'
                    : (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-theme', theme);
            } catch (e) {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="/icons/apple-touch-180.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title>{{ config('app.name', 'Fast English') }}</title>
    @vite(['resources/css/app.css', 'resources/js/theme.js', 'resources/js/auth.js', 'resources/js/pwa.js'])
    @livewireStyles
</head>
<body class="fe-learner">
<a class="fe-skip" href="#fe-content">پرش به فرم</a>
<main id="fe-content">
    @yield('content')
</main>
@livewireScripts
</body>
</html>
