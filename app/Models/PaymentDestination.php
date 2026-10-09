<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * S5 payment destination (scope §14): card number, holder, bank,
 * transfer instructions, active flag. At most one active row (partial
 * unique index, enforced in the S5 migration).
 *
 * Real card data never lives in the repo: fixtures carry labelled TEST
 * values only (see S5PaymentFixtureSeeder). Production destinations are
 * entered by staff outside version control.
 */
class PaymentDestination extends Model
{
    use HasFactory;

    protected $fillable = [
        'card_number',
        'holder_name',
        'bank_name',
        'instructions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function paymentRequests(): HasMany
    {
        return $this->hasMany(PaymentRequest::class, 'destination_id');
    }
}
