@extends('layouts.learner')

@section('title', 'درخواست پرداخت')

@section('content')
<div class="fe-measure fe-library">
    <h1>درخواست پرداخت</h1>

    @if (session('status'))
        <p class="fe-muted" role="status">{{ session('status') }}</p>
    @endif

    <div class="fe-card">
        <div class="fe-card-body">
            <h2 class="fe-card-title" lang="fa" dir="rtl">{{ $paymentRequest->plan_name_snapshot }}</h2>
            <p class="fe-card-levels">{{ \App\Support\Toman::format((int) $paymentRequest->amount_toman_snapshot) }}</p>
            <p class="fe-muted">{{ $paymentRequest->duration_days_snapshot }} روز دسترسی پس از تأیید</p>
            <p class="fe-muted">وضعیت:
                @if ($paymentRequest->status === 'awaiting_receipt')
                    در انتظار رسید
                @elseif ($paymentRequest->status === 'pending')
                    در صف بررسی
                @elseif ($paymentRequest->status === 'rejected')
                    رد شده
                @elseif ($paymentRequest->status === 'cancelled')
                    لغو شده
                @elseif ($paymentRequest->status === 'approved')
                    تأیید شده
                @else
                    {{ $paymentRequest->status }}
                @endif
            </p>
            @php
                $snapshot = $paymentRequest->destination_snapshot ?? [];
            @endphp
            @if (! empty($snapshot))
                <p>کارت مقصد: <span dir="ltr">{{ $snapshot['card_number'] ?? '' }}</span></p>
                <p>به‌نام: {{ $snapshot['holder_name'] ?? '' }}</p>
                <p>بانک: {{ $snapshot['bank_name'] ?? '' }}</p>
            @endif
            @if (! empty($paymentRequest->public_reason))
                <p>دلیل: {{ $paymentRequest->public_reason }}</p>
            @endif
        </div>
    </div>

    @if ($paymentRequest->status === 'awaiting_receipt')
        <section aria-label="ارسال رسید">
            <h2>قدم بعدی: واریز و ارسال رسید</h2>
            <p class="fe-muted">مبلغ بالا را به کارت مقصد واریز کنید، سپس رسید را همین‌جا ارسال کنید. نتیجه فقط پس از ثبت در سرور نمایش داده می‌شود.</p>
            <form class="fe-filters" method="POST" action="{{ route('payments.receipt.store', $paymentRequest) }}" enctype="multipart/form-data" lang="fa" dir="rtl">
                @csrf
                <div class="fe-field">
                    <label for="receipt">تصویر رسید (JPEG/PNG/WebP تا ۵ مگابایت)</label>
                    <input id="receipt" name="receipt" type="file" accept="image/jpeg,image/png,image/webp" required>
                    @error('receipt')
                        <p class="fe-alert" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <div class="fe-field">
                    <label for="bank_reference">کد پیگیری (اختیاری)</label>
                    <input id="bank_reference" name="bank_reference" type="text" value="{{ old('bank_reference') }}" maxlength="64" autocomplete="off">
                    @error('bank_reference')
                        <p class="fe-alert" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <div class="fe-field">
                    <label for="sender_last4">چهار رقم آخر کارت مبدأ (اختیاری)</label>
                    <input id="sender_last4" name="sender_last4" type="text" value="{{ old('sender_last4') }}" maxlength="4" inputmode="numeric" autocomplete="off">
                    @error('sender_last4')
                        <p class="fe-alert" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <div class="fe-field">
                    <label for="transferred_at">زمان انتقال (اختیاری)</label>
                    <input id="transferred_at" name="transferred_at" type="datetime-local" value="{{ old('transferred_at') }}">
                    @error('transferred_at')
                        <p class="fe-alert" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <div class="fe-field fe-field-actions">
                    <button class="fe-btn fe-btn-primary" type="submit">ثبت رسید</button>
                </div>
            </form>
            <form method="POST" action="{{ route('payments.cancel', $paymentRequest) }}" lang="fa" dir="rtl">
                @csrf
                <div class="fe-field fe-field-actions">
                    <button class="fe-btn" type="submit">لغو این درخواست</button>
                </div>
            </form>
        </section>
    @elseif ($paymentRequest->status === 'pending')
        <section aria-label="وضعیت بررسی">
            <h2>قدم بعدی: انتظار برای بررسی</h2>
            <p class="fe-muted">رسید شما ثبت شد و در صف بررسی قرار گرفت. این وضعیت دسترسی تازه ایجاد نمی‌کند. برای بررسی وضعیت همین صفحه را دوباره بارگذاری کنید.</p>
            <p><a class="fe-btn" href="{{ route('payments.show', $paymentRequest) }}" wire:navigate>بررسی وضعیت</a></p>
            @if ($paymentRequest->receipt_path)
                <p><a class="fe-btn" href="{{ route('payments.receipt.show', $paymentRequest) }}">مشاهده رسید خودم</a></p>
            @endif
        </section>
    @elseif ($paymentRequest->status === 'rejected')
        <section aria-label="رد شده">
            <h2>قدم بعدی: خرید دوباره</h2>
            <p class="fe-muted">این درخواست رد شد. برای ادامه، یک درخواست تازه بسازید؛ سابقه قبلی حفظ می‌شود.</p>
            <p><a class="fe-btn fe-btn-primary" href="{{ route('subscribe.index') }}" wire:navigate>خرید دوباره</a></p>
        </section>
    @elseif ($paymentRequest->status === 'cancelled')
        <section aria-label="لغو شده">
            <h2>قدم بعدی: خرید دوباره در صورت نیاز</h2>
            <p class="fe-muted">این درخواست لغو شد و سابقه آن حفظ شده است. برای شروع تازه، یک پلن انتخاب کنید.</p>
            <p><a class="fe-btn fe-btn-primary" href="{{ route('subscribe.index') }}" wire:navigate>انتخاب پلن</a></p>
        </section>
    @elseif ($paymentRequest->status === 'approved')
        <section aria-label="تأیید شده">
            <h2>تأیید شده</h2>
            <p class="fe-muted">این درخواست تأیید شده است. جزئیات اشتراک در حساب نمایش داده می‌شود.</p>
        </section>
    @endif
</div>
@endsection
