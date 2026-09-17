<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PartnerPayment extends Model
{
    protected $fillable = [
        'investor_id',
        'payment_date',
        'amount',
        'bank_account_id',
        'reference_number',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Coa::class, 'bank_account_id');
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * Recalculate the payment amount based on associated trips:
     * sum of mitra_share_amount.
     */
    public function recalculateAmount(): void
    {
        $total = (float) $this->trips()->sum('mitra_share_amount');

        $this->update([
            'amount' => $total,
        ]);
    }
}
