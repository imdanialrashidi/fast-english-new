@extends('layouts.learner')

@section('title', $lesson->title_en.' ('.$lesson->level.')')

@section('content')
<div class="fe-measure">
    <nav aria-label="سطح درس">
        <p class="fe-muted">سطح:</p>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            @foreach ($availableLevels as $level)
                <a class="fe-level-link"
                   href="{{ route('reader.show', ['topic' => $topic->slug, 'level' => $level]) }}"
                   wire:navigate
                   @if ($level === $lesson->level) aria-current="page" @endif>{{ $level }}</a>
            @endforeach
        </div>
    </nav>

    <h1 lang="en" dir="ltr">{{ $lesson->title_en }}</h1>
    @if ($topic->cover_path)
        <img class="fe-cover" src="/storage/{{ $topic->cover_path }}" alt="">
    @endif
    <p dir="auto" class="fe-muted">{{ $topic->summary_public }}</p>
    <p class="fe-muted">سطح {{ $lesson->level }} · حدود {{ $lesson->estimated_minutes }} دقیقه مطالعه</p>

    {{-- Lesson binding for the persistent player in the learner layout.
         The audio URL stays in the HTML so the S1 no-mixing proof (body +
         audio URL present only for the active level) keeps holding; the
         layout player reads these attributes on load and on
         livewire:navigated, sets its source without autoplay, and seeks to
         the saved position when the revision matches. --}}
    <div id="fe-lesson-data"
         data-lesson-id="{{ $lesson->id }}"
         data-audio-url="{{ route('media.lesson.audio', $lesson) }}"
         data-revision="{{ $lesson->audio_revision }}"
         data-initial-position="{{ $initialPosition ?? 0 }}"
         data-topic="{{ $topic->title_en }}"
         data-level="{{ $lesson->level }}"
         data-authenticated="{{ auth()->check() ? '1' : '0' }}"
         data-completed="{{ ! empty($completedAt) ? '1' : '0' }}"
         hidden></div>

    @if (! empty($initialPosition) && $initialPosition > 1 && empty($completedAt))
        <p class="fe-muted" role="status">ادامه از موقعیت ذخیره‌شده</p>
    @endif
    @if (! empty($completedAt))
        <p class="fe-muted" role="status">این درس تکمیل شده است</p>
    @endif

    @auth
    <div class="fe-reader-actions">
        <button id="bookmark-toggle" class="fe-btn" type="button"
                data-topic-id="{{ $topic->id }}"
                data-bookmarked="{{ ! empty($bookmarked) ? '1' : '0' }}"
                aria-pressed="{{ ! empty($bookmarked) ? 'true' : 'false' }}">
            {{ ! empty($bookmarked) ? 'حذف از ذخیره‌شده‌ها' : 'ذخیره برای بعد' }}
        </button>
        @if (empty($completedAt))
            <button id="mark-read" class="fe-btn" type="button" data-lesson-id="{{ $lesson->id }}">خواندم</button>
        @else
            <button id="reset-progress" class="fe-btn" type="button" data-lesson-id="{{ $lesson->id }}">شروع دوباره</button>
        @endif
    </div>
    <p id="bookmark-status" class="fe-muted" role="status"></p>
    @endauth

    @if ($cuesUsable)
        <ol class="fe-sentences" lang="en" dir="ltr" aria-label="متن درس با همگام‌سازی صوت">
            @foreach ($sentences as $index => $sentence)
                <li>
                    <button class="fe-sentence" type="button"
                            data-index="{{ $index }}"
                            data-start="{{ $cues[$index]['start_seconds'] }}"
                            data-end="{{ $cues[$index]['end_seconds'] }}"
                            aria-current="false"
                            aria-label="پخش جمله {{ $index + 1 }} از {{ count($sentences) }}">{{ $sentence }}</button>
                </li>
            @endforeach
        </ol>
    @else
        <article class="fe-english-body" lang="en" dir="ltr">
            @foreach (preg_split("/\\R{2,}/", $lesson->body_en) as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach
        </article>
        <p class="fe-muted" role="note">همگام‌سازی جمله‌به‌جمله برای این نسخه هنوز آماده نیست؛ پخش عادی فعال است و درس بدون مشکل کامل می‌شود.</p>
    @endif

    {{-- Active lesson's glossary only. Every value is escaped; premium
         lessons never reach this view for ineligible readers (policy 403
         happens in the controller first). Each word can be saved into the
         personal vocabulary notebook. --}}
    @if (! empty($lesson->glossary))
        <section aria-label="واژه‌های کلیدی">
            <h2>واژه‌های کلیدی</h2>
            <dl class="fe-glossary">
                @foreach ($lesson->glossary as $entry)
                    <div class="fe-glossary-row">
                        <dt lang="en" dir="ltr">{{ $entry['word'] }}</dt>
                        <dd>{{ $entry['meaning_fa'] }}</dd>
                        @if (! empty($entry['example_en']))
                            <dd class="fe-muted" lang="en" dir="ltr">{{ $entry['example_en'] }}</dd>
                        @endif
                        @auth
                            <dd>
                                <button class="fe-btn fe-vocab-save" type="button"
                                        data-word="{{ $entry['word'] }}"
                                        data-meaning="{{ $entry['meaning_fa'] }}"
                                        data-example="{{ $entry['example_en'] ?? '' }}"
                                        data-lesson-id="{{ $lesson->id }}">ذخیره واژه</button>
                            </dd>
                        @endauth
                    </div>
                @endforeach
            </dl>
            @auth
                <p id="vocab-status" class="fe-muted" role="status"></p>
            @endauth
        </section>
    @else
        <section aria-label="واژه‌های کلیدی">
            <h2>واژه‌های کلیدی</h2>
            <p class="fe-muted" role="note">برای این درس واژه‌ای ثبت نشده است.</p>
        </section>
    @endif
</div>
@endsection
