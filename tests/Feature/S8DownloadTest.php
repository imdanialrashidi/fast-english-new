<?php

// S8-5 (download): the not-yet-published state appears with no file,
// link, or checksum. The metadata template renders only from real values.

test('download shows not-published state with no file link or checksum', function () {
    config([
        'release.version_name' => null,
        'release.version_code' => null,
        'release.release_date' => null,
        'release.size_bytes' => null,
        'release.sha256' => null,
        'release.file_url' => null,
    ]);

    $html = $this->get(route('download'))->assertOk()->getContent();

    expect($html)->toContain('هنوز منتشر نشده')
        ->and($html)->toContain('lang="fa"')
        ->and($html)->toContain('dir="rtl"')
        ->and($html)->toContain('rel="canonical"');
    // No APK file, no link to one, no checksum hex anywhere. (The words
    // «نسخه»/«SHA-256» appear only as template labels, never as values.)
    expect($html)->not->toMatch('/\.apk/i')
        ->and($html)->not->toMatch('/\b[0-9a-f]{64}\b/i')
        ->and($html)->not->toMatch('/href="[^"]*\.(apk|aab)"/i');
});

test('download metadata template fills only from real values', function () {
    config([
        'release.version_name' => '1.0.0-test',
        'release.version_code' => '3',
        'release.release_date' => '2026-10-01',
        'release.size_bytes' => '12345678',
        'release.sha256' => str_repeat('a', 63).'b',
        'release.file_url' => '/releases/test-app.apk',
    ]);

    $html = $this->get(route('download'))->assertOk()->getContent();

    expect($html)->toContain('1.0.0-test')
        ->and($html)->toContain('12345678')
        ->and($html)->not->toContain('هنوز منتشر نشده');
});

test('download with partial metadata stays unpublished', function () {
    config([
        'release.version_name' => '1.0.0-test',
        'release.version_code' => null,
        'release.release_date' => null,
        'release.size_bytes' => null,
        'release.sha256' => null,
        'release.file_url' => null,
    ]);

    $html = $this->get(route('download'))->assertOk()->getContent();

    expect($html)->toContain('هنوز منتشر نشده')
        ->and($html)->not->toContain('1.0.0-test');
});
