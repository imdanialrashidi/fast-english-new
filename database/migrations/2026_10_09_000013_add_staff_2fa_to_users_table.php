<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * S8 staff two-factor (scope §5): TOTP for staff accounts only.
     *
     * - two_factor_secret: encrypted TOTP secret, set at enable time.
     * - two_factor_recovery_codes: JSON array of SHA-256 hashes (never the
     *   plain codes — they are shown once at confirm time and only hashes
     *   persist). Each hash is single-use: a used code's hash is removed.
     * - two_factor_confirmed_at: set only after a valid TOTP confirmation.
     *   Panel enforcement requires this timestamp; the secret alone (an
     *   unfinished setup) grants nothing.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('remember_token');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);
        });
    }
};
