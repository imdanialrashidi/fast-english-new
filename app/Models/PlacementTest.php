<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * S7 placement test version (scope §12, §14).
 *
 * One published version is current at a time. Editing creates a new
 * draft; published versions and their questions are immutable (guarded
 * in the model + PublishPlacementTest action). scoring_rules is
 * server-only FIXTURE data — hidden and never passed to views or JSON.
 */
#[Hidden(['scoring_rules'])]
class PlacementTest extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    /** @var list<string> */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PUBLISHED,
        self::STATUS_ARCHIVED,
    ];

    /** Server-written only; writers use forceFill. */
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'scoring_rules' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(PlacementQuestion::class, 'test_id')->orderBy('position');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(PlacementAttempt::class, 'test_id');
    }

    /**
     * Immutability guard: a published version keeps its version, status,
     * and scoring rules. Only is_current may toggle (via the publish
     * action, which retires the previous current). Draft edits happen
     * before publishing; post-publish edits must create a new draft.
     */
    protected static function booted(): void
    {
        static::saving(function (PlacementTest $test) {
            if (! $test->exists) {
                return;
            }

            $original = $test->getOriginal();
            if (($original['status'] ?? null) !== self::STATUS_PUBLISHED) {
                return;
            }

            foreach (['version', 'status', 'scoring_rules'] as $field) {
                if ($test->isDirty($field)) {
                    throw new \RuntimeException('Published placement versions are immutable; create a new draft instead.');
                }
            }
        });

        static::deleting(function (PlacementTest $test) {
            if ($test->status === self::STATUS_PUBLISHED && $test->attempts()->exists()) {
                throw new \RuntimeException('Published placement versions with attempts cannot be deleted; archive instead.');
            }
        });
    }
}
