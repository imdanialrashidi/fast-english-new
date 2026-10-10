@if ($paginator->hasPages())
    <div class="fe-pagination-links" role="navigation" aria-label="صفحه‌بندی">
        @php
            $fa = fn ($n) => strtr((string) $n, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
        @endphp
        @if ($paginator->onFirstPage())
            <span class="fe-pagination-disabled" aria-disabled="true">قبلی</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="صفحه قبلی">قبلی</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="fe-pagination-dots" aria-hidden="true">…</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page" aria-label="صفحه {{ $fa($page) }}، صفحه جاری">{{ $fa($page) }}</span>
                    @else
                        <a href="{{ $url }}" aria-label="رفتن به صفحه {{ $fa($page) }}">{{ $fa($page) }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="صفحه بعدی">بعدی</a>
        @else
            <span class="fe-pagination-disabled" aria-disabled="true">بعدی</span>
        @endif
    </div>
@endif
