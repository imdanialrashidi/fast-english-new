<?php

use App\Models\Lesson;
use App\Models\Topic;
use App\Models\User;
use Database\Seeders\S1SampleSeeder;

// S1-2: premium gating for anonymous + logged-in non-staff users, with a
// negative path for every protected route and staff allowed through.
beforeEach(function () {
    $this->seed(S1SampleSeeder::class);
    $this->student = User::factory()->create(['is_staff' => false]);
    $this->staff = User::factory()->create(['is_staff' => true]);
});

test('anonymous visitor is denied the premium page without its body text', function () {
    $response = $this->get(route('reader.show', ['topic' => 'night-trains', 'level' => 'B1']));

    $response->assertForbidden();
    $response->assertDontSee('The night train left the station');
});

test('logged-in non-staff user is denied the premium page without its body text', function () {
    $response = $this->actingAs($this->student)
        ->get(route('reader.show', ['topic' => 'night-trains', 'level' => 'B1']));

    $response->assertForbidden();
    $response->assertDontSee('The night train left the station');
});

test('anonymous visitor gets 403 from the premium audio route', function () {
    $premium = Lesson::whereHas('topic', fn ($q) => $q->where('slug', 'night-trains'))->firstOrFail();

    $this->get(route('media.lesson.audio', $premium))->assertForbidden();
});

test('logged-in non-staff user gets 403 from the premium audio route', function () {
    $premium = Lesson::whereHas('topic', fn ($q) => $q->where('slug', 'night-trains'))->firstOrFail();

    $this->actingAs($this->student)
        ->get(route('media.lesson.audio', $premium))
        ->assertForbidden();
});

test('staff can read the premium page and fetch its audio', function () {
    $premium = Lesson::whereHas('topic', fn ($q) => $q->where('slug', 'night-trains'))->firstOrFail();

    $this->actingAs($this->staff)
        ->get(route('reader.show', ['topic' => 'night-trains', 'level' => 'B1']))
        ->assertOk()
        ->assertSee('The night train left the station');

    $this->actingAs($this->staff)
        ->get(route('media.lesson.audio', $premium))
        ->assertOk();
});

test('draft topic returns 404 to anonymous and logged-in users', function () {
    $topic = Topic::factory()->create(['slug' => 'draft-topic', 'status' => 'draft', 'published_at' => null]);
    Lesson::factory()->for($topic)->create(['level' => 'A2', 'status' => 'published']);

    $this->get(route('reader.show', ['topic' => 'draft-topic', 'level' => 'A2']))->assertNotFound();
    $this->actingAs($this->student)
        ->get(route('reader.show', ['topic' => 'draft-topic', 'level' => 'A2']))
        ->assertNotFound();
    $this->actingAs($this->staff)
        ->get(route('reader.show', ['topic' => 'draft-topic', 'level' => 'A2']))
        ->assertNotFound();
});

test('draft lesson returns 404 to everyone including staff', function () {
    $topic = Topic::factory()->create(['slug' => 'mixed-topic', 'status' => 'published']);
    $draft = Lesson::factory()->for($topic)->create([
        'level' => 'B2',
        'status' => 'draft',
        'is_public_sample' => true,
    ]);

    $this->get(route('reader.show', ['topic' => 'mixed-topic', 'level' => 'B2']))->assertNotFound();
    $this->actingAs($this->student)
        ->get(route('reader.show', ['topic' => 'mixed-topic', 'level' => 'B2']))
        ->assertNotFound();
    $this->actingAs($this->staff)
        ->get(route('reader.show', ['topic' => 'mixed-topic', 'level' => 'B2']))
        ->assertNotFound();
    $this->get(route('media.lesson.audio', $draft))->assertNotFound();
    $this->actingAs($this->staff)->get(route('media.lesson.audio', $draft))->assertNotFound();
});

test('archived lesson returns 404 to everyone', function () {
    $topic = Topic::factory()->create(['slug' => 'arch-topic', 'status' => 'published']);
    $archived = Lesson::factory()->for($topic)->create([
        'level' => 'A2',
        'status' => 'archived',
        'is_public_sample' => true,
    ]);

    $this->get(route('reader.show', ['topic' => 'arch-topic', 'level' => 'A2']))->assertNotFound();
    $this->actingAs($this->staff)->get(route('media.lesson.audio', $archived))->assertNotFound();
});
