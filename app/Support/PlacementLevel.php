<?php

namespace App\Support;

/**
 * S7 suggested-level fixture (scope §12): score → suggested level.
 *
 * This table is a FIXTURE for the optional placement suggestion — not a
 * validated CEFR scale. Teacher approval of real questions and cut scores
 * is required before any public exam (recorded BLOCKED in the plan).
 */
final class PlacementLevel
{
    public static function forScore(int $score): string
    {
        return match (true) {
            $score <= 3 => 'A1',
            $score <= 7 => 'A2',
            $score <= 11 => 'B1',
            $score <= 15 => 'B2',
            $score <= 18 => 'C1',
            default => 'C2',
        };
    }

    /** @return array<string, array{int, int}> */
    public static function fixtureMap(): array
    {
        return [
            'A1' => [0, 3],
            'A2' => [4, 7],
            'B1' => [8, 11],
            'B2' => [12, 15],
            'C1' => [16, 18],
            'C2' => [19, 20],
        ];
    }
}
