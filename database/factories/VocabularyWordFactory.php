<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VocabularyWord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VocabularyWord>
 */
class VocabularyWordFactory extends Factory
{
    protected $model = VocabularyWord::class;

    public function definition(): array
    {
        $word = fake()->unique()->word();

        return [
            'user_id' => User::factory(),
            'word' => $word,
            'meaning_fa' => 'معنی '.$word,
            'example_en' => 'An example with '.$word.'.',
            'lesson_id' => null,
            'topic_id' => null,
            'status' => 'learning',
            'ease_factor' => 2.5,
            'interval_days' => 0,
            'repetitions' => 0,
            'lapses' => 0,
            'due_at' => now(),
            'last_reviewed_at' => null,
        ];
    }
}
