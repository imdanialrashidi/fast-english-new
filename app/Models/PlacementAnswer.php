<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * S7 placement answer (scope §14): one selected option per
 * (attempt, question), saved per question on the server for resume.
 *
 * is_correct is server-side marking filled at submit only — hidden from
 * serialization and never rendered before or after (the result shows the
 * total score and suggested level, never per-question correctness or the
 * answer key).
 */
#[Hidden(['is_correct'])]
class PlacementAnswer extends Model
{
    use HasFactory;

    /** Server-written only; writers use forceFill. */
    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'selected_option' => 'integer',
            'is_correct' => 'boolean',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(PlacementAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(PlacementQuestion::class, 'question_id');
    }
}
