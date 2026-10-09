<?php

namespace Database\Seeders;

use App\Actions\PublishPlacementTest;
use App\Models\PlacementQuestion;
use App\Models\PlacementTest;
use Illuminate\Database\Seeder;

/**
 * S7 placement fixture seeder: one published current version with exactly
 * 20 four-option questions.
 *
 * Every prompt and option row is marked FIXTURE — these are labelled
 * synthetic rows for local/test use, never real exam content. Teacher
 * approval of real questions and cut scores is required before any public
 * exam (recorded BLOCKED in the plan). Refuses production.
 *
 * Version: s7-fixture-v1 (TEST). Correct options rotate 0–3 so the
 * server-marking tests can assert exact scores deterministically.
 */
class S7PlacementFixtureSeeder extends Seeder
{
    public const VERSION = 's7-fixture-v1';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('S7PlacementFixtureSeeder refuses to run in production (fixture only).');
        }

        $test = PlacementTest::where('version', self::VERSION)->first();
        if ($test === null) {
            $test = PublishPlacementTest::createDraft(self::VERSION);
        }

        if (PlacementQuestion::where('test_id', $test->id)->count() === 0) {
            for ($position = 1; $position <= 20; $position++) {
                $tag = str_pad((string) $position, 2, '0', STR_PAD_LEFT);
                PublishPlacementTest::addQuestion(
                    $test->fresh(),
                    $position,
                    "FIXTURE question {$tag} — choose the marked option (TEST)",
                    [
                        "FIXTURE option A-{$tag} (TEST)",
                        "FIXTURE option B-{$tag} (TEST)",
                        "FIXTURE option C-{$tag} (TEST)",
                        "FIXTURE option D-{$tag} (TEST)",
                    ],
                    ($position - 1) % 4,
                );
            }
        }

        PublishPlacementTest::publish($test->fresh());
    }
}
