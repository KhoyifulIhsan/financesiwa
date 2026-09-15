<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'customer_id',
        'issue_date',
        'due_date',
        'subtotal',
        'tax',
        'total_amount',
        'status',
        'notes',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoiceDetails()
    {
        return $this->hasMany(InvoiceDetail::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function getAmountDueAttribute()
    {
        return (float) $this->total_amount - (float) $this->payments()->sum('amount');
    }
}
