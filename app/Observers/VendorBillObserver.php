<?php

namespace App\Observers;

use App\Models\Coa;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\VendorBill;
use Illuminate\Support\Facades\DB;

class VendorBillObserver
{
    /**
     * Handle the VendorBill "created" event.
     */
    public function created(VendorBill $bill): void
    {
        if (strtoupper((string) $bill->status) === 'UNPAID') {
            $this->createAutomaticJournal($bill);
        }
    }

    /**
     * Handle the VendorBill "updated" event.
     */
    public function updated(VendorBill $bill): void
    {
        $originalStatus = strtoupper((string) $bill->getOriginal('status'));
        $currentStatus = strtoupper((string) $bill->status);

        // Jika status berubah menjadi 'UNPAID'
        if ($originalStatus !== 'UNPAID' && $currentStatus === 'UNPAID') {
            $this->createAutomaticJournal($bill);
        }
    }

    /**
     * Generate double-entry journal for unpaid vendor bill (expense recognition & AP).
     */
    protected function createAutomaticJournal(VendorBill $bill): void
    {
        $totalAmount = (float) $bill->total_amount;

        if ($totalAmount <= 0 || ! $bill->expense_account_id) {
            return;
        }

        $reference = 'Tagihan Vendor #'.$bill->bill_number;

        // Cegah duplikasi jurnal untuk tagihan yang sama
        if (Journal::where('reference', $reference)->exists()) {
            return;
        }

        DB::transaction(function () use ($bill, $totalAmount, $reference) {
            $apCoaCode = config('accounting.default_ap', '2100');
            $coaHutangUsaha = Coa::firstOrCreate(
                ['code' => $apCoaCode],
                [
                    'name' => 'Hutang Usaha',
                    'type' => 'liability',
                    'is_active' => true,
                ]
            );

            $vendorName = $bill->vendor?->name ?? 'Vendor';
            $journalNumber = 'BILL-'.$bill->bill_number;
            if (Journal::where('journal_number', $journalNumber)->exists()) {
                $journalNumber .= '-'.strtoupper(substr(uniqid(), -4));
            }

            $journal = Journal::create([
                'journal_number' => $journalNumber,
                'date' => $bill->bill_date ?? now()->toDateString(),
                'reference' => $reference,
                'description' => 'Pengakuan Tagihan Vendor '.$bill->bill_number.' ('.$vendorName.')',
                'status' => 'approved',
                'created_by' => auth()->id(),
            ]);

            // DEBIT: Akun Beban / Aset yang dipilih user di expense_account_id
            JournalDetail::create([
                'journal_id' => $journal->id,
                'coa_id' => $bill->expense_account_id,
                'debit' => $totalAmount,
                'credit' => 0,
                'description' => 'Beban Tagihan Vendor #'.$bill->bill_number,
            ]);

            // KREDIT: Hutang Usaha (Accounts Payable)
            JournalDetail::create([
                'journal_id' => $journal->id,
                'coa_id' => $coaHutangUsaha->id,
                'debit' => 0,
                'credit' => $totalAmount,
                'description' => 'Hutang Usaha Tagihan Vendor #'.$bill->bill_number,
            ]);
        });
    }

    /**
     * Handle the VendorBill "deleted" event.
     */
    public function deleted(VendorBill $bill): void
    {
        $reference = 'Tagihan Vendor #'.$bill->bill_number;
        $journal = Journal::where('reference', $reference)->first();
        if ($journal) {
            $journal->delete();
        }

        $payReference = 'Pelunasan Tagihan #'.$bill->bill_number;
        $payJournal = Journal::where('reference', $payReference)->first();
        if ($payJournal) {
            $payJournal->delete();
        }
    }
}
