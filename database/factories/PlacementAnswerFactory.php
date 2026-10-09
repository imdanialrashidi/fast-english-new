<?php

namespace Database\Factories;

use App\Models\PlacementAnswer;
use App\Models\PlacementAttempt;
use App\Models\PlacementQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlacementAnswer>
 */
class PlacementAnswerFactory extends Factory
{
    protected $model = PlacementAnswer::class;

    public function definition(): array
    {
        return [
            'attempt_id' => PlacementAttempt::factory(),
            'question_id' => PlacementQuestion::factory(),
            'selected_option' => 0,
            'is_correct' => null,
        ];
    }
}
