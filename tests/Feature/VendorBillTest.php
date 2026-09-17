<?php

namespace Tests\Feature;

use App\Models\BankCash;
use App\Models\Coa;
use App\Models\Journal;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VendorBillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Akun Bank & Beban & Hutang
        Coa::firstOrCreate(['code' => '1200'], ['name' => 'Bank Mandiri Utama', 'type' => 'asset', 'is_active' => true]);
        Coa::firstOrCreate(['code' => '2100'], ['name' => 'Hutang Usaha', 'type' => 'liability', 'is_active' => true]);
        Coa::firstOrCreate(['code' => '5100'], ['name' => 'Beban Solar Kendaraan', 'type' => 'expense', 'is_active' => true]);
        Coa::firstOrCreate(['code' => '5200'], ['name' => 'Biaya Servis & Bengkel', 'type' => 'expense', 'is_active' => true]);
    }

    public function test_vendor_bill_can_be_created_with_default_draft_status(): void
    {
        $vendor = Vendor::create([
            'code' => 'VEND-001',
            'name' => 'Bengkel Mobil Sentosa',
            'phone' => '0812345678',
            'type' => 'maintenance',
            'is_active' => true,
        ]);

        $expenseCoa = Coa::where('code', '5200')->first();

        $bill = VendorBill::create([
            'vendor_id' => $vendor->id,
            'bill_number' => 'V-BILL-20260917-001',
            'bill_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'expense_account_id' => $expenseCoa->id,
            'total_amount' => 2500000,
            'notes' => 'Ganti oli dan kampas rem truk B 1111 SWA',
        ]);

        $this->assertDatabaseHas('vendor_bills', [
            'id' => $bill->id,
            'vendor_id' => $vendor->id,
            'bill_number' => 'V-BILL-20260917-001',
            'status' => 'DRAFT',
            'total_amount' => 2500000,
        ]);

        // Belum ada jurnal yang dibuat saat status masih DRAFT
        $this->assertDatabaseMissing('journals', [
            'reference' => 'Tagihan Vendor #V-BILL-20260917-001',
        ]);

        // Verifikasi relasi
        $this->assertEquals($vendor->id, $bill->vendor->id);
        $this->assertEquals($expenseCoa->id, $bill->expenseAccount->id);
        $this->assertCount(1, $vendor->vendorBills);
    }

    public function test_changing_status_to_unpaid_creates_automatic_ap_journal(): void
    {
        $vendor = Vendor::create([
            'code' => 'VEND-002',
            'name' => 'SPBU Shell Cikarang',
            'phone' => '0812987654',
            'type' => 'bbm',
            'is_active' => true,
        ]);

        $expenseCoa = Coa::where('code', '5100')->first();

        $bill = VendorBill::create([
            'vendor_id' => $vendor->id,
            'bill_number' => 'V-BILL-20260917-002',
            'bill_date' => now()->toDateString(),
            'expense_account_id' => $expenseCoa->id,
            'total_amount' => 4000000,
            'status' => 'DRAFT',
        ]);

        // Ubah status menjadi UNPAID
        $bill->update(['status' => 'UNPAID']);

        // Jurnal pengakuan hutang dan biaya otomatis terbuat
        $journal = Journal::where('reference', 'Tagihan Vendor #V-BILL-20260917-002')->first();
        $this->assertNotNull($journal);
        $this->assertEquals('approved', $journal->status);

        // Verifikasi rincian 2 baris jurnal
        $this->assertCount(2, $journal->details);

        // DEBIT: Beban Solar Kendaraan (5100) = 4,000,000
        $debitDetail = $journal->details->where('debit', '>', 0)->first();
        $this->assertNotNull($debitDetail);
        $this->assertEquals(4000000, (float) $debitDetail->debit);
        $this->assertEquals('5100', $debitDetail->coa->code);

        // KREDIT: Hutang Usaha (2100) = 4,000,000
        $creditDetail = $journal->details->where('credit', '>', 0)->first();
        $this->assertNotNull($creditDetail);
        $this->assertEquals(4000000, (float) $creditDetail->credit);
        $this->assertEquals('2100', $creditDetail->coa->code);

        // Jurnal seimbang
        $this->assertEquals($journal->details->sum('debit'), $journal->details->sum('credit'));
    }

    public function test_pay_bill_action_updates_status_to_paid_and_creates_settlement_journal(): void
    {
        $vendor = Vendor::create([
            'code' => 'VEND-003',
            'name' => 'Vendor GPS Tracker',
            'phone' => '0813445566',
            'type' => 'other',
            'is_active' => true,
        ]);

        $bankCoa = Coa::where('code', '1200')->first();
        $bankCash = BankCash::create([
            'name' => 'Mandiri Operasional',
            'type' => 'bank',
            'coa_id' => $bankCoa->id,
            'current_balance' => 30000000,
        ]);

        $expenseCoa = Coa::where('code', '5200')->first();

        $bill = VendorBill::create([
            'vendor_id' => $vendor->id,
            'bill_number' => 'V-BILL-20260917-003',
            'bill_date' => now()->toDateString(),
            'expense_account_id' => $expenseCoa->id,
            'total_amount' => 1500000,
            'status' => 'UNPAID',
        ]);

        // Simulasikan action Bayar Tagihan (Pay Bill)
        DB::transaction(function () use ($bill, $bankCoa) {
            $totalAmount = (float) $bill->total_amount;
            $bill->update(['status' => 'PAID']);

            $coaHutangUsaha = Coa::where('code', '2100')->first();

            $journal = Journal::create([
                'journal_number' => 'PAY-BILL-'.$bill->id.'-'.time(),
                'date' => now()->toDateString(),
                'reference' => 'Pelunasan Tagihan #'.$bill->bill_number,
                'description' => 'Pembayaran Tagihan Vendor '.$bill->bill_number,
                'status' => 'approved',
            ]);

            // DEBIT: Hutang Usaha
            $journal->details()->create([
                'coa_id' => $coaHutangUsaha->id,
                'debit' => $totalAmount,
                'credit' => 0,
                'description' => 'Pelunasan Tagihan Vendor #'.$bill->bill_number,
            ]);

            // KREDIT: Bank
            $journal->details()->create([
                'coa_id' => $bankCoa->id,
                'debit' => 0,
                'credit' => $totalAmount,
                'description' => 'Pengeluaran Kas/Bank untuk Pembayaran Tagihan #'.$bill->bill_number,
            ]);

            $bankCash = BankCash::where('coa_id', $bankCoa->id)->first();
            if ($bankCash) {
                $bankCash->decrement('current_balance', $totalAmount);
            }
        });

        $bill->refresh();
        $this->assertEquals('PAID', $bill->status);

        // Verifikasi Jurnal Pelunasan
        $payJournal = Journal::where('reference', 'Pelunasan Tagihan #V-BILL-20260917-003')->first();
        $this->assertNotNull($payJournal);

        // DEBIT: Hutang Usaha (2100) = 1,500,000
        $debitDetail = $payJournal->details->where('debit', '>', 0)->first();
        $this->assertEquals(1500000, (float) $debitDetail->debit);
        $this->assertEquals('2100', $debitDetail->coa->code);

        // KREDIT: Bank (1200) = 1,500,000
        $creditDetail = $payJournal->details->where('credit', '>', 0)->first();
        $this->assertEquals(1500000, (float) $creditDetail->credit);
        $this->assertEquals('1200', $creditDetail->coa->code);

        // Saldo bank berkurang
        $bankCash->refresh();
        $this->assertEquals(28500000, (float) $bankCash->current_balance);
    }

    public function test_admin_can_access_vendor_bill_filament_pages(): void
    {
        $admin = User::create([
            'name' => 'Finance Admin',
            'email' => 'admin-bill@sinarwardana.com',
            'password' => bcrypt('password'),
        ]);

        Role::firstOrCreate(['name' => 'Superadmin']);
        $admin->assignRole('Superadmin');

        $vendor = Vendor::create(['code' => 'VEND-004', 'name' => 'Vendor Test Access', 'type' => 'maintenance', 'is_active' => true]);
        $expenseCoa = Coa::where('code', '5200')->first();

        $bill = VendorBill::create([
            'vendor_id' => $vendor->id,
            'bill_number' => 'V-BILL-ACCESS-01',
            'bill_date' => now()->toDateString(),
            'expense_account_id' => $expenseCoa->id,
            'total_amount' => 500000,
        ]);

        $responseIndex = $this->actingAs($admin)->get('/admin/vendor-bills');
        $responseIndex->assertSuccessful();

        $responseCreate = $this->actingAs($admin)->get('/admin/vendor-bills/create');
        $responseCreate->assertSuccessful();

        $responseEdit = $this->actingAs($admin)->get('/admin/vendor-bills/'.$bill->id.'/edit');
        $responseEdit->assertSuccessful();
    }
}
