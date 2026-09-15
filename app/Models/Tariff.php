<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tariff extends Model
{
    protected $fillable = [
        'name',
        'customer_price',
        'fee_type',
        'pt_margin',
    ];

    protected $casts = [
        'customer_price' => 'decimal:2',
        'pt_margin' => 'decimal:2',
    ];
}
