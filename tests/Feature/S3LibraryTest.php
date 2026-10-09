<?php

use App\Models\Lesson;
use Illuminate\Support\Facades\DB;
use Tests\Support\ContentFixtures;

// S3-3: one card per topic. A topic with two published lessons renders a
// single card listing both levels; pagination returns 12 per page with the
// correct total; the query count stays constant as topics grow.

test('a topic with published A1 and B2 lessons renders one card with both levels', function () {
    $topic = ContentFixtures::publishedTopic(['slug' => 's3-two-level', 'title_en' => 'S3 Two Level']);
    Lesson::factory()->for($topic)->create([
        'level' => 'A1', 'status' => 'published', 'published_at' => now(), 'is_public_sample' => true,
    ]);
    Lesson::factory()->for($topic)->create([
        'level' => 'B2', 'status' => 'published', 'published_at' => now(), 'is_public_sample' => false,
    ]);

    $html = $this->get('/app')->assertOk()->getContent();

    // Exactly one card links to the topic …
    expect(substr_count($html, '/app/topics/s3-two-level'))->toBe(1);

    // … and it names both available levels.
    expect($html)->toContain('A1')->toContain('B2');
});

test('pagination returns 12 per page with the correct total', function () {
    for ($i = 1; $i <= 13; $i++) {
        $topic = ContentFixtures::publishedTopic([
            'slug' => "s3-page-{$i}", 'title_en' => "S3 Page {$i}",
        ]);
        Lesson::factory()->for($topic)->create([
            'level' => 'A1', 'status' => 'published', 'published_at' => now(),
        ]);
    }

    $page1 = $this->get('/app')->assertOk();
    $page1->assertSee('13 مطلب');
    expect(substr_count($page1->getContent(), '<article class="fe-card">'))->toBe(12);

    $page2 = $this->get('/app?page=2')->assertOk();
    expect(substr_count($page2->getContent(), '<article class="fe-card">'))->toBe(1);
});

test('the library query count stays constant as topics grow (no N+1)', function () {
    $build = function (int $count, string $prefix): void {
        for ($i = 1; $i <= $count; $i++) {
            $topic = ContentFixtures::publishedTopic([
                'slug' => "{$prefix}-{$i}", 'title_en' => "Topic {$prefix} {$i}",
            ]);
            foreach (['A1', 'B1'] as $level) {
                Lesson::factory()->for($topic)->create([
                    'level' => $level, 'status' => 'published', 'published_at' => now(),
                ]);
            }
        }
    };

    $build(6, 's3-small');

    DB::enableQueryLog();
    DB::flushQueryLog();
    $this->get('/app')->assertOk();
    $small = count(DB::getQueryLog());

    $build(12, 's3-large');

    DB::flushQueryLog();
    $this->get('/app')->assertOk();
    $large = count(DB::getQueryLog());
    DB::disableQueryLog();

    // 6 topics (12 lessons) vs 18 topics (36 lessons): identical budgets.
    expect($large)->toBe($small)->and($small)->toBeLessThanOrEqual(8);
});
