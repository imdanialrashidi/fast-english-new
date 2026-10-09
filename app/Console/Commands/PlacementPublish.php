<?php

namespace App\Console\Commands;

use App\Actions\PublishPlacementTest;
use App\Models\PlacementTest;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

/**
 * S8 placement publishing CLI: publishes a placement version through the
 * SAME shared App\Actions\PublishPlacementTest the staff page uses —
 * versioning logic lives in exactly one place (mirrors the S3
 * content:transition pattern).
 */
class PlacementPublish extends Command
{
    protected $signature = 'placement:publish {id : ID of the placement test version}';

    protected $description = 'Publish a placement test version through the shared publish action.';

    public function handle(): int
    {
        $test = PlacementTest::find((int) $this->argument('id'));
        if ($test === null) {
            $this->error('Unknown placement test.');

            return 1;
        }

        try {
            $result = PublishPlacementTest::publish($test);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ((array) $messages as $message) {
                    $this->error("{$field}: {$message}");
                }
            }

            return 1;
        }

        $this->info("Placement test [{$result->id}] version [{$result->version}] is now published and current.");

        return 0;
    }
}
