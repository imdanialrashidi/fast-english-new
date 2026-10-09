<?php

namespace App\Filament\Pages;

use App\Models\PlacementTest;
use Filament\Pages\Page;

/**
 * S8 placement management (scope §5: staff «مدیریت آزمون»).
 *
 * Lists placement versions with per-version question previews. The answer
 * key (correct_option) renders here because this page lives inside the
 * staff-only panel — no public route ever receives it. Publishing posts
 * to StaffPlacementController, which calls the SAME shared
 * PublishPlacementTest action the `placement:publish` CLI uses.
 */
class PlacementOverview extends Page
{
    protected string $view = 'filament.pages.placement-overview';

    protected static ?string $navigationLabel = 'Placement tests';

    protected static ?string $title = 'Placement tests';

    /** @var list<array<string, mixed>> */
    public array $versions = [];

    public function mount(): void
    {
        $this->versions = PlacementTest::query()
            ->orderByDesc('is_current')
            ->orderByDesc('id')
            ->with(['questions' => fn ($questions) => $questions->orderBy('position')])
            ->get()
            ->map(fn (PlacementTest $test): array => [
                'id' => $test->id,
                'version' => $test->version,
                'status' => $test->status,
                'is_current' => (bool) $test->is_current,
                'question_count' => $test->questions->count(),
                'questions' => $test->questions->map(fn ($question): array => [
                    'position' => $question->position,
                    'prompt' => $question->prompt,
                    'options' => $question->options,
                    'correct_option' => $question->correct_option,
                ])->all(),
            ])
            ->all();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && (bool) $user->is_staff && $user->disabled_at === null;
    }
}
