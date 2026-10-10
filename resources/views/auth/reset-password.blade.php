@extends('layouts.public')

@section('title', 'تعیین رمز عبور تازه')
@section('meta_description', 'تعیین رمز عبور تازه Fast English')
@section('noindex', '1')

@section('content')
<div class="fe-auth">
    <section class="fe-auth-brand" aria-label="درباره فست اینگلیش">
        <a class="fe-brand" href="{{ route('landing') }}" aria-label="Fast English"><x-fe-brand /></a>
        <h1>یک رمز تازه انتخاب کن.</h1>
        <ul class="fe-auth-points">
            <li><x-fe-icon name="lock" size="20" />رمز تازه را دو بار وارد کن تا ثبت شود</li>
        </ul>
    </section>

    <section class="fe-auth-card" aria-label="تعیین رمز عبور تازه">
        <h2>تعیین رمز عبور تازه</h2>
        <p class="fe-muted">رمز عبور تازه حساب خود را وارد کنید.</p>

        @if ($errors->any())
            <div class="fe-alert" role="alert">
                <x-fe-icon name="circle-alert" size="20" />
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form class="fe-auth-form" method="POST" action="{{ route('password.update') }}" lang="fa" dir="rtl" data-fe-auth-form novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">
            <div class="fe-field">
                <label for="email"><x-fe-icon name="mail" size="16" />ایمیل</label>
                <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autocomplete="email" dir="ltr">
            </div>
            <div class="fe-field">
                <label for="password"><x-fe-icon name="lock" size="16" />رمز عبور تازه</label>
                <div class="fe-password-wrap">
                    <input id="password" type="password" name="password" required autocomplete="new-password">
                    <button class="fe-password-toggle" type="button" data-fe-password-toggle aria-controls="password" aria-pressed="false" aria-label="نمایش رمز عبور">
                        <span data-fe-icon-show hidden><x-fe-icon name="eye" size="20" /></span>
                        <span data-fe-icon-hide><x-fe-icon name="eye-off" size="20" /></span>
                    </button>
                </div>
            </div>
            <div class="fe-field">
                <label for="password_confirmation"><x-fe-icon name="lock" size="16" />تکرار رمز عبور تازه</label>
                <div class="fe-password-wrap">
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                    <button class="fe-password-toggle" type="button" data-fe-password-toggle aria-controls="password_confirmation" aria-pressed="false" aria-label="نمایش رمز عبور">
                        <span data-fe-icon-show hidden><x-fe-icon name="eye" size="20" /></span>
                        <span data-fe-icon-hide><x-fe-icon name="eye-off" size="20" /></span>
                    </button>
                </div>
            </div>
            <button class="fe-btn fe-btn-primary" type="submit" data-fe-submit><x-fe-icon name="check" size="20" /><span data-fe-submit-label>ذخیره رمز تازه</span></button>
        </form>
    </section>
</div>
@endsection
