<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends Model
{
    protected $attributes = [
        'status' => 'aktif',
        'ownership_status' => 'Milik PT',
    ];

    protected $fillable = [
        'license_plate',
        'capacity',
        'driver_name',
        'status',
        'ownership_status',
        'investor_id',
    ];

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }
}
