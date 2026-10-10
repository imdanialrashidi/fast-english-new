@extends('layouts.learner')

@section('title', $lesson->title_en.' ('.$lesson->level.')')

@section('content')
<div class="fe-measure fe-reader">
    {{-- Immersive editorial order: title → bounded cover → level pills → player → body → key words (scope §17.3). Meta and lede sit with the title; the fixed studio player in the layout is the player step, bound via #fe-lesson-data below. --}}
    <header class="fe-reader-head">
        <h1 class="fe-reader-title" lang="en" dir="ltr">{{ $lesson->title_en }}</h1>
        <p class="fe-reader-meta"><x-fe-icon name="clock" size="16" />سطح <span lang="en" dir="ltr">{{ $lesson->level }}</span> · حدود {{ $lesson->estimated_minutes }} دقیقه مطالعه</p>
        <p class="fe-reader-lede fe-muted" dir="auto">{{ $topic->summary_public }}</p>
        @if ($topic->cover_path)
            <img class="fe-cover" src="/storage/{{ $topic->cover_path }}" alt="">
        @endif
        <nav aria-label="سطح درس">
            <p class="fe-muted"><x-fe-icon name="gauge" size="16" />سطح:</p>
            <div class="fe-pills">
                @foreach ($availableLevels as $level)
                    <a class="fe-level-link"
                       href="{{ route('reader.show', ['topic' => $topic->slug, 'level' => $level]) }}"
                       wire:navigate
                       @if ($level === $lesson->level) aria-current="page" @endif><span lang="en" dir="ltr">{{ $level }}</span></a>
                @endforeach
            </div>
        </nav>
    </header>

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
         data-cover-url="{{ $topic->cover_path ? '/storage/'.$topic->cover_path : '/icons/icon-192.png' }}"
         data-authenticated="{{ auth()->check() ? '1' : '0' }}"
         data-completed="{{ ! empty($completedAt) ? '1' : '0' }}"
         hidden></div>

    @if (! empty($initialPosition) && $initialPosition > 1 && empty($completedAt))
        <p class="fe-notice" role="status"><x-fe-icon name="headphones" size="20" />ادامه از موقعیت ذخیره‌شده</p>
    @endif
    @if (! empty($completedAt))
        <p class="fe-success-note" role="status"><x-fe-icon name="circle-check" size="20" />این درس تکمیل شده است</p>
    @endif

    @auth
    <div class="fe-reader-actions">
        <button id="bookmark-toggle" class="fe-btn" type="button"
                data-topic-id="{{ $topic->id }}"
                data-bookmarked="{{ ! empty($bookmarked) ? '1' : '0' }}"
                aria-pressed="{{ ! empty($bookmarked) ? 'true' : 'false' }}">
            <x-fe-icon name="bookmark" size="20" /><span>{{ ! empty($bookmarked) ? 'حذف از ذخیره‌شده‌ها' : 'ذخیره برای بعد' }}</span>
        </button>
        @if (empty($completedAt))
            <button id="mark-read" class="fe-btn" type="button" data-lesson-id="{{ $lesson->id }}"><x-fe-icon name="check" size="20" />خواندم</button>
        @else
            <button id="reset-progress" class="fe-btn" type="button" data-lesson-id="{{ $lesson->id }}"><x-fe-icon name="rotate-ccw" size="20" />شروع دوباره</button>
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
        <p class="fe-notice" role="note"><x-fe-icon name="info" size="20" />همگام‌سازی جمله‌به‌جمله برای این نسخه هنوز آماده نیست؛ پخش عادی فعال است و درس بدون مشکل کامل می‌شود.</p>
    @endif

    {{-- Active lesson's glossary only. Every value is escaped; premium
         lessons never reach this view for ineligible readers (policy 403
         happens in the controller first). Each word can be saved into the
         personal vocabulary notebook. --}}
    @if (! empty($lesson->glossary))
        <section aria-label="واژه‌های کلیدی">
            <h2 class="fe-section-title"><x-fe-icon name="book-open" size="20" />واژه‌های کلیدی</h2>
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
                                        data-lesson-id="{{ $lesson->id }}"><x-fe-icon name="languages" size="20" />ذخیره واژه</button>
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
            <h2 class="fe-section-title"><x-fe-icon name="book-open" size="20" />واژه‌های کلیدی</h2>
            <p class="fe-muted" role="note">برای این درس واژه‌ای ثبت نشده است.</p>
        </section>
    @endif
</div>
@endsection
