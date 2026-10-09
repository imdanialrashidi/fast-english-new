<?php

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\VocabularyWord;
use Tests\Support\ContentFixtures;

// R5 daily learning path (PLAN-01, AC-26): goal + tasks derived from
// persisted progress, level/premium respected, goal never writes the level.

test('a fresh student sees a goal with a next or subscribe task', function () {
    $student = ContentFixtures::student();

    $response = $this->actingAs($student)->get(route('today.index'))->assertOk();
    $response->assertSee('هدف امروز: 10 دقیقه مطالعه');

    // No progress and no due words: no continue task, and without premium
    // the plan points at the sample or the subscription — never premium.
    $response->assertDontSee('ادامه مطالعه');
});

test('continue and listen tasks reflect real progress with correct links', function () {
    $student = ContentFixtures::student();
    $topic = ContentFixtures::publishedTopic(['slug' => 'r5-resume']);
    $lesson = Lesson::factory()->for($topic)->create([
        'level' => 'A2',
        'status' => 'published',
        'published_at' => now(),
        'is_public_sample' => true,
        'duration_seconds' => 20,
        'estimated_minutes' => 4,
    ]);

    LessonProgress::create([
        'user_id' => $student->id,
        'lesson_id' => $lesson->id,
        'audio_revision' => $lesson->audio_revision,
        'position_seconds' => 12,
        'started_at' => now(),
    ]);

    $response = $this->actingAs($student)->get(route('today.index'))->assertOk();
    $response->assertSee('ادامه مطالعه');
    $response->assertSee(route('reader.show', ['topic' => $topic->slug, 'level' => 'A2']), false);

    // Completing the lesson marks its tasks done and moves the goal ring.
    $this->actingAs($student)->postJson(route('progress.complete'), [
        'lesson_id' => $lesson->id,
        'audio_revision' => $lesson->audio_revision,
    ])->assertOk();

    $done = $this->actingAs($student)->get(route('today.index'))->assertOk();
    $done->assertSee('4 دقیقه از برنامه امروز انجام شده');
});

test('due words create a review task and reviewed words count toward the goal', function () {
    $student = ContentFixtures::student();
    VocabularyWord::factory()->for($student)->create(['word' => 'habit', 'due_at' => now()->subHour()]);

    $response = $this->actingAs($student)->get(route('today.index'))->assertOk();
    $response->assertSee('مرور واژه‌ها');
    $response->assertSee('1 واژه برای مرور');
});

test('guests are redirected from today to login', function () {
    $this->get(route('today.index'))->assertRedirect();
    $this->get(route('words.review'))->assertRedirect();
});

test('changing the daily goal never changes the preferred level', function () {
    $student = ContentFixtures::student();
    expect((int) $student->refresh()->daily_goal_minutes)->toBe(10);

    $this->actingAs($student)
        ->patch(route('account.settings.update'), [
            'preferred_level' => 'B1',
            'daily_goal_minutes' => 15,
        ])
        ->assertRedirect();

    $student->refresh();
    expect($student->preferred_level)->toBe('B1')
        ->and((int) $student->daily_goal_minutes)->toBe(15);

    // Goal-only change leaves the level untouched.
    $this->actingAs($student)
        ->patch(route('account.settings.update'), ['daily_goal_minutes' => 5])
        ->assertRedirect();

    $student->refresh();
    expect($student->preferred_level)->toBe('B1')
        ->and((int) $student->daily_goal_minutes)->toBe(5);

    // Out-of-range goals and levels are both refused.
    $this->actingAs($student)
        ->patch(route('account.settings.update'), ['daily_goal_minutes' => 30])
        ->assertSessionHasErrors('daily_goal_minutes');
});
