<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
