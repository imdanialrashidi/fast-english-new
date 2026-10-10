@extends('layouts.learner')

@section('title', 'تعیین سطح')

@section('content')
<div class="fe-measure fe-library">
    <div class="fe-library-head">
        <h1>تعیین سطح (اختیاری)</h1>
        <p class="fe-muted">آزمون ۲۰ سؤالی، چهارگزینه‌ای. شرکت اختیاری است و نتیجه فقط راهنمای اولیه است.</p>
    </div>

    @if (session('status'))
        <p class="fe-muted" role="status">{{ session('status') }}</p>
    @endif

    @if ($current === null)
        <div class="fe-empty" role="status">
            <span class="fe-empty-icon"><x-fe-icon name="graduation" size="24" /></span>
            <p>آزمون در دست آماده‌سازی است.</p>
            <p class="fe-muted">در دست آماده‌سازی — می‌توانید سطح خود را دستی انتخاب کنید.</p>
            <p><a class="fe-btn fe-btn-primary" href="{{ route('account.settings') }}" wire:navigate><x-fe-icon name="settings" size="20" />انتخاب دستی سطح</a></p>
        </div>
    @elseif ($openAttempt !== null)
        <div class="fe-empty">
            <span class="fe-empty-icon"><x-fe-icon name="file-text" size="24" /></span>
            <p>یک آزمون ناتمام دارید.</p>
            <p><a class="fe-btn fe-btn-primary" href="{{ route('placement.show', $openAttempt) }}" wire:navigate><x-fe-icon name="play" size="20" />ادامه آزمون</a></p>
        </div>
    @else
        <div class="fe-empty">
            <span class="fe-empty-icon"><x-fe-icon name="graduation" size="24" /></span>
            <p>نسخه فعال: {{ $current->version }}</p>
            <p class="fe-muted">۲۰ سؤال، هر سؤال یک پاسخ صحیح. پاسخ‌ها تک‌تک ذخیره می‌شوند.</p>
            <form method="POST" action="{{ route('placement.start') }}" lang="fa" dir="rtl">
                @csrf
                <button class="fe-btn fe-btn-primary" type="submit"><x-fe-icon name="play" size="20" />شروع آزمون</button>
            </form>
        </div>
    @endif

    @if ($errors->any())
        <div class="fe-empty">
            <p class="fe-alert" role="alert"><x-fe-icon name="circle-alert" size="20" />{{ $errors->first() }}</p>
        </div>
    @endif
</div>
@endsection
