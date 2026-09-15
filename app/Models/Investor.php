<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Investor extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'bank_account_info',
    ];

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }
}
