@extends('layouts.public')

@section('title', 'تعیین رمز عبور تازه')
@section('meta_description', 'تعیین رمز عبور تازه Fast English')
@section('noindex', '1')

@section('content')
<h1>تعیین رمز عبور تازه</h1>
<p class="fe-muted">رمز عبور تازه حساب خود را وارد کنید.</p>

@if ($errors->any())
    <div class="fe-empty">
        <p class="fe-alert" role="alert">{{ $errors->first() }}</p>
    </div>
@endif

<form method="POST" action="{{ route('password.update') }}" lang="fa" dir="rtl">
    @csrf
    <input type="hidden" name="token" value="{{ $request->route('token') }}">
    <div class="fe-field">
        <label for="email">ایمیل</label>
        <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autocomplete="email" dir="ltr">
    </div>
    <div class="fe-field">
        <label for="password">رمز عبور تازه</label>
        <input id="password" type="password" name="password" required autocomplete="new-password">
    </div>
    <div class="fe-field">
        <label for="password_confirmation">تکرار رمز عبور تازه</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
    </div>
    <div class="fe-field fe-field-actions">
        <button class="fe-btn fe-btn-primary" type="submit">ذخیره رمز تازه</button>
    </div>
</form>
@endsection
