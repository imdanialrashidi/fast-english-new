@extends('layouts.learner')

@section('title', 'امروز')

@section('content')
<div class="fe-measure fe-today">
    <p class="fe-muted">👋 سلام، {{ $user->name }}</p>
    <h1>امروز هم قدمی بزرگ به سمت هدف‌هات برداشتی.</h1>

    @if ($continue !== null)
        <section class="fe-continue" aria-label="ادامه یادگیری">
            <h2 lang="en" dir="ltr">{{ $continue['detail'] }}</h2>
            <p class="fe-muted">ادامه بده — از همان‌جایی که مانده بودی.</p>
            <p><a class="fe-btn fe-btn-accent" href="{{ $continue['url'] }}" wire:navigate>ادامه مطالعه</a></p>
        </section>
    @endif

    <section class="fe-card" aria-label="برنامه امروز">
        <div class="fe-card-body">
            <h2 class="fe-section-title">برنامه امروز</h2>
            @if (empty($plan['tasks']))
                <div class="fe-empty">
                    <p class="fe-muted">هنوز برنامه‌ای نیست. یک مطلب انتخاب کن تا برنامه امروزت ساخته شود.</p>
                    <p><a class="fe-btn fe-btn-primary" href="{{ route('app.home') }}" wire:navigate>کشف مطالب</a></p>
                </div>
            @else
                <ul class="fe-plan">
                    @foreach ($plan['tasks'] as $task)
                        <li class="fe-plan-row" data-done="{{ $task['done'] ? '1' : '0' }}">
                            <span class="fe-plan-check" aria-hidden="true">@if($task['done']) ✓ @endif</span>
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
        </div>
    </section>

    <section class="fe-goal" aria-label="هدف امروز">
        <div class="fe-ring" style="--fe-ring-pct: {{ $plan['progress_pct'] }}" role="img" aria-label="پیشرفت امروز: {{ $plan['progress_pct'] }} درصد">
            <span>{{ $plan['learned_minutes'] }}/{{ $plan['goal_minutes'] }}</span>
        </div>
        <div>
            <h2 class="fe-section-title">هدف امروز: {{ $plan['goal_minutes'] }} دقیقه مطالعه</h2>
            <p class="fe-muted">{{ $plan['learned_minutes'] }} دقیقه از برنامه امروز انجام شده.</p>
            @if ($plan['due_words'] > 0)
                <p><a class="fe-btn" href="{{ route('words.review') }}" wire:navigate>مرور {{ $plan['due_words'] }} واژه</a></p>
            @endif
        </div>
    </section>

    @if ($plan['due_words'] > 0)
        <section class="fe-card" aria-label="واژه‌های امروز">
            <div class="fe-card-body">
                <h2 class="fe-section-title">{{ $plan['due_words'] }} واژه برای مرور امروز</h2>
                <p><a class="fe-btn fe-btn-primary" href="{{ route('words.review') }}" wire:navigate>شروع مرور</a></p>
            </div>
        </section>
    @endif

    @if (! empty($recommendations))
        <section aria-label="مطالب پیشنهادی">
            <h2 class="fe-section-title">مطالب پیشنهادی</h2>
            <ul class="fe-cards">
                @foreach ($recommendations as $topic)
                    <li>
                        @include('library._card', ['topic' => $topic])
                    </li>
                @endforeach
            </ul>
            <p><a class="fe-btn" href="{{ route('app.home') }}" wire:navigate>مشاهده همه ←</a></p>
        </section>
    @endif
</div>
@endsection
