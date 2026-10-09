<?php

namespace Database\Factories;

use App\Models\PlacementAttempt;
use App\Models\PlacementTest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlacementAttempt>
 */
class PlacementAttemptFactory extends Factory
{
    protected $model = PlacementAttempt::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'test_id' => PlacementTest::factory(),
            'status' => PlacementAttempt::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ];
    }
}
