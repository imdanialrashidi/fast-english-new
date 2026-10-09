<?php

namespace Tests\Support;

use App\Models\PaymentRequest;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * S6 shared builders: real rows through the real S5 HTTP endpoints (no
 * mocks), so the approval tests start from genuine pending requests with
 * private receipt files.
 */
final class S6Payments
{
    public static function staff(): User
    {
        return User::factory()->create(['is_staff' => true]);
    }

    public static function student(): User
    {
        return User::factory()->create(['is_staff' => false]);
    }

    public static function pendingRequest(object $test, User $user, Plan $plan): PaymentRequest
    {
        $test->actingAs($user)->post(route('payments.store'), ['plan_id' => $plan->id]);
        // The open request (latest open): earlier terminal rows stay kept.
        $row = PaymentRequest::where('user_id', $user->id)
            ->whereIn('status', PaymentRequest::OPEN_STATUSES)
            ->orderByDesc('id')
            ->firstOrFail();
        $test->actingAs($user)->post(route('payments.receipt.store', $row), [
            'receipt' => UploadedFile::fake()->createWithContent(
                'receipt.jpg',
                (string) file_get_contents(database_path('seeders/fixtures/s3-cover-fixture.jpg'))
            ),
        ]);

        $fresh = $row->fresh();
        if ($fresh->status !== PaymentRequest::STATUS_PENDING) {
            throw new \RuntimeException('S6 fixture failed to reach pending.');
        }

        return $fresh;
    }
}
