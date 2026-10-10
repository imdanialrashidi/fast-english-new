@extends('layouts.public')

@section('title', 'بازیابی رمز عبور')
@section('meta_description', 'بازیابی رمز عبور Fast English')
@section('noindex', '1')

@section('content')
<div class="fe-auth">
    <section class="fe-auth-brand" aria-label="درباره فست اینگلیش">
        <a class="fe-brand" href="{{ route('landing') }}" aria-label="Fast English"><x-fe-brand /></a>
        <h1>رمزت یادت رفته؟ پیوند تازه می‌فرستیم.</h1>
        <ul class="fe-auth-points">
            <li><x-fe-icon name="mail" size="20" />پیوند بازیابی به همین ایمیل ارسال می‌شود</li>
            <li><x-fe-icon name="lock" size="20" />بدون ایمیل درست، چیزی ارسال نمی‌شود</li>
        </ul>
    </section>

    <section class="fe-auth-card" aria-label="بازیابی رمز عبور">
        <h2>بازیابی رمز عبور</h2>
        <p class="fe-muted">نشانی ایمیل حساب خود را وارد کنید. اگر حسابی با این ایمیل وجود داشته باشد، پیوند بازیابی ارسال می‌شود.</p>

        @if (session('status'))
            <p class="fe-success-note" role="status"><x-fe-icon name="circle-check" size="20" />{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <div class="fe-alert" role="alert">
                <x-fe-icon name="circle-alert" size="20" />
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form class="fe-auth-form" method="POST" action="{{ route('password.email') }}" lang="fa" dir="rtl" data-fe-auth-form novalidate>
            @csrf
            <div class="fe-field">
                <label for="email"><x-fe-icon name="mail" size="16" />ایمیل</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus dir="ltr">
            </div>
            <button class="fe-btn fe-btn-primary" type="submit" data-fe-submit><x-fe-icon name="mail" size="20" /><span data-fe-submit-label>ارسال پیوند بازیابی</span></button>
        </form>

        <p class="fe-auth-alt"><a href="{{ route('login') }}">بازگشت به ورود</a></p>
    </section>
</div>
@endsection
