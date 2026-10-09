<?php

// S2 PWA/TWA settings. Secrets and host-specific values live in the
// untracked environment file, never here.

return [

    /*
    |--------------------------------------------------------------------------
    | Service worker version
    |--------------------------------------------------------------------------
    |
    | Baked into the versioned cache names (fe-public-<version>). Bump to
    | roll caches. The /sw.js route prefers storage/app/sw-version when
    | present so browser tests can trigger a real update without a restart.
    |
    */

    'sw_version' => env('SW_VERSION', 's2-v1'),

    /*
    |--------------------------------------------------------------------------
    | TWA / Digital Asset Links
    |--------------------------------------------------------------------------
    |
    | PROPOSAL (not owner-approved): package com.fastenglishpodcast.app.
    | Finalize before the first release. The SHA-256 certificate fingerprint
    | belongs in the untracked environment file; /.well-known/assetlinks.json
    | serves an empty statement list until both values are configured.
    |
    */

    'twa_package' => env('TWA_PACKAGE_NAME', 'com.fastenglishpodcast.app'),

    'twa_fingerprint' => env('TWA_SHA256_FINGERPRINT'),
];
