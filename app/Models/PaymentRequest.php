<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * S5 payment request (scope §10, §14): server-owned snapshot + state.
 *
 * Status vocabulary from scope §10.1 exactly: awaiting_receipt, pending,
 * approved, rejected, cancelled. Only awaiting_receipt/pending are
 * created or changed in S5; approval/rejection UI is S6. Pending grants
 * no access (S5-3).
 *
 * Mass-assignment boundary (scope §15–16): money and status are written
 * by the server only. The fillable list accepts nothing but the plan
 * choice on create — amount, duration, destination, and status are never
 * fillable and are set via forceFill inside the controllers/actions.
 * Livewire public properties must not carry them either (S5 uses plain
 * Blade forms, no Livewire component).
 *
 * Privacy (scope §10.3, §16): receipt_path, bank_reference, sender_last4,
 * and internal_note are hidden from public serialization and never
 * rendered outside the owner's request page / receipt route.
 */
#[Hidden(['receipt_path', 'bank_reference', 'sender_last4', 'internal_note'])]
class PaymentRequest extends Model
{
    use HasFactory;

    public const STATUS_AWAITING_RECEIPT = 'awaiting_receipt';

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_AWAITING_RECEIPT,
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
    ];

    /** @var list<string> */
    public const OPEN_STATUSES = [
        self::STATUS_AWAITING_RECEIPT,
        self::STATUS_PENDING,
    ];

    /**
     * Only the plan choice may be mass-assigned. Everything else —
     * amount, duration, destination, status, snapshots, receipt, reasons —
     * is set by the server via forceFill.
     */
    protected $fillable = [
        'plan_id',
    ];

    protected function casts(): array
    {
        return [
            'amount_toman_snapshot' => 'integer',
            'duration_days_snapshot' => 'integer',
            'destination_snapshot' => 'array',
            'transferred_at' => 'datetime',
            'receipt_deleted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(PaymentDestination::class, 'destination_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }
}
