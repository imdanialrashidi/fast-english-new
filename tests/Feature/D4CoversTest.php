<?php

// D4 covers (owner direction 2026-10-10): every published topic shows a
// real cover, never an empty placeholder. Default is a GD abstract,
// deterministic from the slug, with no baked text. Staff uploads
// (JPEG/PNG/WebP) each need a license record (source + license name +
// date checked + usage notes); EXIF is stripped via re-encode. No image
// is fetched from a third-party API at runtime. PIL placeholders are gone.

use App\Actions\PublishLesson;
use App\Models\Topic;
use App\Support\TopicCover;
use App\Support\TopicLicense;
use Database\Seeders\S1SampleSeeder;
use Database\Seeders\S3SampleSeeder;
use Database\Seeders\S4PaginationSeeder;
use Illuminate\Support\Facades\Storage;

test('gd covers are deterministic, imageless-text, and runtime-fetch-free', function () {
    $a1 = TopicCover::generate('d4-determinism-check');
    $a2 = TopicCover::generate('d4-determinism-check');
    $b = TopicCover::generate('d4-other-slug');

    expect($a1)->toBe($a2);
    expect($a1)->toStartWith('covers/gd-');

    $disk = Storage::disk('public');
    expect($disk->exists($a1))->toBeTrue();
    expect($disk->exists($b))->toBeTrue();
    expect(md5((string) $disk->get($a1)))->toBe(md5((string) $disk->get($a2)));
    expect(md5((string) $disk->get($a1)))->not->toBe(md5((string) $disk->get($b)));

    $size = getimagesize($disk->path($a1));
    expect($size[0])->toBe(800);
    expect($size[1])->toBe(450);
    expect($size['mime'])->toBe('image/jpeg');

    // No EXIF survives the GD pipeline (generated images carry no camera/
    // GPS tags; exif_read_data still reports FILE/COMPUTED for any JPEG).
    $exif = @exif_read_data($disk->path($a1));
    if (is_array($exif)) {
        foreach (['EXIF', 'GPS', 'IFD0', 'IFD1', 'INTEROP'] as $section) {
            expect($exif)->not->toHaveKey($section);
        }
    } else {
        expect($exif)->toBeFalse();
    }

    // No baked text by construction: the generator never calls text APIs.
    $src = (string) file_get_contents(app_path('Support/TopicCover.php'));
    expect($src)->not->toContain('imagestring');
    expect($src)->not->toContain('imagettftext');
    expect($src)->not->toContain('imagettfbbox');

    // No runtime third-party fetch in the cover pipeline.
    foreach ([app_path('Support/TopicCover.php'), app_path('Support/CoverImage.php')] as $file) {
        $code = (string) file_get_contents($file);
        expect($code)->not->toContain('https://');
        expect($code)->not->toContain('http://');
        expect($code)->not->toContain('curl_');
    }
});

test('every published fixture topic carries a real cover file', function () {
    $this->seed(S1SampleSeeder::class);
    $this->seed(S3SampleSeeder::class);
    $this->seed(S4PaginationSeeder::class);

    $published = Topic::where('status', 'published')->get();
    expect($published->count())->toBeGreaterThan(0);

    $disk = Storage::disk('public');
    foreach ($published as $topic) {
        expect(trim((string) $topic->cover_path))->not->toBe('', "topic {$topic->slug} has no cover");
        expect($disk->exists((string) $topic->cover_path))->toBeTrue("missing file for {$topic->slug}");
        $info = @getimagesize($disk->path((string) $topic->cover_path));
        expect($info)->not->toBeFalse("unreadable image for {$topic->slug}");
    }
});

test('staff covers need a license record; gd defaults are exempt', function () {
    // Staff upload without any record is refused at the publish gate.
    $staff = Topic::factory()->create([
        'slug' => 'd4-staff-no-license',
        'status' => 'draft',
        'cover_path' => TopicCover::generate('d4-staff-no-license-src'),
        'source_note' => null,
    ]);
    // Move the file outside the gd- namespace to simulate a staff upload.
    $disk = Storage::disk('public');
    $staffPath = 'covers/d4-staff-upload.jpg';
    $disk->copy((string) $staff->cover_path, $staffPath);
    $staff->forceFill(['cover_path' => $staffPath, 'source_note' => null])->save();

    $problems = PublishLesson::topicProblems($staff->fresh());
    expect($problems)->toHaveKey('cover_path');

    // Four-line record passes.
    $staff->forceFill(['source_note' => "Source: owner archive\nLicense: CC-BY 4.0\nChecked: 2026-10-01\nNotes: hero for {$staff->slug}"])->save();
    expect(PublishLesson::topicProblems($staff->fresh()))->not->toHaveKey('cover_path');

    // Bad date is refused.
    $staff->forceFill(['source_note' => "Source: x\nLicense: y\nChecked: 2026-13-99\nNotes: z"])->save();
    expect(PublishLesson::topicProblems($staff->fresh()))->toHaveKey('cover_path');

    // GD defaults never need the staff record.
    $gd = Topic::factory()->create([
        'slug' => 'd4-gd-exempt',
        'status' => 'draft',
        'cover_path' => TopicCover::generate('d4-gd-exempt'),
        'source_note' => null,
    ]);
    expect(TopicLicense::problem($gd->cover_path, $gd->source_note))->toBeNull();
    expect(PublishLesson::topicProblems($gd->fresh()))->not->toHaveKey('cover_path');
});

test('pil placeholders are no longer referenced by seeders', function () {
    $seeders = '';
    foreach (glob(database_path('seeders/*.php')) ?: [] as $file) {
        $seeders .= (string) file_get_contents($file);
    }
    expect($seeders)->not->toContain('PIL-made');
    expect($seeders)->not->toContain('PIL placeholders');
    expect($seeders)->not->toContain('s3-cover-fixture');
});

test('gd fallback has six deterministic layout variants', function () {
    expect(TopicCover::VARIANTS)->toBe(6);

    // Same slug always maps to the same variant; variants stay in range.
    expect(TopicCover::variant('morning-market'))->toBe(TopicCover::variant('morning-market'));
    expect(TopicCover::variant('morning-market'))->toBeGreaterThanOrEqual(0)->toBeLessThan(6);

    // Brute-force a small deterministic set: all six layouts must appear
    // so the library never looks like one repeated blob.
    $seen = [];
    for ($i = 0; $i < 60; $i++) {
        $seen[TopicCover::variant("d4-variant-{$i}")] = true;
    }
    expect(array_keys($seen))->toHaveCount(6);

    // Each variant renders a distinct, valid 800x450 cover.
    $hashes = [];
    $representative = [];
    for ($i = 0; $i < 60 && count($representative) < 6; $i++) {
        $v = TopicCover::variant("d4-variant-{$i}");
        if (! isset($representative[$v])) {
            $representative[$v] = "d4-variant-{$i}";
        }
    }
    $disk = Storage::disk('public');
    foreach ($representative as $slug) {
        $path = TopicCover::generate($slug);
        expect($disk->exists($path))->toBeTrue();
        $size = getimagesize($disk->path($path));
        expect($size[0])->toBe(800)->and($size[1])->toBe(450);
        $hashes[] = md5((string) $disk->get($path));
    }
    expect(array_unique($hashes))->toHaveCount(6);
});
