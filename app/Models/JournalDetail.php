<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalDetail extends Model
{
    protected $fillable = [
        'journal_id',
        'coa_id',
        'debit',
        'credit',
        'description',
    ];

    public function journal(): BelongsTo
    {
        return $this->belongsTo(Journal::class);
    }

    public function coa(): BelongsTo
    {
        return $this->belongsTo(Coa::class);
    }
}
