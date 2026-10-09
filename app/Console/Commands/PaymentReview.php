<?php

namespace App\Console\Commands;

use App\Actions\ApprovePayment;
use App\Models\PaymentRequest;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

/**
 * S6 payment review CLI (scope §11.2): the non-panel path through the same
 * shared ApprovePayment action the Filament queue uses. Approve is the
 * only path that grants access; reject/cancel/grant/revoke share the same
 * lock order and mandatory reasons.
 */
class PaymentReview extends Command
{
    protected $signature = 'payment:review
        {action : approve|reject|cancel|grant|revoke}
        {target : payment request ID for approve/reject/cancel, user ID for grant/revoke}
        {--by= : Staff email performing the review (required)}
        {--reason= : Reason (required for cancel/grant/revoke)}
        {--public-reason= : Public reason shown to the learner (required for reject)}
        {--internal-note= : Staff-only note for reject}
        {--duration= : Duration in days for grant}';

    protected $description = 'Review a payment request or subscription through the shared approval action.';

    public function handle(): int
    {
        $action = (string) $this->argument('action');
        $target = (int) $this->argument('target');
        $by = trim((string) $this->option('by'));

        if ($by === '') {
            $this->error('The --by option is required: staff email performing the review.');

            return 1;
        }

        $staff = User::where('email', $by)->first();
        if ($staff === null) {
            $this->error("Staff [{$by}] not found.");

            return 1;
        }

        try {
            match ($action) {
                'approve' => $this->reportApprove(ApprovePayment::approve($target, $staff)),
                'reject' => $this->reportRequest(ApprovePayment::reject(
                    $target,
                    $staff,
                    (string) $this->option('public-reason'),
                    $this->option('internal-note') !== null ? (string) $this->option('internal-note') : null,
                )),
                'cancel' => $this->reportRequest(ApprovePayment::cancel($target, $staff, (string) $this->option('reason'))),
                'grant' => $this->reportGrant(ApprovePayment::grant($target, $staff, (int) $this->option('duration'), (string) $this->option('reason'))),
                'revoke' => $this->reportGrant(ApprovePayment::revoke($target, $staff, (string) $this->option('reason'))),
                default => throw ValidationException::withMessages(['action' => 'Unknown action. Use approve, reject, cancel, grant, or revoke.']),
            };
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ((array) $messages as $message) {
                    $this->error("{$field}: {$message}");
                }
            }

            return 1;
        }

        return 0;
    }

    /** @param array{request: PaymentRequest, replayed: bool} $outcome */
    private function reportApprove(array $outcome): void
    {
        $request = $outcome['request'];
        $this->info($outcome['replayed']
            ? "Request [{$request->id}] already approved; no new event, no extension."
            : "Request [{$request->id}] approved.");
    }

    private function reportRequest(PaymentRequest $request): void
    {
        $this->info("Request [{$request->id}] is now {$request->status}.");
    }

    /** @param array{subscription: Subscription} $outcome */
    private function reportGrant(array $outcome): void
    {
        $subscription = $outcome['subscription'];
        $this->info("Subscription [{$subscription->id}] updated; expires {$subscription->expires_at->toIso8601String()}.");
    }
}
