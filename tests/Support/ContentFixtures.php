<?php

namespace Tests\Support;

use App\Models\Lesson;
use App\Models\Topic;
use App\Models\User;

/**
 * S3 shared builders: real rows, real fixture audio on the private disk,
 * no mocks. LessonFactory copies the S1 tone fixture to the lesson's
 * audio_path, so publish validation always sees genuine bytes.
 */
final class ContentFixtures
{
    public static function staff(): User
    {
        return User::factory()->create(['is_staff' => true]);
    }

    public static function student(): User
    {
        return User::factory()->create(['is_staff' => false]);
    }

    public static function publishedTopic(array $overrides = []): Topic
    {
        return Topic::factory()->create(array_merge([
            'status' => 'published',
            'published_at' => now(),
        ], $overrides));
    }

    /**
     * A draft lesson that is valid in every publish respect: published
     * parent, allowed level, non-empty title/body, real audio with a
     * positive duration, and a recorded review.
     */
    public static function validDraftLesson(array $overrides = []): Lesson
    {
        $reviewer = User::factory()->create(['is_staff' => true]);

        return Lesson::factory()
            ->for(self::publishedTopic())
            ->create(array_merge([
                'level' => 'A2',
                'status' => 'draft',
                'published_at' => null,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ], $overrides));
    }
}
