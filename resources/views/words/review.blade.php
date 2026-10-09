@extends('layouts.learner')

@section('title', 'مرور واژه‌ها')

@section('content')
<div class="fe-measure fe-today">
    <h1>مرور واژه‌ها</h1>
    <p class="fe-muted" role="status">{{ $remaining }} واژه برای مرور</p>

    @if ($card === null)
        <div class="fe-empty">
            <p>واژه‌ای برای مرور نیست. 🎉</p>
            <p><a class="fe-btn" href="{{ route('words.index') }}" wire:navigate>بازگشت به دفترچه</a></p>
        </div>
    @else
        <section class="fe-flash" aria-label="کارت مرور">
            <h2 class="fe-flash-word" lang="en" dir="ltr">{{ $card->word }}</h2>
            <p id="flash-meaning" hidden>
                <span dir="auto">{{ $card->meaning_fa ?? '—' }}</span>
                @if ($card->example_en)
                    <br><small class="fe-muted" lang="en" dir="ltr">{{ $card->example_en }}</small>
                @endif
                @if ($card->lesson !== null && $card->topic !== null)
                    <br><small class="fe-muted"><a href="{{ route('reader.show', ['topic' => $card->topic->slug, 'level' => $card->lesson->level]) }}" wire:navigate>دیدن در متن اصلی</a></small>
                @endif
            </p>
            <p><button id="flash-show" class="fe-btn" type="button">نمایش معنی</button></p>
            <div id="flash-grades" class="fe-grade-row" role="group" aria-label="نتیجه مرور" hidden>
                @foreach (['again' => 'دوباره', 'hard' => 'سخت', 'good' => 'خوب', 'easy' => 'آسان'] as $grade => $label)
                    <button class="fe-btn fe-grade" type="button" data-grade="{{ $grade }}" data-word-id="{{ $card->id }}">{{ $label }}</button>
                @endforeach
            </div>
            <p id="grade-status" class="fe-muted" role="status"></p>
        </section>
        <p><a class="fe-btn" href="{{ route('words.index') }}" wire:navigate>بازگشت به دفترچه</a></p>
    @endif
</div>
@endsection
