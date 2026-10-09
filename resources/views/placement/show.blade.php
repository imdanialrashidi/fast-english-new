@extends('layouts.learner')

@section('title', 'سؤالات تعیین سطح')

@section('content')
<div class="fe-measure fe-library">
    <h1>سؤالات تعیین سطح</h1>
    <p class="fe-muted">{{ count($selected) }} از ۲۰ پاسخ ذخیره شده است.</p>

    @if (session('status'))
        <p class="fe-muted" role="status">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <div class="fe-empty">
            <p class="fe-alert" role="alert">{{ $errors->first() }}</p>
        </div>
    @endif

    @foreach ($questions as $question)
        <section class="fe-card" aria-label="سؤال {{ $question->position }}">
            <div class="fe-card-body">
                <h2 class="fe-card-title" lang="en" dir="ltr">{{ $question->position }}. {{ $question->prompt }}</h2>
                <form method="POST" action="{{ route('placement.answer', $attempt) }}" lang="fa" dir="rtl">
                    @csrf
                    <input type="hidden" name="question_id" value="{{ $question->id }}">
                    <div class="fe-field" role="radiogroup" aria-label="گزینه‌های سؤال {{ $question->position }}">
                        @foreach (($question->options ?? []) as $index => $option)
                            <label class="fe-option" lang="en" dir="ltr">
                                <input type="radio" name="selected_option" value="{{ $index }}"
                                    @checked((($selected[$question->id] ?? null) === $index)) required>
                                <span>{{ $option }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="fe-field fe-field-actions">
                        <button class="fe-btn" type="submit">ذخیره پاسخ سؤال {{ $question->position }}</button>
                    </div>
                </form>
            </div>
        </section>
    @endforeach

    <div class="fe-empty">
        <p>پس از ذخیره همه پاسخ‌ها، نتیجه را ثبت کنید. پاسخ ناقص نتیجه‌ای ندارد.</p>
        <form method="POST" action="{{ route('placement.submit', $attempt) }}" lang="fa" dir="rtl">
            @csrf
            <button class="fe-btn fe-btn-primary" type="submit">ثبت نهایی پاسخ‌ها</button>
        </form>
    </div>
</div>
@endsection
