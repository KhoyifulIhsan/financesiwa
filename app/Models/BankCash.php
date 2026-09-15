<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankCash extends Model
{
    protected $fillable = [
        'coa_id',
        'name',
        'type',
        'account_number',
        'current_balance',
    ];

    public function coa(): BelongsTo
    {
        return $this->belongsTo(Coa::class);
    }
}
