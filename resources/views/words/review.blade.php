@extends('layouts.learner')

@section('title', 'مرور واژه‌ها')

@section('content')
<div class="fe-measure fe-review">
    <div class="fe-review-head">
        <p class="fe-display fe-review-eyebrow" lang="en" dir="ltr">Review</p>
        <h1>مرور واژه‌ها</h1>
        <p class="fe-muted" role="status">{{ $remaining }} واژه برای مرور</p>
    </div>

    @if ($card === null)
        <div class="fe-empty" role="status">
            <span class="fe-empty-icon"><x-fe-icon name="circle-check" size="24" /></span>
            <p>واژه‌ای برای مرور نیست.</p>
            <p class="fe-muted">واژه‌های جدید را از درس‌ها ذخیره کن تا اینجا مرور شوند.</p>
            <p><a class="fe-btn fe-btn-primary" href="{{ route('words.index') }}" wire:navigate>بازگشت به دفترچه</a></p>
        </div>
    @else
        <section class="fe-flash" aria-label="کارت مرور" data-word-id="{{ $card->id }}">
            <h2 class="fe-flash-word" lang="en" dir="ltr">{{ $card->word }}</h2>
            <div id="flash-meaning" hidden>
                <p class="fe-flash-meaning" dir="auto">{{ $card->meaning_fa ?? '—' }}</p>
                @if ($card->example_en)
                    <p class="fe-flash-example" lang="en" dir="ltr">{{ $card->example_en }}</p>
                @endif
                @if ($card->lesson !== null && $card->topic !== null)
                    <p class="fe-vocab-meta"><a href="{{ route('reader.show', ['topic' => $card->topic->slug, 'level' => $card->lesson->level]) }}" wire:navigate><x-fe-icon name="book-open" size="16" />دیدن در متن اصلی</a></p>
                @endif
            </div>
            <p class="fe-flash-show-row"><button id="flash-show" class="fe-btn fe-btn-primary" type="button"><x-fe-icon name="eye" size="20" />نمایش معنی</button></p>
            <div id="flash-grades" class="fe-grade-row" role="group" aria-label="نتیجه مرور" hidden>
                @foreach (['again' => 'دوباره', 'hard' => 'سخت', 'good' => 'خوب', 'easy' => 'آسان'] as $grade => $label)
                    @php $shortcut = ['again' => '1', 'hard' => '2', 'good' => '3', 'easy' => '4'][$grade]; @endphp
                    <button class="fe-btn fe-grade" type="button" data-grade="{{ $grade }}" data-word-id="{{ $card->id }}" aria-keyshortcuts="{{ $shortcut }}">{{ $label }}<span class="fe-grade-key" aria-hidden="true">{{ $shortcut }}</span></button>
                @endforeach
            </div>
            <p class="fe-grade-hint fe-muted" id="flash-grades-hint" hidden>کلیدهای ۱ تا ۴ هم کار می‌کنند.</p>
            <p id="grade-status" class="fe-muted" role="status"></p>
        </section>
        <p><a class="fe-btn" href="{{ route('words.index') }}" wire:navigate>بازگشت به دفترچه</a></p>
    @endif
</div>
@endsection
