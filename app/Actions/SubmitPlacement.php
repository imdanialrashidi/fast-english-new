<?php

namespace App\Actions;

use App\Models\PlacementAnswer;
use App\Models\PlacementAttempt;
use App\Models\PlacementQuestion;
use App\Support\PlacementLevel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * S7 shared submit owner (scope §12): server-side marking with
 * idempotent replay and row-locked race safety.
 *
 * The placement controller AND the `placement:submit` CLI both call
 * submit(); the logic is never copied into callers. Steps in one
 * transaction: lock the attempt → replay completed as-is → load the
 * pinned version's 20 question ids → require exactly those 20 answers
 * with no duplicates or foreign questions (a partial submit stores no
 * result) → mark each answer server-side → write score +
 * recommended_level (only here, at completion) → complete.
 */
final class SubmitPlacement
{
    /**
     * @return array{attempt: PlacementAttempt, replayed: bool}
     *
     * @throws ValidationException
     */
    public static function submit(int $attemptId): array
    {
        return DB::transaction(function () use ($attemptId) {
            $row = PlacementAttempt::where('id', $attemptId)->lockForUpdate()->firstOrFail();

            if ($row->status === PlacementAttempt::STATUS_COMPLETED) {
                return ['attempt' => $row, 'replayed' => true];
            }

            if ($row->status !== PlacementAttempt::STATUS_IN_PROGRESS) {
                throw ValidationException::withMessages(['attempt' => 'Only an open attempt can be submitted.']);
            }

            $questionIds = PlacementQuestion::where('test_id', $row->test_id)
                ->orderBy('position')
                ->pluck('id')
                ->all();

            $answers = PlacementAnswer::where('attempt_id', $row->id)->lockForUpdate()->get();

            if ($answers->count() !== 20 || count($questionIds) !== 20) {
                throw ValidationException::withMessages(['answers' => 'پاسخ همه ۲۰ سؤال برای ثبت نتیجه الزامی است.']);
            }

            $answerQuestionIds = $answers->pluck('question_id')->all();
            sort($answerQuestionIds);
            $sortedQuestions = $questionIds;
            sort($sortedQuestions);
            if ($answerQuestionIds !== $sortedQuestions) {
                throw ValidationException::withMessages(['answers' => 'پاسخ‌ها باید دقیقاً متعلق به همین آزمون باشند.']);
            }

            $key = PlacementQuestion::where('test_id', $row->test_id)
                ->pluck('correct_option', 'id')
                ->all();

            $score = 0;
            foreach ($answers as $answer) {
                $correct = (int) ($key[$answer->question_id] ?? -1) === (int) $answer->selected_option;
                $answer->forceFill(['is_correct' => $correct])->save();
                if ($correct) {
                    $score++;
                }
            }

            $row->forceFill([
                'status' => PlacementAttempt::STATUS_COMPLETED,
                'completed_at' => now(),
                'score' => $score,
                'recommended_level' => PlacementLevel::forScore($score),
            ]);
            $row->save();

            return ['attempt' => $row->fresh(), 'replayed' => false];
        });
    }
}
