<?php

// S3-8 evidence: every learner text pair reuses the DESIGN.md tokens at or
// above 4.5:1 (WCAG 2.2 AA normal text). No new colors were introduced for
// the library — this test parses the canonical token source
// (resources/css/app.css :root --fe-*), recomputes relative luminance, and
// locks the pairs. Measured values are recorded in the active exec plan.

function s3Luminance(string $hex): float
{
    $hex = ltrim($hex, '#');
    $channels = array_map(
        fn ($c) => hexdec($c) / 255,
        [substr($hex, 0, 2), substr($hex, 2, 2), substr($hex, 4, 2)]
    );

    $linear = array_map(
        fn ($c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4,
        $channels
    );

    return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
}

function s3Contrast(string $a, string $b): float
{
    $lighter = max(s3Luminance($a), s3Luminance($b));
    $darker = min(s3Luminance($a), s3Luminance($b));

    return round(($lighter + 0.05) / ($darker + 0.05), 2);
}

test('learner text pairs measure at or above 4.5 to 1', function () {
    $css = (string) file_get_contents(resource_path('css/app.css'));

    preg_match_all('/--fe-([a-z-]+):\s*(#[0-9a-fA-F]{6})/', $css, $matches, PREG_SET_ORDER);
    $tokens = [];
    foreach ($matches as $match) {
        $tokens[$match[1]] = strtolower($match[2]);
    }

    $pairs = [
        'text on canvas' => ['text', 'canvas'],
        'muted text on canvas' => ['muted-text', 'canvas'],
        'text on surface' => ['text', 'surface'],
        'muted text on surface' => ['muted-text', 'surface'],
        'primary on surface' => ['primary', 'surface'],
        'on-primary on primary' => ['on-primary', 'primary'],
        'success on surface' => ['success', 'surface'],
        'danger on surface' => ['danger', 'surface'],
    ];

    foreach ($pairs as $label => [$fg, $bg]) {
        expect($tokens)->toHaveKey($fg)->toHaveKey($bg);
        $ratio = s3Contrast($tokens[$fg], $tokens[$bg]);
        fwrite(STDERR, "\n[S3 contrast] {$label}: {$ratio}:1");
        expect($ratio)->toBeGreaterThanOrEqual(4.5, "{$label} measured {$ratio}:1");
    }
});

test('no color literal lives outside the token block', function () {
    $css = (string) file_get_contents(resource_path('css/app.css'));

    $rootBlock = '';
    if (preg_match('/:root\s*\{(.*?)\}/s', $css, $match)) {
        $rootBlock = $match[0];
    }

    $rest = str_replace($rootBlock, '', $css);
    $rest = (string) preg_replace('/\/\*.*?\*\//s', '', $rest);

    expect($rest)->not->toMatch('/#[0-9a-fA-F]{3,8}\b/');
});
