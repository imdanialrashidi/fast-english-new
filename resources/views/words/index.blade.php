@extends('layouts.learner')

@section('title', 'واژه‌ها')

@section('content')
<div class="fe-measure fe-library">
    <h1>واژه‌ها</h1>
    <p class="fe-muted">دفترچه واژه‌های خودت — از درس‌ها ذخیره کن، سر موعد مرور کن.</p>

    <nav aria-label="وضعیت واژه‌ها">
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            <a class="fe-level-link" href="{{ route('words.index', ['tab' => 'all']) }}" @if($tab === 'all') aria-current="page" @endif>همه</a>
            <a class="fe-level-link" href="{{ route('words.index', ['tab' => 'due']) }}" @if($tab === 'due') aria-current="page" @endif>موعد امروز ({{ $dueCount }})</a>
            <a class="fe-level-link" href="{{ route('words.index', ['tab' => 'learning']) }}" @if($tab === 'learning') aria-current="page" @endif>در حال یادگیری ({{ $learningCount }})</a>
            <a class="fe-level-link" href="{{ route('words.index', ['tab' => 'known']) }}" @if($tab === 'known') aria-current="page" @endif>می‌دانم ({{ $knownCount }})</a>
        </div>
    </nav>

    <form class="fe-filters" method="get" action="{{ route('words.index') }}" role="search" aria-label="جست‌وجو در واژه‌ها">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="fe-field fe-field-search">
            <label for="words-q">جست‌وجو در واژه‌ها</label>
            <input id="words-q" name="q" type="search" value="{{ $activeQuery }}" autocomplete="off">
        </div>
        <div class="fe-field fe-field-actions">
            <button class="fe-btn fe-btn-primary" type="submit">جست‌وجو</button>
        </div>
    </form>

    @if ($dueCount > 0)
        <p><a class="fe-btn fe-btn-primary" href="{{ route('words.review') }}" wire:navigate>شروع مرور ({{ $dueCount }} واژه)</a></p>
    @endif

    @if ($words->isEmpty())
        <div class="fe-empty">
            @if ($tab === 'due')
                <p>واژه‌ای برای مرور امروز نیست. 🎉</p>
            @elseif ($tab === 'known')
                <p>هنوز واژه‌ای را «می‌دانم» نکرده‌ای.</p>
            @else
                <p>هنوز واژه‌ای ذخیره نکرده‌ای. از صفحه هر درس، واژه‌های کلیدی را ذخیره کن.</p>
                <p><a class="fe-btn" href="{{ route('app.home') }}" wire:navigate>کشف مطالب</a></p>
            @endif
        </div>
    @else
        <ul class="fe-plan">
            @foreach ($words as $entry)
                <li class="fe-vocab-row" data-word-id="{{ $entry->id }}">
                    <span style="flex:1">
                        <span class="fe-vocab-word" lang="en" dir="ltr">{{ $entry->word }}</span>
                        @if ($entry->meaning_fa)
                            <br><small dir="auto">{{ $entry->meaning_fa }}</small>
                        @endif
                        @if ($entry->lesson !== null && $entry->topic !== null)
                            <br><small class="fe-muted"><a href="{{ route('reader.show', ['topic' => $entry->topic->slug, 'level' => $entry->lesson->level]) }}" wire:navigate>دیدن در متن اصلی ({{ $entry->lesson->level }})</a></small>
                        @endif
                    </span>
                    <button class="fe-btn fe-word-remove" type="button" data-word-id="{{ $entry->id }}" aria-label="حذف {{ $entry->word }}">حذف</button>
                </li>
            @endforeach
        </ul>
        <p id="words-status" class="fe-muted" role="status"></p>

        @if ($words->hasPages())
            <nav class="fe-pagination" aria-label="صفحه‌های واژه‌ها">
                {{ $words->links() }}
            </nav>
        @endif
    @endif
</div>
@endsection
