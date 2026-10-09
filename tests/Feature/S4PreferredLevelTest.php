<?php

use Database\Seeders\S1SampleSeeder;
use Tests\Support\ContentFixtures;

// S4-5 preferred level (LEVEL-01): explicit settings writes only. The reader
// URL never changes it, the unavailable-level message still holds, and no
// silent substitution happens. Two users, negative paths included.

beforeEach(function () {
    $this->seed(S1SampleSeeder::class);
    $this->studentA = ContentFixtures::student();
    $this->studentB = ContentFixtures::student();
});

test('preferred level changes only through the settings page', function () {
    $this->actingAs($this->studentA)
        ->patch(route('account.settings.update'), ['preferred_level' => 'B1'])
        ->assertRedirect(route('account.settings'));

    expect($this->studentA->fresh()->preferred_level)->toBe('B1');
    expect($this->studentB->fresh()->preferred_level)->toBeNull();
});

test('the reader URL level does not change the preferred level', function () {
    $this->actingAs($this->studentA)
        ->patch(route('account.settings.update'), ['preferred_level' => 'A2'])
        ->assertRedirect();

    $this->actingAs($this->studentA)
        ->get(route('reader.show', ['topic' => 'city-park', 'level' => 'B1']))
        ->assertOk();

    expect($this->studentA->fresh()->preferred_level)->toBe('A2');
});

test('an invalid preferred level is refused and nothing is stored', function () {
    $this->actingAs($this->studentA)
        ->patch(route('account.settings.update'), ['preferred_level' => 'Z9'])
        ->assertSessionHasErrors('preferred_level');

    expect($this->studentA->fresh()->preferred_level)->toBeNull();
});

test('the unavailable-level message and no-substitution rule still hold', function () {
    $this->actingAs($this->studentA)
        ->patch(route('account.settings.update'), ['preferred_level' => 'B1'])
        ->assertRedirect();

    // city-park has no C1: the exact message appears with A2/B1 links, and
    // no body or audio from another level leaks in.
    $response = $this->actingAs($this->studentA)
        ->get(route('reader.show', ['topic' => 'city-park', 'level' => 'C1']))
        ->assertOk();

    $response->assertSee('این سطح هنوز آماده نیست', false);
    expect($response->getContent())->not->toContain('On Saturday morning, Sara walks');
    expect($response->getContent())->not->toContain('/media/lessons/');
});

test('guests cannot open the settings page', function () {
    $this->get(route('account.settings'))->assertRedirect();
    $this->patch(route('account.settings.update'), ['preferred_level' => 'B1'])
        ->assertRedirect();
});
