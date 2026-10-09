<?php

namespace App\Policies;

use App\Models\PlacementAttempt;
use App\Models\User;

/**
 * S7 placement ownership (scope §8, §12): attempts and their answers
 * belong to one learner. Staff have no learner-attempt access here;
 * version management goes through the publish action (tests cover the
 * staff-negative path on these routes).
 */
class PlacementAttemptPolicy
{
    public function view(User $user, PlacementAttempt $attempt): bool
    {
        if ($user->disabled_at !== null) {
            return false;
        }

        return (int) $user->id === (int) $attempt->user_id;
    }

    public function update(User $user, PlacementAttempt $attempt): bool
    {
        return $this->view($user, $attempt);
    }
}
