<?php

namespace App\Console\Commands;

use App\Models\PaymentRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * S8 receipt retention (scope §14, §19.3): reviewed receipts older than
 * 90 days lose their FILE; the financial ROW stays.
 *
 * Qualifies: receipt_path set, receipt_deleted_at null, status in
 * approved/rejected/cancelled, and reviewed_at (or cancelled_at when
 * reviewed_at is null) older than 90 days. Pending requests are never
 * touched — the status filter excludes them by construction.
 *
 * Effect per row: delete the private file when present, set
 * receipt_deleted_at = now. receipt_path is kept as the audit reference;
 * the receipt route 404s once receipt_deleted_at is set.
 */
class RetainReviewedReceipts extends Command
{
    protected $signature = 'receipts:retain-reviewed {--dry-run : List qualifying rows without deleting files}';

    protected $description = 'Delete files of receipts reviewed more than 90 days ago; keep the financial rows.';

    public function handle(): int
    {
        $cutoff = now()->subDays(90);

        $rows = PaymentRequest::whereNotNull('receipt_path')
            ->whereNull('receipt_deleted_at')
            ->whereIn('status', [
                PaymentRequest::STATUS_APPROVED,
                PaymentRequest::STATUS_REJECTED,
                PaymentRequest::STATUS_CANCELLED,
            ])
            ->where(function ($query) use ($cutoff) {
                $query->where('reviewed_at', '<=', $cutoff)
                    ->orWhere(function ($query) use ($cutoff) {
                        $query->whereNull('reviewed_at')->where('cancelled_at', '<=', $cutoff);
                    });
            })
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No receipts qualify for retention cleanup.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            foreach ($rows as $row) {
                $this->line("payment_request {$row->id} status={$row->status}");
            }
            $this->info(count($rows).' receipt(s) qualify (dry run).');

            return self::SUCCESS;
        }

        $disk = Storage::disk('local');
        $removed = 0;
        foreach ($rows as $row) {
            $path = (string) $row->receipt_path;
            if ($path !== '' && $disk->exists($path)) {
                $disk->delete($path);
            }
            $row->forceFill(['receipt_deleted_at' => now()])->save();
            $removed++;
        }

        $this->info("Retained {$removed} receipt row(s); files removed.");

        return self::SUCCESS;
    }
}
