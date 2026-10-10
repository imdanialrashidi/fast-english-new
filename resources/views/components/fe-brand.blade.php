@props(['height' => 36])

@php
    // Owner-supplied header lockup (Logo/fastenglish_header_logo.png for
    // light surfaces; generated dark variant for dark surfaces). One
    // component owns the theme switch so every shell shows the right art.
    // The wrapper carries the accessible name; both images are present but
    // CSS shows exactly one per data-theme (never both, never none).
    $height = max(20, min(72, (int) $height));
    $width = (int) round($height * 697 / 197);
@endphp
<span {{ $attributes->merge(['class' => 'fe-brand-logo']) }} role="img" aria-label="Fast English"><img
        class="fe-logo-img fe-logo-light" src="/images/brand/fastenglish-header-logo.png" alt="" width="{{ $width }}"
        height="{{ $height }}" loading="eager" fetchpriority="high"><img
        class="fe-logo-img fe-logo-dark" src="/images/brand/fastenglish-header-logo-dark.png" alt="" width="{{ $width }}"
        height="{{ $height }}" loading="eager" fetchpriority="high"></span>
