@extends('layouts.learner')

@section('title', 'حساب')

@section('content')
<div class="fe-measure fe-library">
    <div class="fe-library-head">
        <h1>حساب</h1>
        <p class="fe-muted"><x-fe-icon name="user" size="16" />وارد شده به‌عنوان <span dir="ltr">{{ auth()->user()->email }}</span></p>
    </div>

    <livewire:update-display-name />

    <p class="fe-card-meta"><x-fe-icon name="gauge" size="16" />سطح ترجیحی: {{ auth()->user()->preferred_level ?? 'انتخاب نشده' }} · هدف روزانه: {{ (int) (auth()->user()->daily_goal_minutes ?? 10) }} دقیقه</p>

    <section class="fe-card" aria-label="وضعیت پرداخت و اشتراک">
        <div class="fe-card-body">
            <h2 class="fe-section-title"><x-fe-icon name="card" size="20" />اشتراک و پرداخت</h2>
            @if (! empty($subscription) && empty($subscription->revoked_at) && $subscription->starts_at <= now() && now() < $subscription->expires_at)
                <p><x-fe-icon name="circle-check" size="16" />اشتراک فعال تا {{ $subscription->expires_at->timezone('Asia/Tehran')->format('Y/m/d H:i') }}</p>
            @else
                <p class="fe-muted">هنوز اشتراک فعالی وجود ندارد.</p>
            @endif
            @if (! empty($openRequest))
                <p>درخواست جاری:
                    @if ($openRequest->status === 'awaiting_receipt')
                        در انتظار رسید
                    @elseif ($openRequest->status === 'pending')
                        در صف بررسی
                    @endif
                </p>
                <p><a class="fe-btn fe-btn-primary" href="{{ route('payments.show', $openRequest) }}" wire:navigate>مشاهده درخواست</a></p>
            @elseif (! empty($latestRequest) && in_array($latestRequest->status, ['rejected', 'cancelled'], true))
                <p>آخرین درخواست:
                    @if ($latestRequest->status === 'rejected')
                        رد شده
                    @else
                        لغو شده
                    @endif
                </p>
                <p><a class="fe-btn fe-btn-primary" href="{{ route('subscribe.index') }}" wire:navigate>انتخاب پلن</a></p>
            @else
                <p><a class="fe-btn fe-btn-primary" href="{{ route('subscribe.index') }}" wire:navigate><x-fe-icon name="card" size="20" />انتخاب پلن</a></p>
            @endif
        </div>
    </section>

    <div class="fe-reader-actions">
        <a class="fe-btn" href="{{ route('today.index') }}" wire:navigate><x-fe-icon name="home" size="20" />امروز</a>
        <a class="fe-btn" href="{{ route('app.saved') }}" wire:navigate><x-fe-icon name="bookmark" size="20" />ذخیره‌شده‌ها</a>
        <a class="fe-btn" href="{{ route('words.index') }}" wire:navigate><x-fe-icon name="languages" size="20" />واژه‌ها</a>
        <a class="fe-btn" href="{{ route('account.settings') }}" wire:navigate><x-fe-icon name="settings" size="20" />تنظیمات</a>
        <a class="fe-btn" href="{{ route('placement.index') }}" wire:navigate><x-fe-icon name="graduation" size="20" />تعیین سطح</a>
    </div>

    <form method="POST" action="{{ route('logout') }}" data-fe-logout>
        @csrf
        <button class="fe-btn" type="submit"><x-fe-icon name="log-out" size="20" />خروج از حساب</button>
    </form>
</div>
@endsection
