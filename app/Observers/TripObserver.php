<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\Trip;

class TripObserver
{
    /**
     * Handle the Trip "saved" event.
     */
    public function saved(Trip $trip): void
    {
        // Jika trip baru dibuat dengan invoice_id
        if ($trip->wasRecentlyCreated && $trip->invoice_id) {
            Invoice::find($trip->invoice_id)?->recalculateTotalAmount();

            return;
        }

        // Jika relasi invoice_id berubah atau data volume/tarif berubah
        if ($trip->wasChanged('invoice_id') || $trip->wasChanged('volume') || $trip->wasChanged('tariff_id')) {
            // Hitung ulang invoice saat ini
            if ($trip->invoice_id) {
                Invoice::find($trip->invoice_id)?->recalculateTotalAmount();
            }

            // Jika sebelumnya tertaut ke invoice lain, hitung ulang invoice lama juga
            $originalInvoiceId = $trip->getOriginal('invoice_id');
            if ($originalInvoiceId && $originalInvoiceId !== $trip->invoice_id) {
                Invoice::find($originalInvoiceId)?->recalculateTotalAmount();
            }
        }
    }

    /**
     * Handle the Trip "deleted" event.
     */
    public function deleted(Trip $trip): void
    {
        if ($trip->invoice_id) {
            Invoice::find($trip->invoice_id)?->recalculateTotalAmount();
        }
    }
}
