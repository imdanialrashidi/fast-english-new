@extends('layouts.learner')

@section('title', 'واژه‌ها')

@section('content')
<div class="fe-measure fe-words">
    <div class="fe-words-head">
        <p class="fe-display fe-words-eyebrow" lang="en" dir="ltr">Words</p>
        <h1>واژه‌ها</h1>
        <p class="fe-muted">دفترچه واژه‌های خودت — از درس‌ها ذخیره کن، سر موعد مرور کن.</p>
    </div>

    <nav aria-label="وضعیت واژه‌ها">
        <div class="fe-pills" role="group" aria-label="وضعیت واژه‌ها">
            <a class="fe-level-link" href="{{ route('words.index', array_filter(['tab' => 'all', 'q' => $activeQuery])) }}" @if($tab === 'all') aria-current="page" @endif>همه</a>
            <a class="fe-level-link" href="{{ route('words.index', array_filter(['tab' => 'due', 'q' => $activeQuery])) }}" @if($tab === 'due') aria-current="page" @endif>موعد امروز ({{ $dueCount }})</a>
            <a class="fe-level-link" href="{{ route('words.index', array_filter(['tab' => 'learning', 'q' => $activeQuery])) }}" @if($tab === 'learning') aria-current="page" @endif>در حال یادگیری ({{ $learningCount }})</a>
            <a class="fe-level-link" href="{{ route('words.index', array_filter(['tab' => 'known', 'q' => $activeQuery])) }}" @if($tab === 'known') aria-current="page" @endif>می‌دانم ({{ $knownCount }})</a>
        </div>
    </nav>

    <form class="fe-filters fe-words-filters" method="get" action="{{ route('words.index') }}" role="search" aria-label="جست‌وجو در واژه‌ها">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="fe-field fe-field-search">
            <label for="words-q"><x-fe-icon name="search" size="16" />جست‌وجو در واژه‌ها</label>
            <div class="fe-search-wrap">
                <input id="words-q" name="q" type="search" value="{{ $activeQuery }}" autocomplete="off">
                <button class="fe-btn fe-btn-primary" type="submit"><x-fe-icon name="search" size="20" />جست‌وجو</button>
            </div>
        </div>
        @if ($activeQuery !== null && $activeQuery !== '')
            <p class="fe-filters-reset"><a class="fe-btn" href="{{ route('words.index', ['tab' => $tab]) }}"><x-fe-icon name="x" size="20" />پاک کردن جست‌وجو</a></p>
        @endif
    </form>

    @if ($dueCount > 0)
        <p class="fe-words-cta"><a class="fe-btn fe-btn-primary" href="{{ route('words.review') }}" wire:navigate><x-fe-icon name="play" size="20" />شروع مرور ({{ $dueCount }} واژه)</a></p>
    @elseif ($learningCount > 0)
        <p class="fe-muted" role="status">واژه‌ای برای مرور امروز نیست. فردا دوباره سر بزن.</p>
    @endif

    @if ($words->isEmpty())
        <div class="fe-empty" role="status">
            <span class="fe-empty-icon"><x-fe-icon name="inbox" size="24" /></span>
            @if ($activeQuery !== null && $activeQuery !== '')
                <p>برای «{{ $activeQuery }}» چیزی پیدا نشد.</p>
                <p class="fe-muted">واژه دیگری را امتحان کن یا جست‌وجو را پاک کن.</p>
                <p><a class="fe-btn fe-btn-primary" href="{{ route('words.index', ['tab' => $tab]) }}"><x-fe-icon name="x" size="20" />پاک کردن جست‌وجو</a></p>
            @elseif ($tab === 'due')
                <p>واژه‌ای برای مرور امروز نیست.</p>
                <p class="fe-muted">فردا دوباره سر بزن.</p>
            @elseif ($tab === 'known')
                <p>هنوز واژه‌ای را «می‌دانم» نکرده‌ای.</p>
                <p class="fe-muted">از تب «در حال یادگیری» یک واژه را «می‌دانم» کن.</p>
            @else
                <p>هنوز واژه‌ای ذخیره نکرده‌ای. از صفحه هر درس، واژه‌های کلیدی را ذخیره کن.</p>
                <p><a class="fe-btn fe-btn-primary" href="{{ route('app.home') }}" wire:navigate><x-fe-icon name="compass" size="20" />کشف مطالب</a></p>
            @endif
        </div>
    @else
        <ul class="fe-divider-list">
            @foreach ($words as $entry)
                <li class="fe-vocab-row" data-word-id="{{ $entry->id }}">
                    <div class="fe-vocab-main">
                        <p class="fe-vocab-line">
                            <span class="fe-vocab-word" lang="en" dir="ltr">{{ $entry->word }}</span>
                            @if ($entry->status === 'known')
                                <span class="fe-chip"><x-fe-icon name="check" size="16" />می‌دانم</span>
                            @elseif ($entry->is_due)
                                <span class="fe-due"><span class="fe-due-dot" aria-hidden="true"></span>موعد امروز</span>
                            @else
                                <span class="fe-muted fe-vocab-state">در حال یادگیری</span>
                            @endif
                        </p>
                        @if ($entry->meaning_fa)
                            <p class="fe-vocab-meaning" dir="auto">{{ $entry->meaning_fa }}</p>
                        @endif
                        @if ($entry->example_en)
                            <p class="fe-vocab-example" lang="en" dir="ltr">{{ $entry->example_en }}</p>
                        @endif
                        @if ($entry->lesson !== null && $entry->topic !== null)
                            <p class="fe-vocab-meta"><a href="{{ route('reader.show', ['topic' => $entry->topic->slug, 'level' => $entry->lesson->level]) }}" wire:navigate><x-fe-icon name="book-open" size="16" />دیدن در متن اصلی ({{ $entry->lesson->level }})</a></p>
                        @endif
                    </div>
                    <div class="fe-vocab-actions">
                        <button class="fe-btn fe-btn-quiet fe-word-edit" type="button" data-word-id="{{ $entry->id }}" aria-expanded="false" aria-controls="words-edit-{{ $entry->id }}"><x-fe-icon name="pencil" size="20" />ویرایش</button>
                        @if ($entry->status === 'learning')
                            <button class="fe-btn fe-btn-quiet fe-word-known" type="button" data-word-id="{{ $entry->id }}"><x-fe-icon name="check" size="20" />می‌دانم شد</button>
                        @else
                            <button class="fe-btn fe-btn-quiet fe-word-known" type="button" data-word-id="{{ $entry->id }}" data-target-status="learning"><x-fe-icon name="rotate-ccw" size="20" />برگردان به یادگیری</button>
                        @endif
                        <button class="fe-btn fe-btn-quiet fe-word-remove" type="button" data-word-id="{{ $entry->id }}" aria-label="حذف {{ $entry->word }}"><x-fe-icon name="x" size="20" />حذف</button>
                    </div>
                    <form class="fe-word-form" id="words-edit-{{ $entry->id }}" data-word-id="{{ $entry->id }}" hidden>
                        <div class="fe-field">
                            <label for="words-meaning-{{ $entry->id }}">معنی فارسی</label>
                            <input id="words-meaning-{{ $entry->id }}" name="meaning_fa" type="text" dir="auto" maxlength="500" value="{{ $entry->meaning_fa }}" autocomplete="off">
                        </div>
                        <div class="fe-field">
                            <label for="words-example-{{ $entry->id }}"><span lang="en" dir="ltr">Example</span> — جمله نمونه انگلیسی</label>
                            <input id="words-example-{{ $entry->id }}" name="example_en" type="text" lang="en" dir="ltr" maxlength="500" value="{{ $entry->example_en }}" autocomplete="off">
                        </div>
                        <div class="fe-word-form-actions">
                            <button class="fe-btn fe-btn-primary fe-word-save" type="submit"><x-fe-icon name="check" size="20" />ذخیره تغییرات</button>
                        </div>
                        <p class="fe-word-form-status fe-muted" role="status"></p>
                    </form>
                </li>
            @endforeach
        </ul>
        <p id="words-status" class="fe-muted" role="status"></p>

        @if ($words->hasPages())
            <nav class="fe-pagination" aria-label="صفحه‌های واژه‌ها">
                {{ $words->links('pagination.fe') }}
            </nav>
        @endif
    @endif
</div>
@endsection
