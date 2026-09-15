<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'invoice_id',
        'bank_cash_id',
        'payment_date',
        'amount',
        'reference',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function bankCash()
    {
        return $this->belongsTo(BankCash::class);
    }
}
