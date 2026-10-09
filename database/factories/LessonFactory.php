<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

/**
 * @extends Factory<Lesson>
 *
 * Audio always points at a real MP3 on the private disk: the shared S1
 * tone fixture is copied to a per-test path so tests never mock storage.
 */
class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    public function definition(): array
    {
        return [
            'topic_id' => Topic::factory(),
            'level' => 'A2',
            'title_en' => fake()->sentence(4),
            'body_en' => fake()->paragraph()."\n\n".fake()->paragraph(),
            'audio_path' => 'lessons/test-'.fake()->unique()->uuid().'.mp3',
            'audio_revision' => 1,
            'duration_seconds' => 20,
            'estimated_minutes' => 3,
            'is_public_sample' => false,
            'status' => 'published',
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Lesson $lesson) {
            $source = database_path('seeders/fixtures/s1-a2-tone-fixture.mp3');
            Storage::disk('local')->makeDirectory(dirname($lesson->audio_path));
            copy($source, Storage::disk('local')->path($lesson->audio_path));
        });
    }
}
