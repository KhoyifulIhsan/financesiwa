<?php

namespace Tests\Feature;

use App\Models\BankCash;
use App\Models\Coa;
use App\Models\Investor;
use App\Models\Journal;
use App\Models\PartnerPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PartnerPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Coa::firstOrCreate(['code' => '1200'], ['name' => 'Bank Mandiri Utama', 'type' => 'asset', 'is_active' => true]);
        Coa::firstOrCreate(['code' => '2150'], ['name' => 'Hutang Mitra / Investor', 'type' => 'liability', 'is_active' => true]);
    }

    public function test_partner_payment_creation_triggers_automatic_cash_out_journal(): void
    {
        $investor = Investor::create([
            'name' => 'Haji Mansyur Transport',
            'phone' => '08123456789',
            'bank_account_info' => 'BCA 8899001122 a/n Mansyur',
        ]);

        $bankCoa = Coa::where('code', '1200')->first();
        $bankCash = BankCash::create([
            'name' => 'Rekening Operasional Mandiri',
            'type' => 'bank',
            'coa_id' => $bankCoa->id,
            'account_number' => '1440098765432',
            'current_balance' => 50000000,
        ]);

        $payment = PartnerPayment::create([
            'investor_id' => $investor->id,
            'payment_date' => now()->toDateString(),
            'amount' => 7500000,
            'bank_account_id' => $bankCoa->id,
            'reference_number' => 'TRF-MITRA-001',
            'notes' => 'Pencairan bagi hasil trip periode 1-15 September',
        ]);

        $this->assertDatabaseHas('partner_payments', [
            'id' => $payment->id,
            'investor_id' => $investor->id,
            'amount' => 7500000,
            'reference_number' => 'TRF-MITRA-001',
        ]);

        // Verifikasi relasi
        $this->assertEquals($investor->id, $payment->investor->id);
        $this->assertEquals($bankCoa->id, $payment->bankAccount->id);
        $this->assertCount(1, $investor->partnerPayments);

        // Verifikasi Jurnal Otomatis Kas Keluar
        $journal = Journal::where('reference', 'TRF-MITRA-001')->first();
        $this->assertNotNull($journal);
        $this->assertEquals('approved', $journal->status);
        $this->assertStringContainsString('Haji Mansyur Transport', $journal->description);

        // Verifikasi 2 baris journal details
        $this->assertCount(2, $journal->details);

        // DEBIT: Hutang Mitra / Investor (2150)
        $debitDetail = $journal->details->where('debit', '>', 0)->first();
        $this->assertNotNull($debitDetail);
        $this->assertEquals(7500000, (float) $debitDetail->debit);
        $this->assertEquals('2150', $debitDetail->coa->code);

        // KREDIT: Rekening Kas / Bank (1200)
        $creditDetail = $journal->details->where('credit', '>', 0)->first();
        $this->assertNotNull($creditDetail);
        $this->assertEquals(7500000, (float) $creditDetail->credit);
        $this->assertEquals('1200', $creditDetail->coa->code);

        // Jurnal seimbang (Debit == Kredit)
        $this->assertEquals($journal->details->sum('debit'), $journal->details->sum('credit'));

        // Saldo BankCash terpotong
        $bankCash->refresh();
        $this->assertEquals(42500000, (float) $bankCash->current_balance);
    }

    public function test_admin_can_access_partner_payment_filament_pages(): void
    {
        $admin = User::create([
            'name' => 'Finance Admin',
            'email' => 'admin-keuangan@sinarwardana.com',
            'password' => bcrypt('password'),
        ]);

        Role::firstOrCreate(['name' => 'Superadmin']);
        $admin->assignRole('Superadmin');

        $responseIndex = $this->actingAs($admin)->get('/admin/partner-payments');
        $responseIndex->assertSuccessful();

        $responseCreate = $this->actingAs($admin)->get('/admin/partner-payments/create');
        $responseCreate->assertSuccessful();
    }
}
