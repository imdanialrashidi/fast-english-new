<?php

namespace App\Actions\Fortify;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

/**
 * S8 password reset (AUTH-02, scope §3): the standard Fortify reset
 * action. Validation mirrors registration (PasswordValidationRules);
 * the token itself is single-use — Laravel deletes the
 * password_reset_tokens row on success, so a replayed link fails.
 */
class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    public function reset($user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();
    }
}
