<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripExpense extends Model
{
    protected $fillable = [
        'trip_id',
        'coa_id',
        'description',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(Coa::class);
    }
}
