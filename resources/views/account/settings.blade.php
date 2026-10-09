@extends('layouts.learner')

@section('title', 'تنظیمات حساب')

@section('content')
<div class="fe-measure">
    <h1>تنظیمات</h1>

    @if (session('status'))
        <p class="fe-muted" role="status">{{ session('status') }}</p>
    @endif

    <form class="fe-filters" method="POST" action="{{ route('account.settings.update') }}">
        @csrf
        @method('PATCH')
        <div class="fe-field">
            <label for="preferred-level">سطح ترجیحی</label>
            <select id="preferred-level" name="preferred_level">
                <option value="">انتخاب نشده</option>
                @foreach ($levels as $level)
                    <option value="{{ $level }}" @selected($user->preferred_level === $level)>{{ $level }}</option>
                @endforeach
            </select>
            @error('preferred_level')
                <p class="fe-alert" role="alert">{{ $message }}</p>
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
                <p class="fe-alert" role="alert">{{ $message }}</p>
            @enderror
        </div>
        <div class="fe-field fe-field-actions">
            <button class="fe-btn fe-btn-primary" type="submit">ذخیره</button>
        </div>
    </form>

    <p class="fe-muted">تغییر سطح در صفحه مطلب، سطح ترجیحی را تغییر نمی‌دهد. تغییر هدف روزانه، سطح ترجیحی را تغییر نمی‌دهد.</p>
</div>
@endsection
