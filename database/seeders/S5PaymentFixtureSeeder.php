<?php

namespace Database\Seeders;

use App\Models\PaymentDestination;
use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * S5 payment fixture seeder: plans (30, 90, 365 days) + one destination.
 *
 * Every value here is a labelled TEST VALUE — never real prices or a real
 * card. Real amounts and the real destination live outside version
 * control (untracked environment / staff-entered production rows) and
 * must never be added to migrations, seeders, or tracked config.
 *
 * Fixture values (TEST ONLY):
 * - 30-day «یک‌ماهه آزمایشی» — 100٬000 تومان (TEST)
 * - 90-day «سه‌ماهه آزمایشی» — 250٬000 تومان (TEST)
 * - 365-day «یک‌ساله آزمایشی» — 900٬000 تومان (TEST)
 * - destination card 6037-9911-1234-5678 (TEST), holder «صاحب آزمایشی»,
 *   bank «بانک آزمایشی» (all TEST values)
 *
 * Refuses production. The single active destination satisfies the
 * at-most-one-active constraint.
 */
class S5PaymentFixtureSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('S5PaymentFixtureSeeder refuses to run in production (test values only).');
        }

        $plans = [
            [
                'slug' => 's5-test-30',
                'name_fa' => 'یک‌ماهه آزمایشی (TEST)',
                'price_toman' => 100000,
                'duration_days' => 30,
                'is_active' => true,
                'display_order' => 1,
            ],
            [
                'slug' => 's5-test-90',
                'name_fa' => 'سه‌ماهه آزمایشی (TEST)',
                'price_toman' => 250000,
                'duration_days' => 90,
                'is_active' => true,
                'display_order' => 2,
            ],
            [
                'slug' => 's5-test-365',
                'name_fa' => 'یک‌ساله آزمایشی (TEST)',
                'price_toman' => 900000,
                'duration_days' => 365,
                'is_active' => true,
                'display_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }

        // Deactivate any other active destination first so the partial
        // unique index never sees two active rows during the upsert.
        PaymentDestination::where('is_active', true)
            ->where('card_number', '!=', '6037991112345678')
            ->update(['is_active' => false]);

        PaymentDestination::updateOrCreate(
            ['card_number' => '6037991112345678'],
            [
                // TEST VALUE — not a real card.
                'card_number' => '6037991112345678',
                'holder_name' => 'صاحب آزمایشی (TEST)',
                'bank_name' => 'بانک آزمایشی (TEST)',
                'instructions' => 'مبلغ دقیق پلن را کارت‌به‌کارت کنید و رسید را همین‌جا ارسال کنید. (متن آزمایشی — TEST)',
                'is_active' => true,
            ]
        );
    }
}
