<?php

use App\Models\User;

// S3 pre-slice correction (scope §16): five upload requests per minute per
// user on every upload route. The sixth request in the window is 429.

test('the sixth upload request in a minute returns 429', function () {
    $user = User::factory()->create(['email' => 'upload-throttle-a@example.com']);
    User::factory()->create(['email' => 'upload-throttle-b@example.com']);

    $url = route('livewire.upload-file');

    for ($i = 0; $i < 5; $i++) {
        $response = $this->actingAs($user)->post($url, []);
        // Unsigned posts are refused by Livewire (401), but they still count
        // toward the throttle: the point is the sixth request is 429, not
        // which error the first five carry.
        expect($response->status())->toBeIn([401, 419, 422]);
    }

    $this->actingAs($user)->post($url, [])->assertStatus(429);
});

test('the upload throttle is per user, not global', function () {
    $first = User::factory()->create(['email' => 'upload-throttle-c@example.com']);
    $second = User::factory()->create(['email' => 'upload-throttle-d@example.com']);

    $url = route('livewire.upload-file');

    for ($i = 0; $i < 5; $i++) {
        $this->actingAs($first)->post($url, []);
    }
    $this->actingAs($first)->post($url, [])->assertStatus(429);

    // A different user on the same IP still has a fresh bucket.
    $response = $this->actingAs($second)->post($url, []);
    expect($response->status())->not->toBe(429);
});
