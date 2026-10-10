@extends('layouts.app')

@section('content')
<div class="fe-auth">
    <section class="fe-auth-brand" aria-label="درباره فست اینگلیش">
        <a class="fe-brand" href="{{ route('landing') }}" aria-label="Fast English"><x-fe-brand /></a>
        <h1>با یک متن کوتاه انگلیسی، امروز را شروع کن.</h1>
        <ul class="fe-auth-points">
            <li><x-fe-icon name="book-open" size="20" />متن کوتاه در سطح خودت، با صوت هماهنگ</li>
            <li><x-fe-icon name="headphones" size="20" />پخش‌کننده‌ای که جمله‌به‌جمله جلو می‌رود</li>
            <li><x-fe-icon name="languages" size="20" />دفترچه واژه با مرور سر موعد</li>
        </ul>
    </section>

    <section class="fe-auth-card" aria-label="ورود به حساب">
        <h2>ورود به حساب</h2>
        <p class="fe-muted">ایمیل و رمز عبورت را وارد کن تا به درس‌هایت برسی.</p>

        @if ($errors->any())
            <div class="fe-alert" role="alert">
                <x-fe-icon name="circle-alert" size="20" />
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form class="fe-auth-form" method="POST" action="{{ route('login.store') }}" data-fe-auth-form novalidate>
            @csrf
            <div class="fe-field">
                <label for="email"><x-fe-icon name="mail" size="16" />ایمیل</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus dir="ltr" aria-describedby="email-error">
                @error('email')
                    <p class="fe-field-error" id="email-error" role="alert"><x-fe-icon name="circle-alert" size="16" />{{ $message }}</p>
                @enderror
            </div>
            <div class="fe-field">
                <label for="password"><x-fe-icon name="lock" size="16" />رمز عبور</label>
                <div class="fe-password-wrap">
                    <input id="password" type="password" name="password" required autocomplete="current-password" aria-describedby="password-error">
                    <button class="fe-password-toggle" type="button" data-fe-password-toggle aria-controls="password" aria-pressed="false" aria-label="نمایش رمز عبور">
                        <span data-fe-icon-show hidden><x-fe-icon name="eye" size="20" /></span>
                        <span data-fe-icon-hide><x-fe-icon name="eye-off" size="20" /></span>
                    </button>
                </div>
                @error('password')
                    <p class="fe-field-error" id="password-error" role="alert"><x-fe-icon name="circle-alert" size="16" />{{ $message }}</p>
                @enderror
            </div>
            <button class="fe-btn fe-btn-primary" type="submit" data-fe-submit><x-fe-icon name="log-in" size="20" /><span data-fe-submit-label>ورود</span></button>
        </form>

        <p class="fe-auth-alt">حساب نداری؟ <a href="{{ route('register') }}">ساخت حساب</a> · <a href="{{ route('password.request') }}">رمز را فراموش کرده‌ام</a></p>
    </section>
</div>
@endsection
