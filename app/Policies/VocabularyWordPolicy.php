<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VocabularyWord;

/**
 * R4 ownership (VOCAB-01): a student owns exactly their own words. Staff
 * have no extra read/write here — support uses the same panel rules as
 * every other learner record (no shared vocabulary administration).
 */
class VocabularyWordPolicy
{
    public function view(User $user, VocabularyWord $entry): bool
    {
        if ($user->disabled_at !== null) {
            return false;
        }

        return $entry->user_id === $user->id;
    }

    public function update(User $user, VocabularyWord $entry): bool
    {
        return $this->view($user, $entry);
    }

    public function delete(User $user, VocabularyWord $entry): bool
    {
        return $this->view($user, $entry);
    }
}
