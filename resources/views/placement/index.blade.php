@extends('layouts.learner')

@section('title', 'تعیین سطح')

@section('content')
<div class="fe-measure fe-library">
    <h1>تعیین سطح (اختیاری)</h1>
    <p class="fe-muted">آزمون ۲۰ سؤالی، چهارگزینه‌ای. شرکت اختیاری است و نتیجه فقط راهنمای اولیه است.</p>

    @if (session('status'))
        <p class="fe-muted" role="status">{{ session('status') }}</p>
    @endif

    @if ($current === null)
        <div class="fe-empty">
            <p>آزمون در دست آماده‌سازی است.</p>
            <p class="fe-muted">در دست آماده‌سازی — می‌توانید سطح خود را دستی انتخاب کنید.</p>
            <p><a class="fe-btn fe-btn-primary" href="{{ route('account.settings') }}" wire:navigate>انتخاب دستی سطح</a></p>
        </div>
    @elseif ($openAttempt !== null)
        <div class="fe-empty">
            <p>یک آزمون ناتمام دارید.</p>
            <p><a class="fe-btn fe-btn-primary" href="{{ route('placement.show', $openAttempt) }}" wire:navigate>ادامه آزمون</a></p>
        </div>
    @else
        <div class="fe-empty">
            <p>نسخه فعال: {{ $current->version }}</p>
            <p class="fe-muted">۲۰ سؤال، هر سؤال یک پاسخ صحیح. پاسخ‌ها تک‌تک ذخیره می‌شوند.</p>
            <form method="POST" action="{{ route('placement.start') }}" lang="fa" dir="rtl">
                @csrf
                <button class="fe-btn fe-btn-primary" type="submit">شروع آزمون</button>
            </form>
        </div>
    @endif

    @if ($errors->any())
        <div class="fe-empty">
            <p class="fe-alert" role="alert">{{ $errors->first() }}</p>
        </div>
    @endif
</div>
@endsection
