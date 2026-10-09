@extends('layouts.public')

@section('title', 'تأیید دومرحله‌ای')
@section('meta_description', 'تأیید دومرحله‌ای staff')
@section('noindex', '1')

@section('content')
<h1>تأیید دومرحله‌ای</h1>
<p class="fe-muted">کد فعلی برنامه احراز هویت یا یکی از کدهای بازیابی را وارد کنید.</p>

@if ($errors->any())
    <div class="fe-empty">
        <p class="fe-alert" role="alert">{{ $errors->first() }}</p>
    </div>
@endif

<form method="POST" action="{{ route('staff.twofactor.verify') }}" lang="fa" dir="rtl">
    @csrf
    <div class="fe-field">
        <label for="code">کد تأیید یا کد بازیابی</label>
        <input id="code" type="text" name="code" autocomplete="one-time-code" required dir="ltr">
    </div>
    <div class="fe-field fe-field-actions">
        <button class="fe-btn fe-btn-primary" type="submit">تأیید و ادامه</button>
    </div>
</form>
@endsection
