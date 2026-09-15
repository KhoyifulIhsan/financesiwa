<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $fillable = [
        'code',
        'name',
        'address',
        'phone',
        'type',
        'is_active',
    ];
}
