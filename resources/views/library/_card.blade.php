@props(['topic'])
@php
    $levels = $topic->lessons->pluck('level')->filter()->values();
    $firstLevel = $levels->first();
    $extraCount = max(0, $levels->count() - 1);
    $minutes = (int) $topic->lessons->sum('estimated_minutes');
    $levelsFa = $levels->implode('، ');
@endphp
<article class="fe-card fe-discover-card">
    <a class="fe-card-link" href="{{ route('reader.show', $topic) }}" wire:navigate aria-label="{{ $topic->title_en }}">
        <span class="fe-card-media">
            @if ($topic->cover_path)
                <img class="fe-card-cover" src="/storage/{{ $topic->cover_path }}" alt="" loading="lazy">
            @else
                <span class="fe-card-cover fe-card-cover-empty" aria-hidden="true"><img class="fe-cover-logo" src="/icons/icon-192.png" alt="" width="56" height="56" loading="lazy"></span>
            @endif
            @if ($firstLevel)
                <span class="fe-card-level-overlay" aria-hidden="true"><span class="fe-chip fe-chip-overlay" lang="en" dir="ltr">{{ $firstLevel }}{{ $extraCount > 0 ? ' · +'.$extraCount : '' }}</span></span>
            @endif
        </span>
        <div class="fe-card-body">
            <h2 class="fe-card-title" lang="en" dir="ltr">{{ $topic->title_en }}</h2>
            <p class="fe-card-meta"><x-fe-icon name="clock" size="16" />حدود {{ $minutes }} دقیقه مطالعه@if ($levelsFa !== '') — سطح‌ها: <span lang="en" dir="ltr">{{ $levelsFa }}</span>@endif</p>
            @if ($topic->relationLoaded('category') && $topic->category)
                <p class="fe-card-meta fe-card-category"><x-fe-icon name="book-open" size="16" />{{ $topic->category->name_fa }}</p>
            @endif
        </div>
    </a>
</article>
