@extends('layouts.public')

@section('title', 'کدهای بازیابی')
@section('meta_description', 'کدهای بازیابی احراز هویت دومرحله‌ای')
@section('noindex', '1')

@section('content')
<h1>کدهای بازیابی</h1>
<div class="fe-empty">
    <p><strong>این کدها فقط یک‌بار نمایش داده می‌شوند.</strong> آن‌ها را در جایی امن نگه دارید. هر کد فقط یک‌بار قابل استفاده است.</p>
    <ul>
        @foreach ($codes as $code)
            <li lang="en" dir="ltr">{{ $code }}</li>
        @endforeach
    </ul>
    <p>
        <a class="fe-btn fe-btn-primary" href="/admin">ادامه به پنل</a>
    </p>
</div>
@endsection
