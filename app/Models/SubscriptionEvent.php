<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * S6 immutable audit row (scope §11.1, §14): one per approve or manual
 * grant/revoke, written inside the same transaction as the window change.
 * A payment request sources at most one event (unique-when-not-null
 * source_payment_request_id); manual events carry NULL there.
 *
 * Immutable: created once, never updated — the table has created_at only.
 */
class SubscriptionEvent extends Model
{
    public const TYPE_APPROVED = 'approved';

    public const TYPE_GRANTED = 'granted';

    public const TYPE_REVOKED = 'revoked';

    /** @var list<string> */
    public const TYPES = [
        self::TYPE_APPROVED,
        self::TYPE_GRANTED,
        self::TYPE_REVOKED,
    ];

    /** No updated_at: audit rows are never modified. */
    public $timestamps = false;

    /** Server-written only; the action uses forceFill. */
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'before_starts_at' => 'datetime',
            'before_expires_at' => 'datetime',
            'after_starts_at' => 'datetime',
            'after_expires_at' => 'datetime',
            'duration_days' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
