@extends('layouts.learner')

@section('title', 'حساب')

@section('content')
<div class="fe-measure fe-library">
    <h1>حساب</h1>

    <p>وارد شده به‌عنوان {{ auth()->user()->email }}</p>

    <livewire:update-display-name />

    <p class="fe-muted">سطح ترجیحی: {{ auth()->user()->preferred_level ?? 'انتخاب نشده' }}</p>

    <section aria-label="وضعیت پرداخت و اشتراک">
        <h2>اشتراک و پرداخت</h2>
        @if (! empty($subscription) && empty($subscription->revoked_at) && $subscription->starts_at <= now() && now() < $subscription->expires_at)
            <p>اشتراک فعال تا {{ $subscription->expires_at->timezone('Asia/Tehran')->format('Y/m/d H:i') }}</p>
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
            <p><a class="fe-btn fe-btn-primary" href="{{ route('subscribe.index') }}" wire:navigate>انتخاب پلن</a></p>
        @endif
    </section>

    <div class="fe-reader-actions">
        <a class="fe-btn" href="{{ route('app.saved') }}" wire:navigate>ذخیره‌شده‌ها</a>
        <a class="fe-btn" href="{{ route('account.settings') }}" wire:navigate>تنظیمات</a>
        <a class="fe-btn" href="{{ route('placement.index') }}" wire:navigate>تعیین سطح</a>
    </div>

    <form method="POST" action="{{ route('logout') }}" data-fe-logout>
        @csrf
        <button class="fe-btn" type="submit">Log out</button>
    </form>
</div>
@endsection
