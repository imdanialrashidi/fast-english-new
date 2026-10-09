<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'name_fa' => 'پلن آزمایشی '.fake()->word(),
            'price_toman' => 100000,
            'duration_days' => 30,
            'is_active' => true,
            'display_order' => 0,
        ];
    }
}
