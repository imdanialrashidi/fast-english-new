<?php

use App\Models\Lesson;
use App\Models\User;
use Database\Seeders\S1SampleSeeder;
use Livewire\Features\SupportAutoInjectedAssets\SupportAutoInjectedAssets;

// S1-1: anonymous sample journey. Each level view shows only that lesson's
// body text and audio source, with Persian RTL chrome + English LTR body.
beforeEach(function () {
    $this->seed(S1SampleSeeder::class);
    $this->a2 = Lesson::where('level', 'A2')
        ->whereHas('topic', fn ($q) => $q->where('slug', 'city-park'))
        ->firstOrFail();
    $this->b1 = Lesson::where('level', 'B1')
        ->whereHas('topic', fn ($q) => $q->where('slug', 'city-park'))
        ->firstOrFail();
});

test('anonymous visitor reads the A2 sample lesson with only its body and audio', function () {
    $response = $this->get(route('reader.show', ['topic' => 'city-park', 'level' => 'A2']));

    $response->assertOk()
        ->assertSee('lang="fa" dir="rtl"', false)
        ->assertSee('lang="en" dir="ltr"', false);

    $a2Audio = route('media.lesson.audio', $this->a2);
    $response->assertSee('On Saturday morning, Sara walks');
    $response->assertSee($a2Audio, false);

    // The B1 lesson's body and audio are absent: no cross-level mixing.
    $response->assertDontSee('Sara loves Saturday mornings');
    $response->assertDontSee(route('media.lesson.audio', $this->b1), false);
});

test('anonymous visitor reads the B1 sample lesson with only its body and audio', function () {
    $response = $this->get(route('reader.show', ['topic' => 'city-park', 'level' => 'B1']));

    $response->assertOk();
    $response->assertSee('Sara loves Saturday mornings');
    $response->assertSee(route('media.lesson.audio', $this->b1), false);

    $response->assertDontSee('On Saturday morning, Sara walks');
    $response->assertDontSee(route('media.lesson.audio', $this->a2), false);
});

test('sample audio bytes differ per level so a mix-up is detectable', function () {
    $a2 = $this->get(route('media.lesson.audio', $this->a2));
    $b1 = $this->get(route('media.lesson.audio', $this->b1));

    $a2->assertOk();
    $b1->assertOk();

    expect($a2->headers->get('Content-Length'))
        ->not->toBe($b1->headers->get('Content-Length'));
});

test('learner layout loads no Filament assets and no second Alpine copy', function () {
    // S4 adaptation: the learner layout now owns the persistent player via
    // Livewire navigate (@persist + wire:navigate), so `livewire` is
    // expected in the HTML and blade sources. Filament and a second Alpine
    // copy stay forbidden: Alpine arrives only once via the Livewire
    // bundle, never via CDN or a duplicate script.
    if (class_exists(SupportAutoInjectedAssets::class)) {
        SupportAutoInjectedAssets::$hasRenderedAComponentThisRequest = false;
        SupportAutoInjectedAssets::$forceAssetInjection = false;
    }

    $html = $this->get(route('reader.show', ['topic' => 'city-park', 'level' => 'A2']))
        ->assertOk()
        ->getContent();

    expect($html)->not->toContain('filament')
        ->and($html)->not->toContain('alpinejs');

    // The persistent player is present exactly once in the layout.
    expect(substr_count($html, 'id="lesson-audio"'))->toBe(1);
    expect($html)->toContain('persist');

    // Structural backstop: none of the learner/error views reference
    // Filament or a second Alpine at the source level.
    $views = array_merge(
        glob(resource_path('views/layouts/learner*.blade.php')) ?: [],
        glob(resource_path('views/reader/*.blade.php')) ?: [],
        glob(resource_path('views/errors/*.blade.php')) ?: []
    );
    expect($views)->not->toBeEmpty();
    foreach ($views as $view) {
        $source = strtolower((string) file_get_contents($view));
        expect($source)->not->toContain('filament')
            ->and($source)->not->toContain('alpine');
    }
});

test('two users see the same public sample without leaking each other', function () {
    User::factory()->create(['email' => 'student-a@example.com']);
    User::factory()->create(['email' => 'student-b@example.com']);

    $this->actingAs(User::where('email', 'student-a@example.com')->first())
        ->get(route('reader.show', ['topic' => 'city-park', 'level' => 'A2']))
        ->assertOk()
        ->assertSee('On Saturday morning, Sara walks')
        ->assertDontSee('student-b@example.com');
});
