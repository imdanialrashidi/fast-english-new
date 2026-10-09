<!DOCTYPE html>
{{-- Editorial learner layout: Persian RTL chrome with a persistent lesson
     player. One HTMLAudioElement lives here under @persist('fe-player') so
     audio survives Livewire in-app navigation. Desktop (≥1024px) shows a
     restrained sidebar; mobile shows a four-destination bottom tab bar
     (امروز / کشف / واژه‌ها / حساب) with the player bar riding above it.
     Logout and admin are full navigations that stop and clear the audio
     (see reader-player.js). --}}
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#F7F5EF">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="apple-touch-icon" href="/icons/apple-touch-180.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title>@yield('title', config('app.name', 'Fast English'))</title>
    @vite(['resources/css/app.css', 'resources/js/reader-player.js', 'resources/js/pwa.js'])
    @livewireStyles
</head>
<body class="fe-learner">
<a class="fe-skip" href="#fe-content">پرش به محتوای درس</a>
@php
    $isToday = request()->routeIs('today.*');
    $isDiscover = request()->routeIs('app.home') || request()->routeIs('reader.*') || request()->routeIs('app.saved');
    $isWords = request()->routeIs('words.*');
    $isAccount = request()->routeIs('app.account') || request()->routeIs('account*') || request()->routeIs('subscribe.*') || request()->routeIs('payments.*') || request()->routeIs('placement.*');
@endphp
<div class="fe-shell">
    <aside class="fe-sidebar" aria-label="ناوبری اصلی">
        <p class="fe-sidebar-brand" lang="en" dir="ltr">Fast English</p>
        <a href="{{ route('today.index') }}" wire:navigate @if($isToday) aria-current="page" @endif>امروز</a>
        <a href="{{ route('app.home') }}" wire:navigate @if($isDiscover) aria-current="page" @endif>کشف مطالب</a>
        <a href="{{ route('words.index') }}" wire:navigate @if($isWords) aria-current="page" @endif>واژه‌ها</a>
        <a href="{{ route('app.account') }}" wire:navigate @if($isAccount) aria-current="page" @endif>حساب</a>
    </aside>
    <div>
        <header class="fe-topnav">
            <nav aria-label="ناوبری اصلی">
                <a class="fe-nav-link" href="{{ route('today.index') }}" wire:navigate @if($isToday) aria-current="page" @endif>امروز</a>
                <a class="fe-nav-link" href="{{ route('app.home') }}" wire:navigate @if($isDiscover) aria-current="page" @endif>کشف</a>
                <a class="fe-nav-link" href="{{ route('words.index') }}" wire:navigate @if($isWords) aria-current="page" @endif>واژه‌ها</a>
                <a class="fe-nav-link" href="{{ route('app.account') }}" wire:navigate @if($isAccount) aria-current="page" @endif>حساب</a>
            </nav>
        </header>
        <main id="fe-content" class="fe-main-pad">
            @yield('content')
        </main>
    </div>
</div>
<div class="fe-bottomnav">
    <nav aria-label="ناوبری اصلی">
        <a href="{{ route('today.index') }}" wire:navigate @if($isToday) aria-current="page" @endif>امروز</a>
        <a href="{{ route('app.home') }}" wire:navigate @if($isDiscover) aria-current="page" @endif>کشف</a>
        <a href="{{ route('words.index') }}" wire:navigate @if($isWords) aria-current="page" @endif>واژه‌ها</a>
        <a href="{{ route('app.account') }}" wire:navigate @if($isAccount) aria-current="page" @endif>حساب</a>
    </nav>
</div>

{{-- Persistent player: the single audio owner. The @persist directive keeps
     this element (and its playback) across Livewire navigations. --}}
<div @persist('fe-player')>
<section class="fe-playerbar" aria-label="پخش‌کننده صوت درس">
    <div class="fe-playerbar-inner">
        <p id="mini-player" class="fe-muted" role="status">پخش‌کننده آماده است</p>
        <audio id="lesson-audio" preload="metadata"></audio>
        <div class="fe-player-row">
            <button id="player-play" class="fe-btn fe-btn-primary" type="button">پخش</button>
            <button id="player-back" class="fe-btn" type="button">۱۰ ثانیه به عقب</button>
            <button id="player-forward" class="fe-btn" type="button">۱۰ ثانیه به جلو</button>
        </div>
        <div class="fe-player-row fe-seek-row">
            <span id="player-current" class="fe-time" dir="ltr">0:00</span>
            <input id="player-seek" class="fe-seek" type="range" min="0" max="100" step="0.1" value="0" aria-label="جست‌وجو در صوت درس">
            <span id="player-duration" class="fe-time" dir="ltr">–:––</span>
        </div>
        <div class="fe-player-row" role="group" aria-label="سرعت پخش">
            @foreach ([0.75, 1, 1.25, 1.5] as $speed)
                <button class="fe-speed-btn" type="button" data-speed="{{ $speed }}"
                        aria-pressed="{{ $speed == 1 ? 'true' : 'false' }}">{{ $speed }}×</button>
            @endforeach
        </div>
        <p id="player-status" class="fe-muted" role="status"></p>
        <p id="player-error" class="fe-alert" role="alert" hidden></p>
        <div class="fe-player-row">
            <button id="player-retry" class="fe-btn" type="button" hidden>تلاش دوباره</button>
        </div>
        <p id="progress-error" class="fe-alert" role="alert" hidden></p>
        <div class="fe-player-row">
            <button id="progress-retry" class="fe-btn" type="button" hidden>تلاش دوباره برای ذخیره</button>
        </div>
    </div>
</section>
</div>

@livewireScripts
</body>
</html>
