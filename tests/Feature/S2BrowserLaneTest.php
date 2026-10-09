<?php

use App\Models\Lesson;
use App\Support\BrowserDatabaseGuard;
use Database\Seeders\S1SampleSeeder;
use Database\Seeders\S2BrowserSeeder;
use Illuminate\Console\Events\CommandStarting;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

// S2 pre-slice correction 1: the browser lane owns fe_browser; Pest and
// destructive artisan commands must never touch it.

test('guard refuses the browser database and allows the others', function () {
    expect(fn () => BrowserDatabaseGuard::ensureNotBrowserDatabase('fe_browser'))
        ->toThrow(RuntimeException::class, 'fe_browser');

    BrowserDatabaseGuard::ensureNotBrowserDatabase('fe_test');
    BrowserDatabaseGuard::ensureNotBrowserDatabase('fe');
    BrowserDatabaseGuard::ensureNotBrowserDatabase(null);
    expect(true)->toBeTrue();
});

test('destructive console commands abort when pointed at the browser database', function () {
    config()->set('database.connections.pgsql.database', 'fe_browser');

    try {
        event(new CommandStarting('migrate:fresh', new ArrayInput([]), new NullOutput));
        $this->fail('CommandStarting on fe_browser should have been refused.');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toContain('fe_browser');
    }
});

test('browser seeder refuses production', function () {
    $this->app['env'] = 'production';

    // Direct run: the artisan db:seed path adds its own production
    // confirmation on top; the seeder's self-guard must fire first.
    expect(fn () => (new S2BrowserSeeder)->run())
        ->toThrow(RuntimeException::class, 'refuses to run in production');
});

// S2 pre-slice correction 2: every S1 duration_seconds value is the
// ffprobe-measured fixture duration (A2 tone 20.000000 s, B1 tone
// 30.000000 s — see active exec plan), not a typed guess.
test('seeded durations match the ffprobe-measured fixture durations', function () {
    $this->seed(S1SampleSeeder::class);

    $durations = Lesson::orderBy('level')->pluck('duration_seconds', 'level');

    expect($durations['A2'])->toBe(20)
        ->and($durations['B1'])->toBe(30);
});
