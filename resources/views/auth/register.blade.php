@extends('layouts.app')

@section('content')
<div class="fe-auth">
    <section class="fe-auth-brand" aria-label="درباره فست اینگلیش">
        <a class="fe-brand" href="{{ route('landing') }}" aria-label="Fast English"><x-fe-brand /></a>
        <h1>یک حساب بساز؛ سطحت را انتخاب کن و بخوان.</h1>
        <ul class="fe-auth-points">
            <li><x-fe-icon name="book-open" size="20" />متن کوتاه در سطح خودت، با صوت هماهنگ</li>
            <li><x-fe-icon name="headphones" size="20" />پخش‌کننده‌ای که جمله‌به‌جمله جلو می‌رود</li>
            <li><x-fe-icon name="languages" size="20" />دفترچه واژه با مرور سر موعد</li>
        </ul>
    </section>

    <section class="fe-auth-card" aria-label="ساخت حساب">
        <h2>ساخت حساب</h2>
        <p class="fe-muted">نام، ایمیل و یک رمز عبور برای حسابت وارد کن.</p>

        @if ($errors->any())
            <div class="fe-alert" role="alert">
                <x-fe-icon name="circle-alert" size="20" />
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <form class="fe-auth-form" method="POST" action="{{ route('register.store') }}" data-fe-auth-form novalidate>
            @csrf
            <div class="fe-field">
                <label for="name"><x-fe-icon name="user" size="16" />نام</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus aria-describedby="name-error">
                @error('name')
                    <p class="fe-field-error" id="name-error" role="alert"><x-fe-icon name="circle-alert" size="16" />{{ $message }}</p>
                @enderror
            </div>
            <div class="fe-field">
                <label for="email"><x-fe-icon name="mail" size="16" />ایمیل</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" dir="ltr" aria-describedby="email-error">
                @error('email')
                    <p class="fe-field-error" id="email-error" role="alert"><x-fe-icon name="circle-alert" size="16" />{{ $message }}</p>
                @enderror
            </div>
            <div class="fe-field">
                <label for="password"><x-fe-icon name="lock" size="16" />رمز عبور</label>
                <div class="fe-password-wrap">
                    <input id="password" type="password" name="password" required autocomplete="new-password" aria-describedby="password-error">
                    <button class="fe-password-toggle" type="button" data-fe-password-toggle aria-controls="password" aria-pressed="false" aria-label="نمایش رمز عبور">
                        <span data-fe-icon-show hidden><x-fe-icon name="eye" size="20" /></span>
                        <span data-fe-icon-hide><x-fe-icon name="eye-off" size="20" /></span>
                    </button>
                </div>
                @error('password')
                    <p class="fe-field-error" id="password-error" role="alert"><x-fe-icon name="circle-alert" size="16" />{{ $message }}</p>
                @enderror
            </div>
            <div class="fe-field">
                <label for="password_confirmation"><x-fe-icon name="lock" size="16" />تکرار رمز عبور</label>
                <div class="fe-password-wrap">
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                    <button class="fe-password-toggle" type="button" data-fe-password-toggle aria-controls="password_confirmation" aria-pressed="false" aria-label="نمایش رمز عبور">
                        <span data-fe-icon-show hidden><x-fe-icon name="eye" size="20" /></span>
                        <span data-fe-icon-hide><x-fe-icon name="eye-off" size="20" /></span>
                    </button>
                </div>
            </div>
            <button class="fe-btn fe-btn-primary" type="submit" data-fe-submit><x-fe-icon name="user" size="20" /><span data-fe-submit-label>ساخت حساب</span></button>
        </form>

        <p class="fe-auth-alt">حساب داری؟ <a href="{{ route('login') }}">ورود به حساب</a></p>
    </section>
</div>
@endsection
