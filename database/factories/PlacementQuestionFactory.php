<?php

namespace Database\Factories;

use App\Models\PlacementQuestion;
use App\Models\PlacementTest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlacementQuestion>
 *
 * FIXTURE rows only (never real exam content). The correct option cycles
 * deterministically unless overridden per test.
 */
class PlacementQuestionFactory extends Factory
{
    protected $model = PlacementQuestion::class;

    public function definition(): array
    {
        return [
            'test_id' => PlacementTest::factory(),
            'position' => 1,
            'prompt' => 'FIXTURE question (TEST)',
            'options' => [
                'FIXTURE option A (TEST)',
                'FIXTURE option B (TEST)',
                'FIXTURE option C (TEST)',
                'FIXTURE option D (TEST)',
            ],
            'correct_option' => 0,
        ];
    }
}
