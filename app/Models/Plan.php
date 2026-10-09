<?php

namespace App\Models;

use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * S5 plan (scope §14): slug unique, Persian name, integer toman price,
 * duration in exact days, active flag, display order.
 *
 * Money is integer toman (scope §8): no floats, no rial conversion.
 * Amount/duration are written by the server only — never mass-assigned
 * from learner input (see PaymentRequest snapshot rules).
 */
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'name_fa',
        'price_toman',
        'duration_days',
        'is_active',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'price_toman' => 'integer',
            'duration_days' => 'integer',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class);
    }
}
