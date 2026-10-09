<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * S7 placement attempt (scope §12, §14): pinned to one test version at
 * start. One in_progress attempt per user (partial unique index); a new
 * start within 24 hours of the last start is refused; resuming the open
 * attempt is allowed.
 *
 * recommended_level is written only at completion (submit); browsing
 * never writes it or users.preferred_level. Accepting the result sets
 * users.preferred_level explicitly — the only writer besides settings.
 */
class PlacementAttempt extends Model
{
    use HasFactory;

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_ABANDONED = 'abandoned';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_IN_PROGRESS,
        self::STATUS_COMPLETED,
        self::STATUS_ABANDONED,
    ];

    /** Server-written only; writers use forceFill. */
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'score' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(PlacementTest::class, 'test_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(PlacementAnswer::class, 'attempt_id');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }
}
