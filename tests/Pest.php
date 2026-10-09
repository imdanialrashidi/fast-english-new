<?php

use App\Support\BrowserDatabaseGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature');

// S2: Pest (RefreshDatabase → migrate:fresh) must never touch the
// browser-lane database. Fail fast before any migration runs.
beforeEach(function () {
    BrowserDatabaseGuard::ensureNotBrowserDatabase(
        config('database.connections.'.config('database.default').'.database')
    );
})->in('Feature', 'Unit');
