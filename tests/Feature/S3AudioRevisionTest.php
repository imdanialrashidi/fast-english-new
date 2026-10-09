<?php

use App\Actions\PublishLesson;
use Illuminate\Support\Facades\Storage;
use Tests\Support\ContentFixtures;

// S3-5: replacing the audio file increments audio_revision; changing only
// the title (or fixing a typo in the body) leaves it untouched. Progress
// scoped to the revision arrives in S4; here the stored value is proven.

test('replacing the audio file increments the stored revision', function () {
    $lesson = ContentFixtures::validDraftLesson();
    expect($lesson->audio_revision)->toBe(1);

    $replacement = 'lessons/s3-replacement-'.$lesson->id.'.mp3';
    copy(
        database_path('seeders/fixtures/s1-b1-tone-fixture.mp3'),
        Storage::disk('local')->path($replacement)
    );

    $lesson->audio_path = $replacement;
    $lesson->save();

    expect($lesson->fresh()->audio_revision)->toBe(2);

    Storage::disk('local')->delete($replacement);
});

test('changing only the title or body does not touch the revision', function () {
    $lesson = ContentFixtures::validDraftLesson();

    $lesson->title_en = $lesson->title_en.' (2nd ed.)';
    $lesson->body_en = $lesson->body_en."\n\nA corrected sentence.";
    $lesson->save();

    expect($lesson->fresh()->audio_revision)->toBe(1);
});

test('the revision survives a publish round-trip', function () {
    $lesson = ContentFixtures::validDraftLesson();

    PublishLesson::publish($lesson);

    expect($lesson->fresh()->audio_revision)->toBe(1);
});
