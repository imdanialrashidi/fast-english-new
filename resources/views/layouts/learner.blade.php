<!DOCTYPE html>
{{-- S4 learner layout: Persian RTL chrome with a persistent lesson player.
     One HTMLAudioElement lives here under @persist('fe-player') so audio
     survives Livewire in-app navigation between library, reader, saved,
     and account pages. Logout and admin are full navigations that stop
     and clear the audio (see reader-player.js). S1 controls, speeds,
     ±10s skip, and the no-autoplay rule are unchanged. --}}
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#1D4ED8">
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
<header class="fe-topnav">
    <nav aria-label="ناوبری اصلی">
        <a class="fe-nav-link" href="{{ route('app.home') }}" wire:navigate>مطالب</a>
        <a class="fe-nav-link" href="{{ route('app.saved') }}" wire:navigate>ذخیره‌شده‌ها</a>
        <a class="fe-nav-link" href="{{ route('app.account') }}" wire:navigate>حساب</a>
    </nav>
</header>
<main id="fe-content" class="fe-main-pad">
    @yield('content')
</main>

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
