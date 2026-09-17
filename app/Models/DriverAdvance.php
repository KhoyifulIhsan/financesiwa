<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverAdvance extends Model
{
    protected $attributes = [
        'status' => 'DRAFT',
        'amount' => 0,
    ];

    protected $fillable = [
        'trip_id',
        'driver_id',
        'amount',
        'issue_date',
        'bank_account_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(Coa::class, 'bank_account_id');
    }
}
