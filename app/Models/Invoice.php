<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'customer_id',
        'invoice_date',
        'due_date',
        'total_amount',
        'status',
        'subtotal',
        'tax',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function invoiceDetails(): HasMany
    {
        return $this->hasMany(InvoiceDetail::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getAmountDueAttribute(): float
    {
        return (float) $this->total_amount - (float) $this->payments()->sum('amount');
    }

    /**
     * Recalculate the invoice total amount based on associated trips:
     * sum of (volume * tariff.customer_price).
     */
    public function recalculateTotalAmount(): void
    {
        $total = (float) $this->trips()->with('tariff')->get()->sum(function (Trip $trip) {
            $customerPrice = $trip->tariff ? (float) $trip->tariff->customer_price : 0;

            return (float) $trip->volume * $customerPrice;
        });

        $this->updateQuietly([
            'total_amount' => $total,
            'subtotal' => $total,
        ]);
    }
}
