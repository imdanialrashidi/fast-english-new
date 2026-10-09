<?php

namespace App\Console\Commands;

use App\Models\PaymentRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * S5 orphan receipt cleanup (scope §10.3): the file is written before the
 * DB mutation, so a crashed request can leave a file with no row. This
 * command deletes every file under receipts/ on the private disk that no
 * payment_requests row references. Rows with receipt_deleted_at set keep
 * their (already removed) path out of scope — only unreferenced files go.
 *
 * Usage: php artisan receipts:cleanup [--dry-run]
 */
class CleanupOrphanReceipts extends Command
{
    protected $signature = 'receipts:cleanup {--dry-run : List orphaned files without deleting them}';

    protected $description = 'Delete orphaned receipt files with no payment_requests row.';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $files = $disk->allFiles('receipts');

        if ($files === []) {
            $this->info('No receipt files found.');

            return self::SUCCESS;
        }

        $referenced = PaymentRequest::whereNotNull('receipt_path')
            ->pluck('receipt_path')
            ->all();
        $referenced = array_flip($referenced);

        $orphans = array_values(array_filter(
            $files,
            fn (string $file) => ! isset($referenced[$file])
        ));

        if ($orphans === []) {
            $this->info('No orphaned receipt files.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            foreach ($orphans as $orphan) {
                $this->line($orphan);
            }
            $this->info(count($orphans).' orphaned file(s) found (dry run).');

            return self::SUCCESS;
        }

        $deleted = 0;
        foreach ($orphans as $orphan) {
            if ($disk->delete($orphan)) {
                $deleted++;
            }
        }

        $this->info("Deleted {$deleted} orphaned receipt file(s).");

        return self::SUCCESS;
    }
}
