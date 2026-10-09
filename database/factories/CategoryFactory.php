<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'name_fa' => fake()->unique()->word(),
            'slug' => fake()->unique()->slug(),
            'display_order' => 0,
            'is_active' => true,
        ];
    }
}
