<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    protected $fillable = [
        'journal_number',
        'date',
        'reference',
        'description',
        'status',
        'created_by',
    ];

    public function details(): HasMany
    {
        return $this->hasMany(JournalDetail::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function setStatusAttribute($value): void
    {
        $this->attributes['status'] = match (strtolower((string) $value)) {
            'posted', 'approved' => 'approved',
            default => 'draft',
        };
    }
}
