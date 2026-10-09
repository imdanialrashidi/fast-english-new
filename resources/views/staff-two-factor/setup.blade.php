@extends('layouts.public')

@section('title', 'احراز هویت دومرحله‌ای staff')
@section('meta_description', 'فعال‌سازی احراز هویت دومرحله‌ای staff')
@section('noindex', '1')

@section('content')
<h1>احراز هویت دومرحله‌ای</h1>

@if (session('status'))
    <p class="fe-muted" role="status">{{ session('status') }}</p>
@endif

@if ($errors->any())
    <div class="fe-empty">
        <p class="fe-alert" role="alert">{{ $errors->first() }}</p>
    </div>
@endif

@if ($confirmed)
    <div class="fe-empty">
        <p>احراز هویت دومرحله‌ای برای این حساب فعال است.</p>
        <form method="POST" action="{{ route('staff.twofactor.disable') }}" lang="fa" dir="rtl">
            @csrf
            <div class="fe-field">
                <label for="code">کد فعلی برنامه احراز هویت (برای غیرفعال‌سازی)</label>
                <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" required dir="ltr">
            </div>
            <div class="fe-field fe-field-actions">
                <button class="fe-btn" type="submit">غیرفعال‌سازی</button>
            </div>
        </form>
    </div>
@elseif ($provisioning !== null)
    <div class="fe-empty">
        <p>کلید را در برنامه احراز هویت وارد کنید و کد نمایش‌داده‌شده را تأیید کنید.</p>
        @if ($provisioning['image'] !== null)
            <p><img src="{{ $provisioning['image'] }}" alt="کیوآر احراز هویت دومرحله‌ای" width="200" height="200"></p>
        @endif
        <p class="fe-muted" lang="en" dir="ltr">{{ $provisioning['url'] }}</p>
        <form method="POST" action="{{ route('staff.twofactor.confirm') }}" lang="fa" dir="rtl">
            @csrf
            <div class="fe-field">
                <label for="code">کد تأیید</label>
                <input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" required dir="ltr">
            </div>
            <div class="fe-field fe-field-actions">
                <button class="fe-btn fe-btn-primary" type="submit">تأیید و فعال‌سازی</button>
            </div>
        </form>
    </div>
@else
    <div class="fe-empty">
        <p>برای دسترسی به پنل staff، احراز هویت دومرحله‌ای (TOTP) الزامی است.</p>
        <form method="POST" action="{{ route('staff.twofactor.start') }}" lang="fa" dir="rtl">
            @csrf
            <div class="fe-field fe-field-actions">
                <button class="fe-btn fe-btn-primary" type="submit">شروع فعال‌سازی</button>
            </div>
        </form>
    </div>
@endif
@endsection
