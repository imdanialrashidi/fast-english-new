@props(['topic'])
<article class="fe-card">
    <a class="fe-card-link" href="{{ route('reader.show', $topic) }}" wire:navigate aria-label="{{ $topic->title_en }}">
        <span class="fe-card-media">
            @if ($topic->cover_path)
                <img class="fe-card-cover" src="/storage/{{ $topic->cover_path }}" alt="" loading="lazy">
            @else
                <span class="fe-card-cover fe-card-cover-empty" aria-hidden="true"><span lang="en" dir="ltr">FE</span></span>
            @endif
        </span>
        <div class="fe-card-body">
            <h2 class="fe-card-title" lang="en" dir="ltr">{{ $topic->title_en }}</h2>
            <p class="fe-card-levels">سطح‌ها: {{ $topic->lessons->pluck('level')->join('، ') }}</p>
            <p class="fe-muted">حدود {{ $topic->lessons->sum('estimated_minutes') }} دقیقه مطالعه</p>
        </div>
    </a>
</article>
