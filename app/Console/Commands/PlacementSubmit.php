<?php

namespace App\Console\Commands;

use App\Actions\SubmitPlacement;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

/**
 * S7 submit CLI: the non-HTTP path through the same shared SubmitPlacement
 * action the placement controller uses. Exists so the submit race (two
 * real OS processes, one attempt) is proven exactly like the S6
 * approve×approve race — no mocks, real PostgreSQL row locks.
 */
class PlacementSubmit extends Command
{
    protected $signature = 'placement:submit {attempt : Placement attempt ID}';

    protected $description = 'Submit a placement attempt through the shared submit action.';

    public function handle(): int
    {
        $attemptId = (int) $this->argument('attempt');

        try {
            $outcome = SubmitPlacement::submit($attemptId);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ((array) $messages as $message) {
                    $this->error("{$field}: {$message}");
                }
            }

            return 1;
        }

        $attempt = $outcome['attempt'];
        $this->info($outcome['replayed']
            ? "Attempt [{$attempt->id}] already completed with score {$attempt->score}; no new result."
            : "Attempt [{$attempt->id}] completed with score {$attempt->score}.");

        return 0;
    }
}
