<?php

namespace App\Policies;

use App\Models\PaymentRequest;
use App\Models\User;

/**
 * S6 receipt policy (scope §10.3, §16): the receipt image is private.
 *
 * - The owner may view the request page and the receipt bytes.
 * - Active staff may fetch the receipt bytes through the same staff-only
 *   no-store route (used by the Filament payment queue); the request page
 *   itself stays owner-only.
 * - Other students and guests are refused; disabled accounts everywhere.
 */
class PaymentRequestPolicy
{
    public function view(User $user, PaymentRequest $request): bool
    {
        if ($user->disabled_at !== null) {
            return false;
        }

        return (int) $user->id === (int) $request->user_id;
    }

    public function viewReceipt(User $user, PaymentRequest $request): bool
    {
        if ($user->disabled_at !== null || $request->receipt_path === null) {
            return false;
        }

        if ((int) $user->id === (int) $request->user_id) {
            return true;
        }

        return (bool) $user->is_staff;
    }

    public function cancel(User $user, PaymentRequest $request): bool
    {
        if (! $this->view($user, $request)) {
            return false;
        }

        // S5: the learner may cancel only while awaiting_receipt. Pending
        // cancellation is staff-only with a reason — an S6 panel action,
        // explicitly deferred (recorded in the exec plan).
        return $request->status === PaymentRequest::STATUS_AWAITING_RECEIPT;
    }
}
