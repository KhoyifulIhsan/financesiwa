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

        $this->createPaymentJournal($payment, $amount);
    }

    /**
     * Handle the PartnerPayment "updated" event.
     */
    public function updated(PartnerPayment $payment): void
    {
        $newAmount = (float) $payment->amount;
        $oldAmount = (float) $payment->getOriginal('amount');

        if ($newAmount == $oldAmount && ! $payment->wasChanged(['bank_account_id', 'reference_number'])) {
            return;
        }

        $existingJournal = Journal::where('reference', $payment->reference_number)
            ->orWhere('reference', 'Pencairan #'.$payment->id)
            ->first();

        if (! $existingJournal) {
            if ($newAmount > 0) {
                $this->createPaymentJournal($payment, $newAmount);
            }

            return;
        }

        DB::transaction(function () use ($payment, $existingJournal, $newAmount, $oldAmount) {
            $difference = $newAmount - $oldAmount;
            $reference = $payment->reference_number ?: ('Pencairan #'.$payment->id);
            $investorName = $payment->investor ? $payment->investor->name : 'Mitra';

            $existingJournal->update([
                'reference' => $reference,
                'description' => 'Pencairan Bagi Hasil Mitra: '.$investorName.($payment->notes ? ' ('.$payment->notes.')' : ''),
                'date' => $payment->payment_date ?? now(),
            ]);

            $coaHutangMitra = Coa::firstOrCreate(
                ['code' => '2150'],
                [
                    'name' => 'Hutang Mitra / Investor',
                    'type' => 'liability',
                    'is_active' => true,
                ]
            );

            // Update details
            foreach ($existingJournal->details as $detail) {
                if ($detail->coa_id == $coaHutangMitra->id) {
                    $detail->update(['debit' => $newAmount]);
                } else {
                    $detail->update([
                        'coa_id' => $payment->bank_account_id,
                        'credit' => $newAmount,
                    ]);
                }
            }

            // Adjust BankCash
            if ($difference != 0) {
                $bankCash = BankCash::where('coa_id', $payment->bank_account_id)->first();
                if ($bankCash) {
                    $bankCash->decrement('current_balance', $difference);
                }
            }
        });
    }

    /**
     * Handle the PartnerPayment "deleted" event.
     */
    public function deleted(PartnerPayment $payment): void
    {
        $existingJournal = Journal::where('reference', $payment->reference_number)
            ->orWhere('reference', 'Pencairan #'.$payment->id)
            ->first();

        if ($existingJournal) {
            DB::transaction(function () use ($payment, $existingJournal) {
                $amount = (float) $payment->amount;
                if ($amount > 0) {
                    $bankCash = BankCash::where('coa_id', $payment->bank_account_id)->first();
                    if ($bankCash) {
                        $bankCash->increment('current_balance', $amount);
                    }
                }

                $existingJournal->delete();
            });
        }
    }

    protected function createPaymentJournal(PartnerPayment $payment, float $amount): void
    {
        DB::transaction(function () use ($payment, $amount) {
            $coaHutangMitra = Coa::firstOrCreate(
                ['code' => '2150'],
                [
                    'name' => 'Hutang Mitra / Investor',
                    'type' => 'liability',
                    'is_active' => true,
                ]
            );

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

            JournalDetail::create([
                'journal_id' => $journal->id,
                'coa_id' => $coaHutangMitra->id,
                'debit' => $amount,
                'credit' => 0,
                'description' => 'Pelunasan Hutang Bagi Hasil Mitra: '.$investorName,
            ]);

            JournalDetail::create([
                'journal_id' => $journal->id,
                'coa_id' => $payment->bank_account_id,
                'debit' => 0,
                'credit' => $amount,
                'description' => 'Pengeluaran Kas/Bank untuk Pembayaran Mitra: '.$investorName,
            ]);

            $bankCash = BankCash::where('coa_id', $payment->bank_account_id)->first();
            if ($bankCash) {
                $bankCash->decrement('current_balance', $amount);
            }
        });
    }
}
