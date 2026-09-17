<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorBill extends Model
{
    protected $attributes = [
        'status' => 'DRAFT',
        'total_amount' => 0,
    ];

    protected $fillable = [
        'vendor_id',
        'bill_number',
        'bill_date',
        'due_date',
        'expense_account_id',
        'total_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Coa::class, 'expense_account_id');
    }
}
