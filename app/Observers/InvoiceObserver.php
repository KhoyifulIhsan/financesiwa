<?php

namespace App\Observers;

use App\Models\Coa;
use App\Models\Invoice;
use App\Models\Journal;
use App\Models\JournalDetail;
use Illuminate\Support\Facades\DB;

class InvoiceObserver
{
    /**
     * Handle the Invoice "created" event.
     */
    public function created(Invoice $invoice): void
    {
        if (strtoupper((string) $invoice->status) === 'UNPAID') {
            $this->createAutomaticJournal($invoice);
        }
    }

    /**
     * Handle the Invoice "updated" event.
     */
    public function updated(Invoice $invoice): void
    {
        $originalStatus = strtoupper((string) $invoice->getOriginal('status'));
        $currentStatus = strtoupper((string) $invoice->status);

        // Jika status berubah dari 'DRAFT' menjadi 'UNPAID'
        if ($originalStatus === 'DRAFT' && $currentStatus === 'UNPAID') {
            $this->createAutomaticJournal($invoice);
        }
    }

    /**
     * Generate double-entry journal for unpaid invoice.
     */
    protected function createAutomaticJournal(Invoice $invoice): void
    {
        $totalAmount = (float) $invoice->total_amount;

        // Jangan buat jurnal jika total amount 0
        if ($totalAmount <= 0) {
            return;
        }

        $reference = 'Invoice #'.$invoice->invoice_number;

        // Cegah duplikasi jurnal untuk invoice yang sama
        if (Journal::where('reference', $reference)->exists()) {
            return;
        }

        DB::transaction(function () use ($invoice, $totalAmount, $reference) {
            // 1. Pastikan CoA Piutang Usaha (Aset) & Pendapatan Jasa (Pendapatan) tersedia
            $arCoaCode = config('accounting.default_ar', '1300');
            $coaPiutang = Coa::firstOrCreate(
                ['code' => $arCoaCode],
                [
                    'name' => 'Piutang Usaha',
                    'type' => 'asset',
                    'is_active' => true,
                ]
            );

            $coaPendapatan = Coa::firstOrCreate(
                ['code' => '4000'],
                [
                    'name' => 'Pendapatan Jasa Angkut BBM',
                    'type' => 'revenue',
                    'is_active' => true,
                ]
            );

            // 2. Buat baris di tabel journals
            $journal = Journal::create([
                'journal_number' => 'JRN-INV-'.strtoupper(uniqid()),
                'date' => $invoice->invoice_date ?? now()->toDateString(),
                'reference' => $reference,
                'description' => 'Penagihan Piutang Invoice '.$invoice->invoice_number.' ('.($invoice->customer?->name ?? 'Pelanggan').')',
                'status' => 'approved',
                'created_by' => auth()->id(),
            ]);

            // 3. Buat dua baris di journal_details
            // DEBIT: Piutang Usaha (Aset bertambah)
            JournalDetail::create([
                'journal_id' => $journal->id,
                'coa_id' => $coaPiutang->id,
                'debit' => $totalAmount,
                'credit' => 0,
                'description' => 'Piutang Usaha Inv #'.$invoice->invoice_number,
            ]);

            // KREDIT: Pendapatan Jasa (Pendapatan bertambah)
            JournalDetail::create([
                'journal_id' => $journal->id,
                'coa_id' => $coaPendapatan->id,
                'debit' => 0,
                'credit' => $totalAmount,
                'description' => 'Pendapatan Jasa Inv #'.$invoice->invoice_number,
            ]);
        });
    }
}
