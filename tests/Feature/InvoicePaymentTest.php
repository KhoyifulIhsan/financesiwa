<?php

namespace Tests\Feature;

use App\Models\BankCash;
use App\Models\Coa;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoicePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic accounts
        Coa::firstOrCreate(['code' => '1100'], ['name' => 'Kas Kecil Operasional', 'type' => 'asset', 'is_active' => true]);
        $bankCoa = Coa::firstOrCreate(['code' => '1200'], ['name' => 'Bank Utama BCA', 'type' => 'asset', 'is_active' => true]);
        Coa::firstOrCreate(['code' => '1300'], ['name' => 'Piutang Usaha', 'type' => 'asset', 'is_active' => true]);
        Coa::firstOrCreate(['code' => '4000'], ['name' => 'Pendapatan Jasa Angkut BBM', 'type' => 'revenue', 'is_active' => true]);

        BankCash::create([
            'name' => 'Bank BCA Operasional',
            'type' => 'bank',
            'coa_id' => $bankCoa->id,
            'account_number' => '1234567890',
            'current_balance' => 10000000,
        ]);
    }

    public function test_payment_settles_invoice_and_creates_double_entry_journal(): void
    {
        $admin = User::create([
            'name' => 'Finance Staff',
            'email' => 'staff@sinarwardana.com',
            'password' => bcrypt('password'),
        ]);
        Role::firstOrCreate(['name' => 'Superadmin']);
        $admin->assignRole('Superadmin');

        $customer = Customer::create([
            'code' => 'CUST-PAY-01',
            'name' => 'SPBU Cilegon',
            'is_active' => true,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-PAY-001',
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'total_amount' => 5000000,
            'status' => 'UNPAID',
        ]);

        $bankCoa = Coa::where('code', '1200')->first();
        $bankCash = BankCash::where('coa_id', $bankCoa->id)->first();
        $initialBalance = (float) $bankCash->current_balance;

        // Simulasi action terima_pembayaran
        $paymentData = [
            'payment_date' => now()->toDateString(),
            'payment_method' => 'Transfer Bank',
            'bank_account_id' => $bankCoa->id,
            'amount_paid' => 5000000,
        ];

        // Execute logic as handled in action
        DB::transaction(function () use ($paymentData, $invoice) {
            $record = $invoice;
            $data = $paymentData;
            $amountPaid = (float) $data['amount_paid'];

            $record->update(['status' => 'PAID']);

            $arCoaCode = config('accounting.default_ar', '1300');
            $coaPiutang = Coa::firstOrCreate(
                ['code' => $arCoaCode],
                ['name' => 'Piutang Usaha', 'type' => 'asset', 'is_active' => true]
            );

            $journalNumber = 'PAY-'.$record->invoice_number;
            $journal = Journal::create([
                'journal_number' => $journalNumber,
                'date' => $data['payment_date'],
                'reference' => 'Pelunasan Inv #'.$record->invoice_number,
                'description' => 'Penerimaan Pembayaran Invoice '.$record->invoice_number,
                'status' => 'approved',
                'created_by' => null,
            ]);

            JournalDetail::create([
                'journal_id' => $journal->id,
                'coa_id' => $data['bank_account_id'],
                'debit' => $amountPaid,
                'credit' => 0,
                'description' => 'Penerimaan Kas/Bank atas Pelunasan Invoice #'.$record->invoice_number,
            ]);

            JournalDetail::create([
                'journal_id' => $journal->id,
                'coa_id' => $coaPiutang->id,
                'debit' => 0,
                'credit' => $amountPaid,
                'description' => 'Pelunasan Piutang Usaha atas Invoice #'.$record->invoice_number,
            ]);

            $bankCash = BankCash::where('coa_id', $data['bank_account_id'])->first();
            if ($bankCash) {
                $bankCash->increment('current_balance', $amountPaid);
                Payment::create([
                    'invoice_id' => $record->id,
                    'bank_cash_id' => $bankCash->id,
                    'payment_date' => $data['payment_date'],
                    'amount' => $amountPaid,
                    'reference' => $journalNumber,
                ]);
            }
        });

        $invoice->refresh();
        $this->assertEquals('PAID', $invoice->status);

        // Pastikan Jurnal Pelunasan terbuat
        $journal = Journal::where('reference', 'Pelunasan Inv #INV-PAY-001')->first();
        $this->assertNotNull($journal);
        $this->assertEquals('PAY-INV-PAY-001', $journal->journal_number);

        // Validasi detail debit/kredit
        $debitDetail = $journal->details->where('debit', '>', 0)->first();
        $this->assertEquals(5000000, (float) $debitDetail->debit);
        $this->assertEquals($bankCoa->id, $debitDetail->coa_id);

        $creditDetail = $journal->details->where('credit', '>', 0)->first();
        $this->assertEquals(5000000, (float) $creditDetail->credit);
        $this->assertEquals('1300', $creditDetail->coa->code);

        // Pastikan saldo bank bertambah
        $bankCash->refresh();
        $this->assertEquals($initialBalance + 5000000, (float) $bankCash->current_balance);
    }
}
