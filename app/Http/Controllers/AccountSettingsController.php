<?php

namespace App\Http\Controllers;

use App\Actions\PublishLesson;
use Illuminate\Http\Request;

/**
 * S4 level preference (scope §7.2, LEVEL-01): preferred_level changes only
 * through this explicit settings form. Browsing a ?level= URL never writes
 * here (TopicReaderController performs no user writes).
 *
 * Guests keep their selected level as a non-sensitive browser preference
 * (localStorage, no token/receipt/body/answer key — see reader-player.js).
 * When the preferred level has no lesson for a topic, the reader shows the
 * available levels and never substitutes silently (S1 rule, unchanged).
 */
class AccountSettingsController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();
        if ($user === null || $user->disabled_at !== null) {
            abort(403);
        }

        return response()
            ->view('account.settings', [
                'user' => $user,
                'levels' => PublishLesson::LEVELS,
            ])
            ->withHeaders($this->noStore());
    }

    public function update(Request $request)
    {
        $user = $request->user();
        if ($user === null || $user->disabled_at !== null) {
            abort(403);
        }

        $data = $request->validate([
            'preferred_level' => ['nullable', 'in:A1,A2,B1,B2,C1,C2'],
            // R5: the daily goal is an independent explicit choice. It
            // never writes preferred_level and vice versa.
            'daily_goal_minutes' => ['nullable', 'in:5,10,15'],
        ]);

        $updates = [];
        if (array_key_exists('preferred_level', $data)) {
            $updates['preferred_level'] = $data['preferred_level'] ?? null;
        }
        if (array_key_exists('daily_goal_minutes', $data)) {
            $updates['daily_goal_minutes'] = $data['daily_goal_minutes'] !== null
                ? (int) $data['daily_goal_minutes']
                : 10;
        }
        $user->forceFill($updates)->save();

        return redirect()
            ->route('account.settings')
            ->with('status', 'سطح ترجیحی ذخیره شد.');
    }

    /** @return array<string, string> */
    private function noStore(): array
    {
        return ['Cache-Control' => 'private, no-store'];
    }
}
