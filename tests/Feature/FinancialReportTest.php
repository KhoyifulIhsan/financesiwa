<?php

namespace Tests\Feature;

use App\Filament\Pages\FinancialReport;
use App\Models\Coa;
use App\Models\Journal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FinancialReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Chart of Accounts
        Coa::firstOrCreate(['code' => '1100'], ['name' => 'Kas Operasional', 'type' => 'asset', 'is_active' => true]);
        Coa::firstOrCreate(['code' => '1300'], ['name' => 'Piutang Usaha', 'type' => 'asset', 'is_active' => true]);
        Coa::firstOrCreate(['code' => '2100'], ['name' => 'Hutang Usaha', 'type' => 'liability', 'is_active' => true]);
        Coa::firstOrCreate(['code' => '3100'], ['name' => 'Modal Disetor', 'type' => 'equity', 'is_active' => true]);
        Coa::firstOrCreate(['code' => '4100'], ['name' => 'Pendapatan Angkutan', 'type' => 'revenue', 'is_active' => true]);
        Coa::firstOrCreate(['code' => '5100'], ['name' => 'Beban Solar Kendaraan', 'type' => 'expense', 'is_active' => true]);
    }

    public function test_superadmin_can_access_financial_report_page(): void
    {
        $user = User::create([
            'name' => 'Super User',
            'email' => 'super@sinarwardana.com',
            'password' => bcrypt('password'),
        ]);

        Role::firstOrCreate(['name' => 'Superadmin']);
        $user->assignRole('Superadmin');

        $response = $this->actingAs($user)->get('/admin/financial-report');
        $response->assertSuccessful();
    }

    public function test_financial_report_calculates_income_statement_and_balance_sheet_accurately(): void
    {
        $user = User::create([
            'name' => 'Akuntan User',
            'email' => 'akuntan@sinarwardana.com',
            'password' => bcrypt('password'),
        ]);

        Role::firstOrCreate(['name' => 'Akuntan']);
        $user->assignRole('Akuntan');

        $kas = Coa::where('code', '1100')->first();
        $piutang = Coa::where('code', '1300')->first();
        $modal = Coa::where('code', '3100')->first();
        $pendapatan = Coa::where('code', '4100')->first();
        $bebanSolar = Coa::where('code', '5100')->first();

        $today = now()->toDateString();

        // 1. Setoran Modal Awal: Kas (Dr) 10,000,000 vs Modal (Cr) 10,000,000
        $j1 = Journal::create([
            'journal_number' => 'JRN-TEST-001',
            'date' => $today,
            'reference' => 'Setoran Modal',
            'description' => 'Setoran Modal Pemilik',
            'status' => 'approved',
        ]);
        $j1->details()->create(['coa_id' => $kas->id, 'debit' => 10000000, 'credit' => 0]);
        $j1->details()->create(['coa_id' => $modal->id, 'debit' => 0, 'credit' => 10000000]);

        // 2. Tagihan Pelanggan: Piutang (Dr) 5,000,000 vs Pendapatan (Cr) 5,000,000
        $j2 = Journal::create([
            'journal_number' => 'JRN-TEST-002',
            'date' => $today,
            'reference' => 'Invoice #INV-001',
            'description' => 'Pendapatan Angkutan Customer A',
            'status' => 'approved',
        ]);
        $j2->details()->create(['coa_id' => $piutang->id, 'debit' => 5000000, 'credit' => 0]);
        $j2->details()->create(['coa_id' => $pendapatan->id, 'debit' => 0, 'credit' => 5000000]);

        // 3. Biaya BBM: Beban Solar (Dr) 1,500,000 vs Kas (Cr) 1,500,000
        $j3 = Journal::create([
            'journal_number' => 'JRN-TEST-003',
            'date' => $today,
            'reference' => 'BBM Trip #001',
            'description' => 'Beli Solar Truk B 1234 SWA',
            'status' => 'approved',
        ]);
        $j3->details()->create(['coa_id' => $bebanSolar->id, 'debit' => 1500000, 'credit' => 0]);
        $j3->details()->create(['coa_id' => $kas->id, 'debit' => 0, 'credit' => 1500000]);

        // Uji komponen Livewire FinancialReport
        Livewire::actingAs($user)
            ->test(FinancialReport::class)
            ->set('start_date', now()->startOfMonth()->toDateString())
            ->set('end_date', now()->endOfMonth()->toDateString())
            ->assertViewHas('totalRevenue', 5000000.0)
            ->assertViewHas('totalExpense', 1500000.0)
            ->assertViewHas('netIncome', 3500000.0)
            ->assertViewHas('totalAsset', 13500000.0) // Kas: 8.5m + Piutang: 5m
            ->assertViewHas('totalLiability', 0.0)
            ->assertViewHas('totalEquity', 13500000.0) // Modal: 10m + Laba: 3.5m
            ->assertViewHas('totalPasiva', 13500000.0) // Liabilitas: 0 + Ekuitas: 13.5m
            ->assertViewHas('isBalanced', true)
            ->assertSee('5.000.000')
            ->assertSee('1.500.000')
            ->assertSee('3.500.000')
            ->assertSee('Balance (Seimbang)')
            ->assertActionExists('export_pdf');
    }

    public function test_pdf_financial_report_view_renders_correctly(): void
    {
        $viewData = [
            'startDate' => '2026-09-01',
            'endDate' => '2026-09-30',
            'revenues' => [
                ['code' => '4100', 'name' => 'Pendapatan Angkutan', 'balance' => 5000000.0],
            ],
            'expenses' => [
                ['code' => '5100', 'name' => 'Beban Solar Kendaraan', 'balance' => 1500000.0],
            ],
            'totalRevenue' => 5000000.0,
            'totalExpense' => 1500000.0,
            'netIncome' => 3500000.0,
            'assets' => [
                ['code' => '1100', 'name' => 'Kas Operasional', 'balance' => 8500000.0],
                ['code' => '1300', 'name' => 'Piutang Usaha', 'balance' => 5000000.0],
            ],
            'liabilities' => [],
            'equities' => [
                ['code' => '3100', 'name' => 'Modal Disetor', 'balance' => 10000000.0],
            ],
            'totalAsset' => 13500000.0,
            'totalLiability' => 0.0,
            'totalEquityWithoutEarnings' => 10000000.0,
            'cumulativeNetIncome' => 3500000.0,
            'totalEquity' => 13500000.0,
            'totalPasiva' => 13500000.0,
            'isBalanced' => true,
        ];

        $rendered = view('pdf.financial-report', $viewData)->render();

        $this->assertStringContainsString('PT. SINAR WARDANA GROUP', $rendered);
        $this->assertStringContainsString('LAPORAN KEUANGAN LENGKAP', $rendered);
        $this->assertStringContainsString('5.000.000', $rendered);
        $this->assertStringContainsString('1.500.000', $rendered);
        $this->assertStringContainsString('3.500.000', $rendered);
        $this->assertStringContainsString('13.500.000', $rendered);
        $this->assertStringContainsString('BALANCE (SEIMBANG)', $rendered);
    }
}
