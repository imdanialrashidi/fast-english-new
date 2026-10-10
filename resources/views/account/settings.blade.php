@extends('layouts.learner')

@section('title', 'تنظیمات حساب')

@section('content')
<div class="fe-measure fe-library">
    <div class="fe-library-head">
        <h1>تنظیمات</h1>
        <p class="fe-muted">سطح، هدف روزانه و ظاهر برنامه را اینجا تنظیم کن.</p>
    </div>

    @if (session('status'))
        <p class="fe-success-note" role="status"><x-fe-icon name="circle-check" size="20" />{{ session('status') }}</p>
    @endif

    <section class="fe-card" aria-label="ظاهر برنامه">
        <div class="fe-card-body">
            <h2 class="fe-section-title"><x-fe-icon name="monitor" size="20" />ظاهر برنامه</h2>
            <div class="fe-appearance" role="radiogroup" aria-label="انتخاب ظاهر">
                <label class="fe-appearance-option">
                    <input type="radio" name="appearance" value="system">
                    <x-fe-icon name="monitor" size="20" />
                    <span>سیستم <small class="fe-muted">— با دستگاه هماهنگ می‌شود</small></span>
                </label>
                <label class="fe-appearance-option">
                    <input type="radio" name="appearance" value="light">
                    <x-fe-icon name="sun" size="20" />
                    <span>روشن</span>
                </label>
                <label class="fe-appearance-option">
                    <input type="radio" name="appearance" value="dark">
                    <x-fe-icon name="moon" size="20" />
                    <span>تیره</span>
                </label>
            </div>
            <p id="appearance-status" class="fe-muted" role="status"></p>
            <p class="fe-field-hint">«سیستم» پیش‌فرض است و با تغییر حالت دستگاه به‌روز می‌شود. انتخاب روشن یا تیره فقط در همین دستگاه ذخیره می‌شود.</p>
        </div>
    </section>

    <form class="fe-filters" method="POST" action="{{ route('account.settings.update') }}">
        @csrf
        @method('PATCH')
        <p class="fe-filters-title fe-field-search"><x-fe-icon name="settings" size="20" />یادگیری</p>
        <div class="fe-field">
            <label for="preferred-level">سطح ترجیحی</label>
            <select id="preferred-level" name="preferred_level">
                <option value="">انتخاب نشده</option>
                @foreach ($levels as $level)
                    <option value="{{ $level }}" @selected($user->preferred_level === $level)>{{ $level }}</option>
                @endforeach
            </select>
            @error('preferred_level')
                <p class="fe-alert" role="alert"><x-fe-icon name="circle-alert" size="20" />{{ $message }}</p>
            @enderror
        </div>
        <div class="fe-field">
            <label for="daily-goal">هدف روزانه (دقیقه)</label>
            <select id="daily-goal" name="daily_goal_minutes">
                @foreach ([5, 10, 15] as $goal)
                    <option value="{{ $goal }}" @selected((int) ($user->daily_goal_minutes ?? 10) === $goal)>{{ $goal }} دقیقه</option>
                @endforeach
            </select>
            @error('daily_goal_minutes')
                <p class="fe-alert" role="alert"><x-fe-icon name="circle-alert" size="20" />{{ $message }}</p>
            @enderror
        </div>
        <div class="fe-field fe-field-actions">
            <button class="fe-btn fe-btn-primary" type="submit"><x-fe-icon name="check" size="20" />ذخیره</button>
        </div>
    </form>

    <p class="fe-muted">تغییر سطح در صفحه مطلب، سطح ترجیحی را تغییر نمی‌دهد. تغییر هدف روزانه، سطح ترجیحی را تغییر نمی‌دهد.</p>
</div>
@endsection
