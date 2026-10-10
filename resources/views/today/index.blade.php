@extends('layouts.learner')

@section('title', 'امروز')

@section('content')
<div class="fe-measure fe-today">
    <p class="fe-display fe-today-eyebrow" lang="en" dir="ltr">TODAY</p>
    <p class="fe-greet"><x-fe-icon name="sun" size="20" />سلام، {{ $user->name }}</p>
    <h1>امروز هم قدمی بزرگ به سمت هدف‌هات برداشتی.</h1>

    @if ($continue !== null)
        @php
            $posSec = (int) floor((float) ($continue['position_seconds'] ?? 0));
            $durSec = (int) ($continue['duration_seconds'] ?? 0);
            $posLabel = ((int) floor($posSec / 60)).':'.str_pad((string) ($posSec % 60), 2, '0', STR_PAD_LEFT);
            $durLabel = ((int) floor($durSec / 60)).':'.str_pad((string) ($durSec % 60), 2, '0', STR_PAD_LEFT);
            $heroTitle = $continue['title_en'] ?? $continue['detail'];
            $heroLevel = $continue['level'] ?? '';
            $heroCover = $continue['cover_path'] ?? null;
            $heroMinutes = (int) ($continue['estimated_minutes'] ?? 0);
        @endphp
        <section class="fe-continue fe-today-hero" aria-label="ادامه یادگیری">
            <div class="fe-today-hero-top">
                @if (is_string($heroCover) && $heroCover !== '')
                    <img class="fe-today-cover" src="/storage/{{ $heroCover }}" alt="" width="88" height="88" loading="lazy">
                @else
                    <span class="fe-today-cover fe-today-cover-empty" aria-hidden="true"><x-fe-icon name="book-open" size="28" /></span>
                @endif
                <div class="fe-today-hero-head">
                    <p class="fe-today-kicker"><x-fe-icon name="play" size="16" />ادامه یادگیری</p>
                    <h2 lang="en" dir="ltr">{{ $heroTitle }}</h2>
                    @if ($heroLevel !== '')
                        <p class="fe-today-level"><span class="fe-chip" lang="en" dir="ltr">{{ $heroLevel }}</span></p>
                    @endif
                </div>
            </div>
            <p class="fe-muted fe-today-meta">سطح <span lang="en" dir="ltr">{{ $heroLevel }}</span> · ادامه از <span dir="ltr">{{ $posLabel }}</span> از <span dir="ltr">{{ $durLabel }}</span>@if ($heroMinutes > 0) · حدود {{ $heroMinutes }} دقیقه@endif</p>
            <p><a class="fe-btn fe-btn-primary" href="{{ $continue['url'] }}" wire:navigate><x-fe-icon name="play" size="20" />ادامه مطالعه</a></p>
        </section>
    @endif

    <section class="fe-section" aria-label="برنامه امروز">
        <h2 class="fe-section-title"><x-fe-icon name="file-text" size="20" />برنامه امروز</h2>
        @if (empty($plan['tasks']))
            <div class="fe-empty">
                <span class="fe-empty-icon"><x-fe-icon name="compass" size="24" /></span>
                <p class="fe-muted">هنوز برنامه‌ای نیست. یک مطلب انتخاب کن تا برنامه امروزت ساخته شود.</p>
                <p><a class="fe-btn fe-btn-primary" href="{{ route('app.home') }}" wire:navigate><x-fe-icon name="compass" size="20" />کشف مطالب</a></p>
            </div>
        @else
            <ul class="fe-divider-list">
                @foreach ($plan['tasks'] as $task)
                    <li class="fe-plan-row" data-done="{{ $task['done'] ? '1' : '0' }}">
                        <span class="fe-plan-check" aria-hidden="true">@if($task['done'])<x-fe-icon name="check" size="16" />@endif</span>
                        <span>
                            <a href="{{ $task['url'] }}" wire:navigate @if($task['done']) aria-label="{{ $task['title'] }} (انجام شده)" @endif>{{ $task['title'] }}</a>
                            @if (! empty($task['detail']))
                                <br><small class="fe-muted" dir="auto">{{ $task['detail'] }}</small>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="fe-goal" aria-label="هدف امروز">
        <div class="fe-ring" style="--fe-ring-pct: {{ $plan['progress_pct'] }}" role="img" aria-label="پیشرفت امروز: {{ $plan['progress_pct'] }} درصد">
            <span><span dir="ltr">{{ $plan['learned_minutes'] }}/{{ $plan['goal_minutes'] }}</span></span>
        </div>
        <div>
            <h2 class="fe-section-title">هدف امروز: {{ $plan['goal_minutes'] }} دقیقه مطالعه</h2>
            <p class="fe-muted">{{ $plan['learned_minutes'] }} دقیقه از برنامه امروز انجام شده.</p>
            @if ($plan['due_words'] > 0)
                <p><a class="fe-btn" href="{{ route('words.review') }}" wire:navigate><x-fe-icon name="languages" size="20" />مرور {{ $plan['due_words'] }} واژه</a></p>
            @endif
        </div>
    </section>

    @if ($plan['due_words'] > 0)
        <section class="fe-card" aria-label="واژه‌های امروز">
            <div class="fe-card-body">
                <h2 class="fe-section-title"><x-fe-icon name="languages" size="20" />{{ $plan['due_words'] }} واژه برای مرور امروز</h2>
                <p><a class="fe-btn fe-btn-primary" href="{{ route('words.review') }}" wire:navigate><x-fe-icon name="play" size="20" />شروع مرور</a></p>
            </div>
        </section>
    @endif

    @if (! empty($recommendations))
        <section aria-label="مطالب پیشنهادی">
            <h2 class="fe-section-title"><x-fe-icon name="compass" size="20" />مطالب پیشنهادی</h2>
            <ul class="fe-cards">
                @foreach ($recommendations as $topic)
                    <li>
                        @include('library._card', ['topic' => $topic])
                    </li>
                @endforeach
            </ul>
            <p><a class="fe-btn" href="{{ route('app.home') }}" wire:navigate>مشاهده همه</a></p>
        </section>
    @endif
</div>
@endsection
