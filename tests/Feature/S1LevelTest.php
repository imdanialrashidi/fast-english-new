<?php

use Database\Seeders\S1SampleSeeder;

// S1-3: an unavailable level shows the exact message with the available
// levels and substitutes no other level.
beforeEach(function () {
    $this->seed(S1SampleSeeder::class);
});

test('requesting a missing B2 level shows the exact message and available levels', function () {
    $response = $this->get(route('reader.show', ['topic' => 'city-park', 'level' => 'B2']));

    $response->assertOk();
    $response->assertSee('این سطح هنوز آماده نیست', false);
    $response->assertSee('A2', false);
    $response->assertSee('B1', false);

    // No other level is substituted: neither body nor audio appears.
    $response->assertDontSee('On Saturday morning, Sara walks');
    $response->assertDontSee('Sara loves Saturday mornings');
    $response->assertDontSee('/media/lessons/', false);
});

test('lowercase level input is normalized instead of reported missing', function () {
    $this->get(route('reader.show', ['topic' => 'city-park', 'level' => 'b1']))
        ->assertOk()
        ->assertSee('Sara loves Saturday mornings');
});
