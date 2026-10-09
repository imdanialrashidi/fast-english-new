<?php

use App\Models\Lesson;
use App\Models\PaymentRequest;
use App\Models\Plan;
use Database\Seeders\S1SampleSeeder;
use Database\Seeders\S5PaymentFixtureSeeder;
use Illuminate\Http\UploadedFile;
use Tests\Support\ContentFixtures;

// S5-3 (no access): a pending request grants nothing. A premium lesson
// page stays denied and the audio route stays 403 for a student with a
// pending request. Two users + negative path per protected route.

beforeEach(function () {
    $this->seed(S1SampleSeeder::class);
    $this->seed(S5PaymentFixtureSeeder::class);
    $this->studentA = ContentFixtures::student();
    $this->studentB = ContentFixtures::student();
    $this->plan = Plan::where('slug', 's5-test-30')->firstOrFail();
    $this->premium = Lesson::whereHas('topic', fn ($q) => $q->where('slug', 'night-trains'))
        ->where('level', 'B1')
        ->firstOrFail();
});

function s5PendingRequest($test, $user, $plan): PaymentRequest
{
    $test->actingAs($user)->post(route('payments.store'), ['plan_id' => $plan->id]);
    $row = PaymentRequest::where('user_id', $user->id)->firstOrFail();
    $test->actingAs($user)->post(route('payments.receipt.store', $row), [
        'receipt' => UploadedFile::fake()->createWithContent(
            'receipt.jpg',
            (string) file_get_contents(database_path('seeders/fixtures/s3-cover-fixture.jpg'))
        ),
    ]);

    return $row->fresh();
}

test('a pending request grants no premium page or audio access', function () {
    $pending = s5PendingRequest($this, $this->studentA, $this->plan);
    expect($pending->status)->toBe(PaymentRequest::STATUS_PENDING);

    // Premium reader stays denied; the body never leaks.
    $page = $this->actingAs($this->studentA)
        ->get(route('reader.show', ['topic' => 'night-trains', 'level' => 'B1']));
    $page->assertForbidden();
    expect($page->getContent() ?? '')->not->toContain('Night Trains');

    // Premium audio stays 403.
    $this->actingAs($this->studentA)
        ->get(route('media.lesson.audio', $this->premium))
        ->assertForbidden();
});

test('an awaiting_receipt request also grants nothing, and a second student is isolated', function () {
    $this->actingAs($this->studentA)->post(route('payments.store'), ['plan_id' => $this->plan->id]);

    $this->actingAs($this->studentA)
        ->get(route('reader.show', ['topic' => 'night-trains', 'level' => 'B1']))
        ->assertForbidden();
    $this->actingAs($this->studentB)
        ->get(route('reader.show', ['topic' => 'night-trains', 'level' => 'B1']))
        ->assertForbidden();

    // Guests are denied on the same routes (negative path).
    $this->get(route('reader.show', ['topic' => 'night-trains', 'level' => 'B1']))->assertForbidden();
    $this->get(route('media.lesson.audio', $this->premium))->assertForbidden();
});
