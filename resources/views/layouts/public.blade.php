<!DOCTYPE html>
{{-- S8 public layout: Persian RTL marketing/trust surface (scope §6, §19).
     Uses learner tokens only (no new colors). Indexable pages provide a
     canonical URL; auth-adjacent users of this layout (reset forms) carry
     an explicit noindex meta on top of the noindex header. --}}
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#F7F5EF">
    <meta name="description" content="@yield('meta_description', 'Fast English — خواندن و شنیدن متن‌های انگلیسی کوتاه در سطح مناسب')">
    @hasSection('noindex')
        <meta name="robots" content="noindex, nofollow">
    @endif
    @hasSection('canonical')
        <link rel="canonical" href="@yield('canonical')">
    @endif
    <title>@yield('title', 'Fast English')</title>
    @vite(['resources/css/app.css'])
</head>
<body class="fe-learner">
<a class="fe-skip" href="#fe-content">پرش به محتوا</a>
<header class="fe-topnav">
    <nav aria-label="ناوبری اصلی">
        <a class="fe-nav-link" href="{{ route('landing') }}" lang="en" dir="ltr" style="font-weight:700">Fast English</a>
        <a class="fe-nav-link" href="{{ route('app.home') }}">مطالب</a>
        <a class="fe-nav-link" href="{{ route('download') }}">دانلود</a>
        <a class="fe-nav-link" href="{{ route('trust.faq') }}">پرسش‌ها</a>
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
