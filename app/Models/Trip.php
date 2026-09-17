<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trip extends Model
{
    protected $fillable = [
        'trip_number',
        'date',
        'customer_id',
        'tariff_id',
        'invoice_id',
        'partner_payment_id',
        'vehicle_id',
        'driver_id',
        'route_origin',
        'route_destination',
        'volume',
        'mitra_share_amount',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'volume' => 'decimal:2',
        'mitra_share_amount' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function tariff(): BelongsTo
    {
        return $this->belongsTo(Tariff::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function partnerPayment(): BelongsTo
    {
        return $this->belongsTo(PartnerPayment::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function tripExpenses(): HasMany
    {
        return $this->hasMany(TripExpense::class);
    }

    public function driverAdvances(): HasMany
    {
        return $this->hasMany(DriverAdvance::class);
    }

    public function getTotalExpensesAttribute(): float
    {
        return (float) $this->tripExpenses()->sum('amount');
    }

    public function getTotalRevenueAttribute(): float
    {
        $customerPrice = $this->tariff ? (float) $this->tariff->customer_price : 0;

        return (float) $this->volume * $customerPrice;
    }
}
