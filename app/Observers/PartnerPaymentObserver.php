<?php

namespace App\Observers;

use App\Models\BankCash;
use App\Models\Coa;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\PartnerPayment;
use Illuminate\Support\Facades\DB;

class PartnerPaymentObserver
{
    /**
     * Handle the PartnerPayment "created" event.
     */
    public function created(PartnerPayment $payment): void
    {
        $amount = (float) $payment->amount;
        if ($amount <= 0) {
            return;
        }

        DB::transaction(function () use ($payment, $amount) {
            // 1. Pastikan CoA 'Hutang Mitra / Investor' tersedia (kode '2150')
            $coaHutangMitra = Coa::firstOrCreate(
                ['code' => '2150'],
                [
                    'name' => 'Hutang Mitra / Investor',
                    'type' => 'liability',
                    'is_active' => true,
                ]
            );

            // 2. Buat entri Jurnal Pengeluaran Kas (Kas Keluar)
            $journalNumber = 'OUT-'.time().'-'.$payment->id;
            if (Journal::where('journal_number', $journalNumber)->exists()) {
                $journalNumber .= '-'.strtoupper(substr(uniqid(), -4));
            }

            $reference = $payment->reference_number ?: ('Pencairan #'.$payment->id);
            $investorName = $payment->investor ? $payment->investor->name : 'Mitra';

            $journal = Journal::create([
                'journal_number' => $journalNumber,
                'date' => $payment->payment_date ?? now(),
                'reference' => $reference,
                'description' => 'Pencairan Bagi Hasil Mitra: '.$investorName.($payment->notes ? ' ('.$payment->notes.')' : ''),
                'status' => 'approved',
                'created_by' => auth()->id(),
            ]);

            // 3. Buat 2 baris journal_details
            // DEBIT: Hutang Mitra / Investor (mengurangi hutang di Debit)
            JournalDetail::create([
                'journal_id' => $journal->id,
                'coa_id' => $coaHutangMitra->id,
                'debit' => $amount,
                'credit' => 0,
                'description' => 'Pelunasan Hutang Bagi Hasil Mitra: '.$investorName,
            ]);

            // KREDIT: Rekening Kas/Bank yang dipilih user (mengurangi kas di Kredit)
            JournalDetail::create([
                'journal_id' => $journal->id,
                'coa_id' => $payment->bank_account_id,
                'debit' => 0,
                'credit' => $amount,
                'description' => 'Pengeluaran Kas/Bank untuk Pembayaran Mitra: '.$investorName,
            ]);

            // 4. Sinkronisasi saldo rekening kas/bank jika akun terdaftar di data BankCash
            $bankCash = BankCash::where('coa_id', $payment->bank_account_id)->first();
            if ($bankCash) {
                $bankCash->decrement('current_balance', $amount);
            }
        });
    }
}
