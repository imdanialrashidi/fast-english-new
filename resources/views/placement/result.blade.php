@extends('layouts.learner')

@section('title', 'نتیجه تعیین سطح')

@section('content')
<div class="fe-measure fe-library">
    <h1>نتیجه تعیین سطح</h1>

    @if (session('status'))
        <p class="fe-muted" role="status">{{ session('status') }}</p>
    @endif

    <div class="fe-card">
        <div class="fe-card-body">
            <h2 class="fe-section-title"><x-fe-icon name="graduation" size="20" />کارنامه</h2>
            <p>نمره: {{ $attempt->score }} از ۲۰</p>
            <p>سطح پیشنهادی: {{ $attempt->recommended_level }}</p>
            <p class="fe-muted">راهنمای اولیه، بدون گواهی رسمی</p>
        </div>
    </div>

    <div class="fe-empty">
        <p>تأیید نتیجه، سطح ترجیحی شما را ذخیره می‌کند. مرور سطح‌ها بدون تأیید، سطح را تغییر نمی‌دهد.</p>
        <form method="POST" action="{{ route('placement.accept', $attempt) }}" lang="fa" dir="rtl">
            @csrf
            <button class="fe-btn fe-btn-primary" type="submit"><x-fe-icon name="check" size="20" />تأیید و ذخیره سطح ترجیحی</button>
        </form>
        <p><a class="fe-btn" href="{{ route('account.settings') }}" wire:navigate><x-fe-icon name="settings" size="20" />انتخاب دستی سطح</a></p>
    </div>
</div>
@endsection
