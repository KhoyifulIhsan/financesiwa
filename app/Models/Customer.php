<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'code',
        'name',
        'address',
        'phone',
        'email',
        'is_active',
    ];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
