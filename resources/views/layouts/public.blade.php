<!DOCTYPE html>
{{-- S8 public layout: Persian RTL marketing/trust surface (scope §6, §19).
     Uses learner tokens only (no new colors). Indexable pages provide a
     canonical URL; auth-adjacent users of this layout (reset forms) carry
     an explicit noindex meta on top of the noindex header. --}}
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
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="/icons/apple-touch-180.png">
    <meta name="description" content="@yield('meta_description', 'Fast English — خواندن و شنیدن متن‌های انگلیسی کوتاه در سطح مناسب')">
    @hasSection('noindex')
        <meta name="robots" content="noindex, nofollow">
    @endif
    @hasSection('canonical')
        <link rel="canonical" href="@yield('canonical')">
    @endif
    <title>@yield('title', 'Fast English')</title>
    @vite(['resources/css/app.css', 'resources/js/theme.js', 'resources/js/auth.js'])
</head>
<body class="fe-learner">
<a class="fe-skip" href="#fe-content">پرش به محتوا</a>
<header class="fe-topnav fe-topnav-public">
    <nav aria-label="ناوبری اصلی">
        <a class="fe-brand" href="{{ route('landing') }}" aria-label="Fast English"><x-fe-brand :height="32" /></a>
        <a class="fe-nav-link" href="{{ route('app.home') }}"><x-fe-icon name="compass" size="20" />مطالب</a>
        <a class="fe-nav-link" href="{{ route('download') }}"><x-fe-icon name="file-text" size="20" />دانلود</a>
        <a class="fe-nav-link" href="{{ route('trust.faq') }}"><x-fe-icon name="info" size="20" />پرسش‌ها</a>
        <a class="fe-nav-link" href="{{ route('login') }}"><x-fe-icon name="log-in" size="20" />ورود</a>
    </nav>
</header>
<main id="fe-content" class="fe-measure fe-public">
    @yield('content')
</main>
<footer class="fe-footer">
    <nav aria-label="پیوندهای اعتماد">
        <a href="{{ route('trust.about') }}">درباره</a>
        <a href="{{ route('trust.cooperation') }}">همکاری</a>
        <a href="{{ route('trust.support') }}">پشتیبانی</a>
        <a href="{{ route('trust.terms') }}">قوانین</a>
        <a href="{{ route('trust.privacy') }}">حریم خصوصی</a>
    </nav>
</footer>
</body>
</html>
