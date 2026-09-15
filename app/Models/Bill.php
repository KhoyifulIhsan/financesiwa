<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    protected $fillable = [
        'bill_number',
        'vendor_id',
        'bill_date',
        'date',
        'due_date',
        'status',
        'total_amount',
        'paid_amount',
        'notes',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (Bill $bill) {
            if (empty($bill->date) && ! empty($bill->bill_date)) {
                $bill->date = $bill->bill_date;
            }
            if (empty($bill->bill_date) && ! empty($bill->date)) {
                $bill->bill_date = $bill->date;
            }
        });
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function billDetails(): HasMany
    {
        return $this->hasMany(BillDetail::class);
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(0, (float) $this->total_amount - (float) $this->paid_amount);
    }

    public function getAmountDueAttribute(): float
    {
        return $this->remaining_amount;
    }
}
