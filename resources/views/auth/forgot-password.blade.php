@extends('layouts.public')

@section('title', 'بازیابی رمز عبور')
@section('meta_description', 'بازیابی رمز عبور Fast English')
@section('noindex', '1')

@section('content')
<h1>بازیابی رمز عبور</h1>
<p class="fe-muted">نشانی ایمیل حساب خود را وارد کنید. اگر حسابی با این ایمیل وجود داشته باشد، پیوند بازیابی ارسال می‌شود.</p>

@if (session('status'))
    <p class="fe-muted" role="status">{{ session('status') }}</p>
@endif

@if ($errors->any())
    <div class="fe-empty">
        <p class="fe-alert" role="alert">{{ $errors->first() }}</p>
    </div>
@endif

<form method="POST" action="{{ route('password.email') }}" lang="fa" dir="rtl">
    @csrf
    <div class="fe-field">
        <label for="email">ایمیل</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus dir="ltr">
    </div>
    <div class="fe-field fe-field-actions">
        <button class="fe-btn fe-btn-primary" type="submit">ارسال پیوند بازیابی</button>
    </div>
</form>
@endsection
