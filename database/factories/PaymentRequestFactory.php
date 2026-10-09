<?php

namespace Database\Factories;

use App\Models\PaymentDestination;
use App\Models\PaymentRequest;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentRequest>
 *
 * Tests build open requests through the real HTTP endpoints whenever the
 * snapshot rule is under test; this factory exists for states (rejected,
 * cancelled, approved-shaped rows) that S5 never creates via its own UI.
 * Snapshots are always written explicitly — never trusted from input.
 */
class PaymentRequestFactory extends Factory
{
    protected $model = PaymentRequest::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plan_id' => Plan::factory(),
            'destination_id' => PaymentDestination::factory(),
            'plan_name_snapshot' => 'پلن آزمایشی',
            'amount_toman_snapshot' => 100000,
            'duration_days_snapshot' => 30,
            'destination_snapshot' => [
                'card_number' => '6037991112345678',
                'holder_name' => 'صاحب آزمایشی (TEST)',
                'bank_name' => 'بانک آزمایشی (TEST)',
            ],
            'status' => PaymentRequest::STATUS_AWAITING_RECEIPT,
        ];
    }
}
