<?php

namespace App\Providers;

use App\Models\Lesson;
use App\Models\PaymentRequest;
use App\Models\PlacementAttempt;
use App\Policies\LessonPolicy;
use App\Policies\PaymentRequestPolicy;
use App\Policies\PlacementAttemptPolicy;
use App\Support\BrowserDatabaseGuard;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Lesson::class, LessonPolicy::class);
        Gate::policy(PaymentRequest::class, PaymentRequestPolicy::class);
        Gate::policy(PlacementAttempt::class, PlacementAttemptPolicy::class);

        // S2: destructive console commands must never wipe the browser-lane
        // database. Seeding uses plain `migrate --seeder=...`, which is
        // unaffected. Carried scope note: X-Accel-Redirect delivery stays
        // deferred to S8/S9 with production hosting (see LessonAudioController).
        if ($this->app->runningInConsole()) {
            $this->app['events']->listen(CommandStarting::class, function (CommandStarting $event): void {
                if (in_array($event->command, ['migrate:fresh', 'migrate:refresh', 'migrate:reset', 'db:wipe'], true)) {
                    $connection = config('database.default');
                    BrowserDatabaseGuard::ensureNotBrowserDatabase(
                        config("database.connections.{$connection}.database")
                    );
                }
            });
        }
    }
}
