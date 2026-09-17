<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function vendorBills(): HasMany
    {
        return $this->hasMany(VendorBill::class);
    }
}
