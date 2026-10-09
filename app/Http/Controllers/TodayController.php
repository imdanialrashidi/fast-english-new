<?php

namespace App\Http\Controllers;

use App\Support\DailyPlan;
use Illuminate\Http\Request;

/**
 * R5 Today — «امروز» (PLAN-01): the principal signed-in student
 * destination. Greeting, Continue Learning, today's plan, goal progress,
 * due vocabulary, and recommendations — every row derived from persisted
 * state via DailyPlan (never stored, never a self-toggling button).
 */
class TodayController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if ($user === null || $user->disabled_at !== null) {
            abort(403);
        }

        $plan = DailyPlan::build($user);
        $recommendations = DailyPlan::recommendations($user, 3);

        $continue = null;
        foreach ($plan['tasks'] as $task) {
            if ($task['key'] === 'continue') {
                $continue = $task;
                break;
            }
        }

        return response()
            ->view('today.index', [
                'user' => $user,
                'plan' => $plan,
                'continue' => $continue,
                'recommendations' => $recommendations,
            ])
            ->withHeaders($this->noStore());
    }

    /** @return array<string, string> */
    private function noStore(): array
    {
        return ['Cache-Control' => 'private, no-store'];
    }
}
