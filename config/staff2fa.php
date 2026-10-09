<?php

// S8 staff two-factor (scope §5: TOTP + recovery handling required before
// real staffing; idle timeout for staff sessions).
//
// - enforce: when on, a staff account without a CONFIRMED TOTP secret is
//   redirected away from the staff panel to the setup page. Default is ON
//   in production and OFF elsewhere (local/dev/test keep the current
//   panel flow; S8-3 toggles this flag explicitly in tests).
// - idle_minutes: staff session idle timeout. A staff request older than
//   this many minutes since the last staff activity is logged out.
//   Recorded value: 30 minutes.
// - window: TOTP verification window (30-second steps each side).
// - recovery_codes: how many single-use codes are issued at confirm time.

return [
    'enforce' => (bool) env('STAFF_2FA_ENFORCE', env('APP_ENV') === 'production'),

    'idle_minutes' => (int) env('STAFF_2FA_IDLE_MINUTES', 30),

    'window' => (int) env('STAFF_2FA_WINDOW', 1),

    'recovery_codes' => 8,
];
