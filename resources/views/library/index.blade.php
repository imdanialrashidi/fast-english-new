@extends('layouts.learner')

@section('title', 'کشف مطالب')

@section('content')
<div class="fe-measure fe-library">
    <h1>کشف مطالب</h1>
    <p class="fe-muted">یک مطلب کوتاه در سطح خودت پیدا کن — هر موضوع یک‌بار، با سطح‌های موجودش.</p>

    <form class="fe-filters" method="get" action="{{ route('app.home') }}" role="search" aria-label="جست‌وجو و فیلتر مطالب">
        <div class="fe-field">
            <label for="filter-level">سطح</label>
            <select id="filter-level" name="level">
                <option value="">همه سطح‌ها</option>
                @foreach ($levels as $level)
                    <option value="{{ $level }}" @selected($activeLevel === $level)>{{ $level }}</option>
                @endforeach
            </select>
        </div>
        <div class="fe-field">
            <label for="filter-category">دسته</label>
            <select id="filter-category" name="category">
                <option value="">همه دسته‌ها</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->slug }}" @selected($activeCategory === $category->slug)>{{ $category->name_fa }}</option>
                @endforeach
            </select>
        </div>
        <div class="fe-field fe-field-search">
            <label for="filter-q">جست‌وجو در عنوان</label>
            <input id="filter-q" name="q" type="search" value="{{ $activeQuery }}" placeholder="مثلاً park" autocomplete="off">
        </div>
        <div class="fe-field fe-field-actions">
            <button class="fe-btn fe-btn-primary" type="submit">اعمال</button>
            @if ($hasFilters)
                <a class="fe-btn" href="{{ route('app.home') }}">پاک کردن فیلترها</a>
            @endif
        </div>
    </form>

    <p class="fe-muted" role="status">{{ $topics->total() }} مطلب</p>
    <p><a class="fe-btn" href="{{ route('app.saved') }}" wire:navigate>ذخیره‌شده‌ها</a></p>

    @if ($topics->isEmpty())
        <div class="fe-empty">
            <p>مطلبی با این مشخصات پیدا نشد.</p>
            <p><a class="fe-btn" href="{{ route('app.home') }}">پاک کردن فیلترها و دیدن همه مطالب</a></p>
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
                {{ $topics->links() }}
            </nav>
        @endif
    @endif
</div>
@endsection
