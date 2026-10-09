<?php

use App\Livewire\UpdateDisplayName;
use App\Models\User;
use Livewire\Livewire;

// S0-3: valid name is stored server-side and survives a full reload.
test('logged-in user can save a display name and still sees it after a full reload', function () {
    $user = User::factory()->create(['name' => 'Old Name']);
    $other = User::factory()->create(['name' => 'Other Name']);
    $this->actingAs($user);

    Livewire::test(UpdateDisplayName::class)
        ->set('name', 'New Name')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('saved', true);

    expect($user->fresh()->name)->toBe('New Name');
    // The other user's row is untouched.
    expect($other->fresh()->name)->toBe('Other Name');

    // Full HTTP reload of the page shows the stored value.
    $this->get(route('account'))->assertOk()->assertSee('New Name');
});

// S0-3 negative path: empty name fails, saves nothing, shows no success.
test('empty display name shows a field error, saves nothing, and shows no success message', function () {
    $user = User::factory()->create(['name' => 'Kept Name']);
    $this->actingAs($user);

    Livewire::test(UpdateDisplayName::class)
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name'])
        ->assertSet('saved', false);

    expect($user->fresh()->name)->toBe('Kept Name');

    $this->get(route('account'))->assertOk()->assertSee('Kept Name');
});
