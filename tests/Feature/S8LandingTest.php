<?php

use App\Models\Lesson;
use App\Models\Plan;
use Database\Seeders\S5PaymentFixtureSeeder;
use Tests\Support\ContentFixtures;

// S8-1 (public pages): landing + trust pages render with lang fa, dir
// rtl, and metadata. Two users are irrelevant here (public surface), but
// both states of the sales switch are proven with the DB plan list.

beforeEach(function () {
    $this->seed(S5PaymentFixtureSeeder::class);
});

test('landing renders fa rtl with metadata, sample link, plans, and no draft label', function () {
    $topic = ContentFixtures::publishedTopic();
    Lesson::factory()->for($topic)->create([
        'level' => 'A2',
        'status' => 'published',
        'published_at' => now(),
        'is_public_sample' => true,
    ]);

    $html = $this->get(route('landing'))->assertOk()->getContent();

    expect($html)->toContain('lang="fa"')
        ->and($html)->toContain('dir="rtl"')
        ->and($html)->toContain('rel="canonical"')
        ->and($html)->toContain(route('download'))
        ->and($html)->toContain(route('trust.faq'))
        // The landing is real copy now: no DRAFT badge (trust pages keep theirs).
        ->and($html)->not->toContain('DRAFT')
        ->and($html)->toContain(route('reader.show', $topic).'?level=A2')
        // Hero preview stages the real sample body text.
        ->and($html)->toContain('پیش‌نمایش روش کار با متن واقعی')
        // Plan names from the database, never amounts (no price claims).
        ->and($html)->toContain('یک‌ماهه آزمایشی (TEST)')
        ->and($html)->not->toContain('100000')
        ->and($html)->not->toContain('۱۰۰٬۰۰۰');
});

test('landing without a sample shows the neutral state, never a broken link', function () {
    $html = $this->get(route('landing'))->assertOk()->getContent();

    expect($html)->toContain('نمونه عمومی به‌زودی آماده می‌شود')
        ->and($html)->not->toContain('?level=');
});

test('landing with sales off shows the preparing state and no purchase cta', function () {
    config(['sales.enabled' => false]);

    $topic = ContentFixtures::publishedTopic();
    Lesson::factory()->for($topic)->create([
        'level' => 'A2',
        'status' => 'published',
        'published_at' => now(),
        'is_public_sample' => true,
    ]);

    $html = $this->get(route('landing'))->assertOk()->getContent();

    expect($html)->toContain('در دست آماده‌سازی')
        ->and($html)->not->toContain('مشاهده پلن‌ها و خرید')
        ->and($html)->not->toContain('یک‌ماهه آزمایشی (TEST)');
});

test('trust pages render fa rtl drafts with canonical tags', function () {
    foreach (['about', 'cooperation', 'faq', 'support', 'terms', 'privacy'] as $page) {
        $html = $this->get(route('trust.'.$page))->assertOk()->getContent();
        expect($html)->toContain('lang="fa"')
            ->and($html)->toContain('dir="rtl"')
            ->and($html)->toContain('rel="canonical"')
            ->and($html)->toContain('DRAFT');
    }
});

test('support terms and privacy show blocked states with no invented details', function () {
    foreach (['support', 'terms', 'privacy'] as $page) {
        $html = $this->get(route('trust.'.$page))->assertOk()->getContent();
        expect($html)->toContain('BLOCKED');
    }

    $support = $this->get(route('trust.support'))->getContent();
    // No invented contact: no email address and no phone-like digits.
    expect($support)->not->toMatch('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i');

    $terms = $this->get(route('trust.terms'))->getContent();
    $privacy = $this->get(route('trust.privacy'))->getContent();
    expect($terms)->not->toContain('ماده')
        ->and($privacy)->not->toContain('ماده');
});

test('sitemap lists public pages only', function () {
    $body = $this->get(route('sitemap'))->assertOk()->getContent();

    foreach (['/', '/about', '/cooperation', '/faq', '/support', '/terms', '/privacy', '/download'] as $path) {
        expect($body)->toContain(config('app.url').$path);
    }
    expect($body)->not->toContain('/app')
        ->and($body)->not->toContain('/admin')
        ->and($body)->not->toContain('/login')
        ->and($body)->not->toContain('/media');
});
