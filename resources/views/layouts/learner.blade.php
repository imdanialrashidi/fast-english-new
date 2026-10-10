<!DOCTYPE html>
{{-- Learner layout: Persian RTL chrome, icon-led navigation, studio player.
     One HTMLAudioElement lives here under @persist('fe-player') so audio
     survives Livewire in-app navigation. Mobile: brand bar + bottom tab
     bar (امروز / کشف / واژه‌ها / حساب) with the player riding above it.
     Desktop (≥1024px): icon sidebar; bottom tabs retire. Theme resolves
     before first paint from localStorage (fe-theme) or the OS. --}}
<html lang="fa" dir="rtl" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#f7f5ef">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>@yield('title', config('app.name', 'Fast English'))</title>
    @vite(['resources/css/app.css', 'resources/js/reader-player.js', 'resources/js/theme.js', 'resources/js/pwa.js'])
    @livewireStyles
</head>
<body class="fe-learner">
<a class="fe-skip" href="#fe-content">پرش به محتوای درس</a>
@php
    $isToday = request()->routeIs('today.*');
    $isDiscover = request()->routeIs('app.home') || request()->routeIs('reader.*') || request()->routeIs('app.saved');
    $isWords = request()->routeIs('words.*');
    $isAccount = request()->routeIs('app.account') || request()->routeIs('account*') || request()->routeIs('subscribe.*') || request()->routeIs('payments.*') || request()->routeIs('placement.*');
    $sectionLabel = $isToday ? 'امروز' : ($isWords ? 'واژه‌ها' : ($isAccount ? 'حساب' : 'کشف مطالب'));
@endphp
<div class="fe-shell">
    <aside class="fe-sidebar" aria-label="ناوبری اصلی">
        <p class="fe-sidebar-brand"><x-fe-brand /></p>
        <a href="{{ route('today.index') }}" wire:navigate @if($isToday) aria-current="page" @endif><x-fe-icon name="home" size="20" />امروز</a>
        <a href="{{ route('app.home') }}" wire:navigate @if($isDiscover) aria-current="page" @endif><x-fe-icon name="compass" size="20" />کشف مطالب</a>
        <a href="{{ route('words.index') }}" wire:navigate @if($isWords) aria-current="page" @endif><x-fe-icon name="languages" size="20" />واژه‌ها</a>
        <a href="{{ route('app.account') }}" wire:navigate @if($isAccount) aria-current="page" @endif><x-fe-icon name="user" size="20" />حساب</a>
    </aside>
    <div>
        <header class="fe-brandbar">
            <a class="fe-brand" href="{{ route('today.index') }}" wire:navigate aria-label="Fast English — امروز"><x-fe-brand /></a>
            <span class="fe-brand-sub">{{ $sectionLabel }}</span>
        </header>
        <header class="fe-topnav">
            <nav aria-label="ناوبری اصلی">
                <a class="fe-nav-link" href="{{ route('today.index') }}" wire:navigate @if($isToday) aria-current="page" @endif><x-fe-icon name="home" size="20" />امروز</a>
                <a class="fe-nav-link" href="{{ route('app.home') }}" wire:navigate @if($isDiscover) aria-current="page" @endif><x-fe-icon name="compass" size="20" />کشف</a>
                <a class="fe-nav-link" href="{{ route('words.index') }}" wire:navigate @if($isWords) aria-current="page" @endif><x-fe-icon name="languages" size="20" />واژه‌ها</a>
                <a class="fe-nav-link" href="{{ route('app.account') }}" wire:navigate @if($isAccount) aria-current="page" @endif><x-fe-icon name="user" size="20" />حساب</a>
            </nav>
        </header>
        <main id="fe-content" class="fe-main-pad">
            @yield('content')
        </main>
    </div>
</div>
<div class="fe-bottomnav">
    <nav aria-label="ناوبری اصلی">
        <a href="{{ route('today.index') }}" wire:navigate @if($isToday) aria-current="page" @endif><x-fe-icon name="home" size="20" />امروز</a>
        <a href="{{ route('app.home') }}" wire:navigate @if($isDiscover) aria-current="page" @endif><x-fe-icon name="compass" size="20" />کشف</a>
        <a href="{{ route('words.index') }}" wire:navigate @if($isWords) aria-current="page" @endif><x-fe-icon name="languages" size="20" />واژه‌ها</a>
        <a href="{{ route('app.account') }}" wire:navigate @if($isAccount) aria-current="page" @endif><x-fe-icon name="user" size="20" />حساب</a>
    </nav>
</div>

{{-- Persistent player: the single audio owner. The @persist directive keeps
     this element (and its playback) across Livewire navigations. The bar
     rests collapsed (play · lesson · speed · expand) and opens into the
     full seek/skip console on demand; errors auto-expand so they stay
     visible. --}}
<div @persist('fe-player')>
<section class="fe-playerbar" data-expanded="false" aria-label="پخش‌کننده صوت درس">
    <div class="fe-playerbar-inner">
        <div class="fe-player-progress" aria-hidden="true"><span id="player-progress-fill"></span></div>
        <div class="fe-player-main">
            <button id="player-play" class="fe-play" type="button" aria-label="پخش" disabled><span id="player-play-icon"><x-fe-icon name="play" size="28" /></span></button>
            <img id="player-thumb" class="fe-player-thumb" src="/icons/icon-192.png" alt="" width="40" height="40" loading="lazy">
            <div class="fe-player-meta">
                <p id="mini-player" class="fe-player-title" role="status">پخش‌کننده آماده است</p>
                <p class="fe-player-compact-time" dir="ltr"><span id="player-current">0:00</span> / <span id="player-duration">–:––</span></p>
            </div>
            <button id="player-speed" class="fe-speed-cycle" type="button" aria-label="سرعت پخش: 1×"><x-fe-icon name="gauge" size="16" /><span id="player-speed-label" dir="ltr">1×</span></button>
            <button id="player-toggle" class="fe-expand-btn" type="button" aria-expanded="false" aria-controls="player-detail" aria-label="نمایش کنترل‌های بیشتر"><x-fe-icon name="chevron-down" size="20" /></button>
        </div>
        <div class="fe-player-detail" id="player-detail">
            <input id="player-seek" class="fe-seek" type="range" min="0" max="100" step="0.1" value="0" aria-label="جست‌وجو در صوت درس">
            <div class="fe-player-controls">
                <button id="player-back" class="fe-skip-btn" type="button" aria-label="۱۰ ثانیه به عقب"><x-fe-icon name="rotate-ccw" size="20" /><span dir="ltr">10</span></button>
                <button id="player-forward" class="fe-skip-btn" type="button" aria-label="۱۰ ثانیه به جلو"><x-fe-icon name="rotate-cw" size="20" /><span dir="ltr">10</span></button>
            </div>
            <audio id="lesson-audio" preload="metadata"></audio>
            <p id="player-status" class="fe-muted" role="status"></p>
            <p id="player-error" class="fe-alert" role="alert" hidden><x-fe-icon name="triangle-alert" size="20" /><span id="player-error-text"></span></p>
            <div class="fe-player-row">
                <button id="player-retry" class="fe-btn" type="button" hidden><x-fe-icon name="rotate-cw" size="20" />تلاش دوباره</button>
            </div>
            <p id="progress-error" class="fe-alert" role="alert" hidden><x-fe-icon name="triangle-alert" size="20" /><span id="progress-error-text"></span></p>
            <div class="fe-player-row">
                <button id="progress-retry" class="fe-btn" type="button" hidden><x-fe-icon name="rotate-cw" size="20" />تلاش دوباره برای ذخیره</button>
            </div>
        </div>
    </div>
</section>
</div>

@livewireScripts
</body>
</html>
