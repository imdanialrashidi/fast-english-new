<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * S7 placement question (scope §12, §14): exactly 20 per published
 * version, four options, one correct answer.
 *
 * correct_option is the server-only answer key (0–3) — hidden from
 * serialization and never passed to views, Livewire state, or JSON.
 * Every seeded prompt/option row is marked FIXTURE; no real exam content
 * is written. Questions of a published version are immutable.
 */
#[Hidden(['correct_option'])]
class PlacementQuestion extends Model
{
    use HasFactory;

    /** Server-written only; writers use forceFill. */
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'correct_option' => 'integer',
            'position' => 'integer',
        ];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(PlacementTest::class, 'test_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(PlacementAnswer::class, 'question_id');
    }

    protected static function booted(): void
    {
        $guard = function (PlacementQuestion $question) {
            $test = $question->test()->first();
            if ($test !== null && $test->status === PlacementTest::STATUS_PUBLISHED) {
                throw new \RuntimeException('Questions of a published placement version are immutable.');
            }
        };

        static::saving($guard);
        static::deleting(function (PlacementQuestion $question) {
            $test = $question->test()->first();
            if ($test !== null && $test->status === PlacementTest::STATUS_PUBLISHED) {
                throw new \RuntimeException('Questions of a published placement version cannot be deleted.');
            }
            if ($question->answers()->exists()) {
                throw new \RuntimeException('Questions with answers cannot be deleted.');
            }
        });
    }
}
