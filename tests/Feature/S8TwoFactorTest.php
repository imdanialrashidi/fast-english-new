<?php

use App\Support\StaffTwoFactor;
use Tests\Support\ContentFixtures;

// S8-3 (staff 2FA, scope §5): TOTP for staff accounts only. Two staff
// accounts + one student + negative paths (wrong code, reused recovery
// code, student enable attempt, idle expiry).

beforeEach(function () {
    $this->staffA = ContentFixtures::staff();
    $this->staffB = ContentFixtures::staff();
    $this->student = ContentFixtures::student();
});

function s8ConfirmStaff2fa(object $test, $staff): array
{
    $test->actingAs($staff)->post(route('staff.twofactor.start'))->assertRedirect();
    $secret = $staff->fresh()->two_factor_secret;
    expect($secret)->not->toBeNull()
        ->and($staff->fresh()->two_factor_confirmed_at)->toBeNull();

    $response = $test->actingAs($staff)->post(route('staff.twofactor.confirm'), [
        'code' => StaffTwoFactor::currentOtp((string) $secret),
    ])->assertOk();

    return [$secret, $response->getContent()];
}

test('staff without 2fa cannot reach the panel once enforcement is on', function () {
    config(['staff2fa.enforce' => true]);

    $this->actingAs($this->staffB)->get('/admin')
        ->assertRedirect(route('staff.twofactor.setup'));

    // Unfinished setup (unconfirmed secret) still grants nothing.
    $this->actingAs($this->staffB)->post(route('staff.twofactor.start'))->assertRedirect();
    $this->actingAs($this->staffB)->get('/admin')
        ->assertRedirect(route('staff.twofactor.setup'));
});

test('staff with confirmed 2fa reaches the panel and a student never does', function () {
    config(['staff2fa.enforce' => true]);

    [$secret, $recoveryHtml] = s8ConfirmStaff2fa($this, $this->staffA);

    // Recovery codes shown once in the confirm response...
    expect($recoveryHtml)->toContain('فقط یک‌بار نمایش');

    // ...stored hashed, never plain.
    $hashes = $this->staffA->fresh()->two_factor_recovery_codes;
    expect($hashes)->toBeArray()->toHaveCount(8);
    foreach ($hashes as $hash) {
        expect($hash)->toMatch('/^[0-9a-f]{64}$/');
    }
    preg_match_all('/[0-9A-F]{4}-[0-9A-F]{4}/', $recoveryHtml, $m);
    $plainCodes = $m[0];
    expect($plainCodes)->toHaveCount(8);
    $stored = json_encode($hashes);
    foreach ($plainCodes as $plain) {
        expect($stored)->not->toContain($plain);
    }

    // A later visit never shows the codes again.
    $setupHtml = $this->actingAs($this->staffA)->get(route('staff.twofactor.setup'))->assertOk()->getContent();
    foreach ($plainCodes as $plain) {
        expect($setupHtml)->not->toContain($plain);
    }

    // Confirm-time session flag carries into the panel.
    $this->actingAs($this->staffA)->get('/admin')->assertOk();

    // The student cannot reach staff routes at all.
    $this->actingAs($this->student)->get('/admin')->assertForbidden();
    $this->actingAs($this->student)->get(route('staff.twofactor.setup'))->assertForbidden();
    $this->actingAs($this->student)->post(route('staff.twofactor.start'))->assertForbidden();
    $this->actingAs($this->student)->get(route('staff.twofactor.challenge'))->assertForbidden();
});

test('challenge accepts totp, rejects wrong codes, consumes recovery codes once', function () {
    config(['staff2fa.enforce' => true]);
    [$secret, $recoveryHtml] = s8ConfirmStaff2fa($this, $this->staffA);
    preg_match_all('/[0-9A-F]{4}-[0-9A-F]{4}/', $recoveryHtml, $m);
    $recoveryCode = $m[0][0];

    // Fresh session (no flag): panel bounces to the challenge.
    $this->actingAs($this->staffA)->withSession(['staff_2fa_passed_at' => null, 'staff_last_activity' => time()]);
    $this->actingAs($this->staffA)->get('/admin')
        ->assertRedirect(route('staff.twofactor.challenge'));

    // Wrong code changes nothing.
    $this->actingAs($this->staffA)->post(route('staff.twofactor.verify'), ['code' => '000000'])
        ->assertSessionHasErrors('code');
    $this->actingAs($this->staffA)->get('/admin')
        ->assertRedirect(route('staff.twofactor.challenge'));

    // Recovery code works once and sets the session flag...
    $this->actingAs($this->staffA)->post(route('staff.twofactor.verify'), ['code' => $recoveryCode])
        ->assertRedirect('/admin')
        ->assertSessionHas('staff_2fa_passed_at');
    expect($this->staffA->fresh()->two_factor_recovery_codes)->toHaveCount(7);
    $this->actingAs($this->staffA)->get('/admin')->assertOk();

    // ...and the same code never works twice (hash consumed).
    $this->actingAs($this->staffA)->withSession(['staff_2fa_passed_at' => null, 'staff_last_activity' => time()]);
    $this->actingAs($this->staffA)->post(route('staff.twofactor.verify'), ['code' => $recoveryCode])
        ->assertSessionHasErrors('code');

    // TOTP still works afterwards.
    $this->actingAs($this->staffA)->post(route('staff.twofactor.verify'), [
        'code' => StaffTwoFactor::currentOtp((string) $this->staffA->fresh()->two_factor_secret),
    ])->assertRedirect('/admin');
    $this->actingAs($this->staffA)->get('/admin')->assertOk();
});

test('idle staff sessions are logged out', function () {
    config(['staff2fa.enforce' => true]);
    s8ConfirmStaff2fa($this, $this->staffA);

    $stale = time() - (31 * 60);
    $this->actingAs($this->staffA)->withSession([
        'staff_2fa_passed_at' => $stale,
        'staff_last_activity' => $stale,
    ]);

    $this->actingAs($this->staffA)->get('/admin')->assertRedirect(route('login'));
    $this->assertGuest();
});

test('enforcement off preserves the dev panel flow for staff without 2fa', function () {
    config(['staff2fa.enforce' => false]);

    $this->actingAs($this->staffB)->get('/admin')->assertOk();
    $this->actingAs($this->student)->get('/admin')->assertForbidden();
});
