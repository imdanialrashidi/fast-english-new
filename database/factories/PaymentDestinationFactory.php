<?php

namespace Database\Factories;

use App\Models\PaymentDestination;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentDestination>
 */
class PaymentDestinationFactory extends Factory
{
    protected $model = PaymentDestination::class;

    public function definition(): array
    {
        return [
            'card_number' => '60379911'.str_pad((string) fake()->unique()->numberBetween(0, 99999999), 8, '0', STR_PAD_LEFT),
            'holder_name' => 'صاحب آزمایشی',
            'bank_name' => 'بانک آزمایشی',
            'instructions' => 'متن آزمایشی.',
            'is_active' => false,
        ];
    }
}
