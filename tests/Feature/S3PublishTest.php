<?php

use App\Actions\PublishLesson;
use App\Models\Lesson;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Support\ContentFixtures;

// S3-2: publishing is an explicit, validated action. Every missing piece
// is refused with a specific error and nothing changes; a valid lesson
// publishes once, and a repeated publish is idempotent.

test('publish refuses a lesson whose audio file is missing', function () {
    $lesson = ContentFixtures::validDraftLesson();
    Storage::disk('local')->delete($lesson->audio_path);
    $before = $lesson->fresh()->getAttributes();

    try {
        PublishLesson::publish($lesson);
        $this->fail('Publishing without audio should have been refused.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('audio');
    }

    expect($lesson->fresh()->getAttributes())->toBe($before);
});

test('publish refuses a lesson with an invalid audio signature', function () {
    $lesson = ContentFixtures::validDraftLesson();
    file_put_contents(Storage::disk('local')->path($lesson->audio_path), 'not an mp3 at all');
    $before = $lesson->fresh()->getAttributes();

    try {
        PublishLesson::publish($lesson);
        $this->fail('Publishing with corrupt audio should have been refused.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('audio');
    }

    expect($lesson->fresh()->getAttributes())->toBe($before);
});

test('publish validation names a zero duration without touching storage', function () {
    // duration_seconds = 0 cannot be stored (CHECK constraint), so the
    // refusal is proven on an in-memory lesson — same validator, no write.
    $lesson = ContentFixtures::validDraftLesson();
    $lesson->duration_seconds = 0;

    $problems = PublishLesson::lessonProblems($lesson);

    expect($problems)->toHaveKey('duration_seconds');
    expect($lesson->fresh()->duration_seconds)->toBeGreaterThan(0);
});

test('publish refuses a lesson without a recorded review', function () {
    $lesson = ContentFixtures::validDraftLesson(['reviewed_by' => null, 'reviewed_at' => null]);
    $before = $lesson->fresh()->getAttributes();

    try {
        PublishLesson::publish($lesson);
        $this->fail('Publishing without review should have been refused.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('review');
    }

    expect($lesson->fresh()->getAttributes())->toBe($before);
});

test('publish refuses a lesson whose parent topic is not published', function () {
    $lesson = ContentFixtures::validDraftLesson();
    $lesson->topic->update(['status' => 'draft', 'published_at' => null]);
    $before = $lesson->fresh()->getAttributes();

    try {
        PublishLesson::publish($lesson);
        $this->fail('Publishing under a draft topic should have been refused.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('topic');
    }

    expect($lesson->fresh()->getAttributes())->toBe($before);
});

test('publish refuses an empty title and an empty body with specific errors', function () {
    $emptyTitle = ContentFixtures::validDraftLesson(['title_en' => '   ']);
    $emptyBody = ContentFixtures::validDraftLesson(['body_en' => "\n\n"]);

    expect(PublishLesson::lessonProblems($emptyTitle))->toHaveKey('title_en');
    expect(PublishLesson::lessonProblems($emptyBody))->toHaveKey('body_en');

    try {
        PublishLesson::publish($emptyTitle);
        $this->fail('Publishing with an empty title should have been refused.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('title_en');
    }
    expect($emptyTitle->fresh()->status)->toBe('draft');
});

test('a valid lesson publishes once and a repeated publish is idempotent', function () {
    $lesson = ContentFixtures::validDraftLesson();

    $published = PublishLesson::publish($lesson);

    expect($published->status)->toBe('published')
        ->and($published->published_at)->not->toBeNull();

    $stamp = $published->published_at->toDateTimeString();

    $again = PublishLesson::publish($published->fresh());

    expect($again->status)->toBe('published')
        ->and($again->published_at->toDateTimeString())->toBe($stamp)
        ->and(Lesson::query()->where('id', $lesson->id)->count())->toBe(1);
});

test('an archived lesson must return to draft before it can publish', function () {
    $lesson = ContentFixtures::validDraftLesson();
    PublishLesson::publish($lesson);
    PublishLesson::archive($lesson->fresh());

    try {
        PublishLesson::publish($lesson->fresh());
        $this->fail('Publishing from archived should have been refused.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('status');
    }

    $draft = PublishLesson::returnToDraft($lesson->fresh());
    expect($draft->status)->toBe('draft');

    expect(PublishLesson::publish($draft->fresh())->status)->toBe('published');
});

test('the CLI publishes through the same shared action', function () {
    $lesson = ContentFixtures::validDraftLesson();

    $this->artisan('content:transition', [
        'action' => 'publish', 'type' => 'lesson', 'id' => $lesson->id,
    ])->assertSuccessful();

    expect($lesson->fresh()->status)->toBe('published');
});

test('the CLI refuses an invalid lesson with the specific error', function () {
    $lesson = ContentFixtures::validDraftLesson(['reviewed_by' => null, 'reviewed_at' => null]);

    $this->artisan('content:transition', [
        'action' => 'publish', 'type' => 'lesson', 'id' => $lesson->id,
    ])->assertExitCode(1);

    expect($lesson->fresh()->status)->toBe('draft');
});
