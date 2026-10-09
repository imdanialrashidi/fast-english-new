<?php

namespace App\Http\Controllers;

use App\Actions\PublishPlacementTest;
use App\Models\PlacementTest;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * S8 placement management (scope §5: staff «مدیریت آزمون»).
 *
 * Publishing goes through the SAME shared PublishPlacementTest action the
 * `placement:publish` CLI uses — the Filament page posts here and this
 * controller delegates without copying any rule. Every route is
 * staff-only (403 for students); the answer key never leaves the staff
 * panel (no public route renders correct_option or scoring_rules).
 */
class StaffPlacementController extends Controller
{
    public function publish(Request $request, PlacementTest $test)
    {
        try {
            $result = PublishPlacementTest::publish($test);
        } catch (ValidationException $e) {
            return back()
                ->withErrors(collect($e->errors())->mapWithKeys(fn ($messages, $field) => [$field => (array) $messages])->all());
        }

        return back()->with('status', "نسخه {$result->version} منتشر و جاری شد.");
    }
}
