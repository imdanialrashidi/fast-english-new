@extends('layouts.learner')

@section('title', 'کشف مطالب')

@section('content')
<div class="fe-measure fe-library">
    <div class="fe-library-head fe-discover-head">
        <p class="fe-display fe-discover-eyebrow" lang="en" dir="ltr">Discover</p>
        <h1>کشف مطالب</h1>
        <p class="fe-muted">یک مطلب کوتاه در سطح خودت پیدا کن — هر موضوع یک‌بار، با سطح‌های موجودش.</p>
    </div>

    <form class="fe-filters fe-discover-filters" method="get" action="{{ route('app.home') }}" role="search" aria-label="جست‌وجو و فیلتر مطالب">
        <div class="fe-field fe-field-search">
            <label for="filter-q"><x-fe-icon name="search" size="16" />جست‌وجو در عنوان</label>
            <div class="fe-search-wrap">
                <input id="filter-q" name="q" type="search" value="{{ $activeQuery }}" placeholder="مثلاً park" autocomplete="off">
                <button class="fe-btn fe-btn-primary" type="submit"><x-fe-icon name="search" size="20" />جست‌وجو</button>
            </div>
        </div>

        <div class="fe-filter-group">
            <p class="fe-filter-label" id="filter-level-label">سطح</p>
            <div class="fe-pills" role="group" aria-labelledby="filter-level-label">
                <a class="fe-level-link" href="{{ route('app.home', array_filter(['category' => $activeCategory, 'q' => $activeQuery])) }}" @if ($activeLevel === null) aria-current="page" @endif>همه</a>
                @foreach ($levels as $level)
                    <a class="fe-level-link" lang="en" dir="ltr" href="{{ route('app.home', array_filter(['level' => $level, 'category' => $activeCategory, 'q' => $activeQuery])) }}" @if ($activeLevel === $level) aria-current="page" @endif>{{ $level }}</a>
                @endforeach
            </div>
        </div>

        <div class="fe-filter-group">
            <p class="fe-filter-label" id="filter-category-label">دسته</p>
            <div class="fe-pills" role="group" aria-labelledby="filter-category-label">
                <a class="fe-level-link" href="{{ route('app.home', array_filter(['level' => $activeLevel, 'q' => $activeQuery])) }}" @if ($activeCategory === null) aria-current="page" @endif>همه دسته‌ها</a>
                @foreach ($categories as $category)
                    <a class="fe-level-link" href="{{ route('app.home', array_filter(['level' => $activeLevel, 'category' => $category->slug, 'q' => $activeQuery])) }}" @if ($activeCategory === $category->slug) aria-current="page" @endif>{{ $category->name_fa }}</a>
                @endforeach
            </div>
        </div>

        {{-- Selects mirror the pill state and submit the same GET params with the search; kept for filter proofs. --}}
        <div class="fe-field fe-visually-hidden" aria-hidden="true">
            <label for="filter-level">سطح</label>
            <select id="filter-level" name="level" tabindex="-1">
                <option value="">همه سطح‌ها</option>
                @foreach ($levels as $level)
                    <option value="{{ $level }}" @selected($activeLevel === $level)>{{ $level }}</option>
                @endforeach
            </select>
        </div>
        <div class="fe-field fe-visually-hidden" aria-hidden="true">
            <label for="filter-category">دسته</label>
            <select id="filter-category" name="category" tabindex="-1">
                <option value="">همه دسته‌ها</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->slug }}" @selected($activeCategory === $category->slug)>{{ $category->name_fa }}</option>
                @endforeach
            </select>
        </div>

        @if ($hasFilters)
            <p class="fe-filters-reset"><a class="fe-btn" href="{{ route('app.home') }}"><x-fe-icon name="x" size="20" />پاک کردن فیلترها</a></p>
        @endif
    </form>

    <div class="fe-toolbar">
        <p class="fe-muted" role="status">{{ $topics->total() }} مطلب</p>
        <p><a class="fe-btn" href="{{ route('app.saved') }}" wire:navigate><x-fe-icon name="bookmark" size="20" />ذخیره‌شده‌ها</a></p>
    </div>

    @if ($topics->isEmpty())
        <div class="fe-empty" role="status">
            <span class="fe-empty-icon"><x-fe-icon name="inbox" size="24" /></span>
            <p>مطلبی با این مشخصات پیدا نشد.</p>
            <p>فیلتر دیگری را امتحان کن یا از اول شروع کن.</p>
            <p><a class="fe-btn fe-btn-primary" href="{{ route('app.home') }}"><x-fe-icon name="x" size="20" />پاک کردن فیلترها و دیدن همه مطالب</a></p>
        </div>
    @else
        <ul class="fe-cards">
            @foreach ($topics as $topic)
                <li>
                    @include('library._card', ['topic' => $topic])
                </li>
            @endforeach
        </ul>

        @if ($topics->hasPages())
            <nav class="fe-pagination" aria-label="صفحه‌های مطالب">
                {{ $topics->links('pagination.fe') }}
            </nav>
        @endif
    @endif
</div>
@endsection
