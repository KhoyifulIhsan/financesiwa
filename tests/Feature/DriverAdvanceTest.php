<?php

namespace Tests\Feature;

use App\Models\BankCash;
use App\Models\Coa;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\DriverAdvance;
use App\Models\Journal;
use App\Models\JournalDetail;
use App\Models\Tariff;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DriverAdvanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Coa::firstOrCreate(['code' => '1100'], ['name' => 'Kas Operasional', 'type' => 'asset', 'is_active' => true]);
        Coa::firstOrCreate(['code' => '1140'], ['name' => 'Piutang Karyawan / Uang Jalan', 'type' => 'asset', 'is_active' => true]);
    }

    public function test_driver_advance_can_be_created_with_default_draft_status(): void
    {
        $customer = Customer::create(['code' => 'CUST-001', 'name' => 'SPBU Bekasi', 'is_active' => true]);
        $driver = Driver::create(['name' => 'Pak Joko', 'phone' => '08123456789', 'is_active' => true]);
        $vehicle = Vehicle::create(['license_plate' => 'B 1111 SWA', 'capacity' => 16, 'status' => 'aktif']);
        $tariff = Tariff::create([
            'name' => 'Tarif Plumpang-Bekasi',
            'customer_price' => 150000,
            'fee_type' => 'fixed',
            'pt_margin' => 30000,
        ]);

        $trip = Trip::create([
            'trip_number' => 'TRIP-ADV-001',
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'tariff_id' => $tariff->id,
            'route_origin' => 'Plumpang',
            'route_destination' => 'Bekasi',
            'volume' => 16,
            'status' => 'Pending',
        ]);

        $bankCoa = Coa::where('code', '1100')->first();

        $advance = DriverAdvance::create([
            'trip_id' => $trip->id,
            'driver_id' => $driver->id,
            'amount' => 500000,
            'issue_date' => now()->toDateString(),
            'bank_account_id' => $bankCoa->id,
            'notes' => 'Uang bensin dan tol',
        ]);

        $this->assertDatabaseHas('driver_advances', [
            'id' => $advance->id,
            'trip_id' => $trip->id,
            'driver_id' => $driver->id,
            'amount' => 500000,
            'status' => 'DRAFT',
        ]);

        // Verifikasi relasi model
        $this->assertEquals($trip->id, $advance->trip->id);
        $this->assertEquals($driver->id, $advance->driver->id);
        $this->assertEquals($bankCoa->id, $advance->bankAccount->id);
        $this->assertCount(1, $trip->driverAdvances);
        $this->assertCount(1, $driver->driverAdvances);
    }

    public function test_disbursing_driver_advance_changes_status_and_creates_automatic_journal(): void
    {
        $customer = Customer::create(['code' => 'CUST-002', 'name' => 'SPBU Cikarang', 'is_active' => true]);
        $driver = Driver::create(['name' => 'Pak Slamet', 'phone' => '08129876543', 'is_active' => true]);
        $vehicle = Vehicle::create(['license_plate' => 'B 2222 SWA', 'capacity' => 24, 'status' => 'aktif']);
        $tariff = Tariff::create([
            'name' => 'Tarif Cikarang',
            'customer_price' => 200000,
            'fee_type' => 'fixed',
            'pt_margin' => 40000,
        ]);

        $trip = Trip::create([
            'trip_number' => 'TRIP-ADV-002',
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'tariff_id' => $tariff->id,
            'route_origin' => 'Plumpang',
            'route_destination' => 'Cikarang',
            'volume' => 24,
            'status' => 'In Transit',
        ]);

        $bankCoa = Coa::where('code', '1100')->first();
        $bankCash = BankCash::create([
            'name' => 'Kas Operasional Kantor',
            'type' => 'cash',
            'coa_id' => $bankCoa->id,
            'current_balance' => 10000000,
        ]);

        $advance = DriverAdvance::create([
            'trip_id' => $trip->id,
            'driver_id' => $driver->id,
            'amount' => 750000,
            'issue_date' => now()->toDateString(),
            'bank_account_id' => $bankCoa->id,
            'notes' => 'Uang jalan Cikarang',
        ]);

        $this->assertEquals('DRAFT', $advance->status);

        // Jalankan logika disburse
        DB::transaction(function () use ($advance) {
            $amount = (float) $advance->amount;
            $advance->update(['status' => 'DISBURSED']);

            $coaPiutangKaryawan = Coa::firstOrCreate(
                ['code' => '1140'],
                [
                    'name' => 'Piutang Karyawan / Uang Jalan',
                    'type' => 'asset',
                    'is_active' => true,
                ]
            );

            $tripNumber = $advance->trip?->trip_number ?? ('Trip #'.$advance->trip_id);
            $driverName = $advance->driver?->name ?? 'Sopir';

            $journal = Journal::create([
                'journal_number' => 'UJ-'.$advance->id.'-'.time(),
                'date' => $advance->issue_date ?? now(),
                'reference' => 'Uang Jalan #'.$advance->id.' ('.$tripNumber.')',
                'description' => 'Pencairan Uang Jalan: '.$driverName.' - '.$tripNumber,
                'status' => 'approved',
            ]);

            JournalDetail::create([
                'journal_id' => $journal->id,
                'coa_id' => $coaPiutangKaryawan->id,
                'debit' => $amount,
                'credit' => 0,
                'description' => 'Pemberian Uang Jalan ke '.$driverName.' ('.$tripNumber.')',
            ]);

            JournalDetail::create([
                'journal_id' => $journal->id,
                'coa_id' => $advance->bank_account_id,
                'debit' => 0,
                'credit' => $amount,
                'description' => 'Pengeluaran Kas/Bank untuk Uang Jalan '.$driverName,
            ]);

            $bankCash = BankCash::where('coa_id', $advance->bank_account_id)->first();
            if ($bankCash) {
                $bankCash->decrement('current_balance', $amount);
            }
        });

        $advance->refresh();
        $this->assertEquals('DISBURSED', $advance->status);

        // Verifikasi Jurnal dibuat
        $journal = Journal::where('reference', 'Uang Jalan #'.$advance->id.' (TRIP-ADV-002)')->first();
        $this->assertNotNull($journal);
        $this->assertEquals('approved', $journal->status);

        // Verifikasi Detail Jurnal
        $this->assertCount(2, $journal->details);

        // DEBIT: Piutang Karyawan (1140) = 750,000
        $debitDetail = $journal->details->where('debit', '>', 0)->first();
        $this->assertNotNull($debitDetail);
        $this->assertEquals(750000, (float) $debitDetail->debit);
        $this->assertEquals('1140', $debitDetail->coa->code);

        // KREDIT: Kas (1100) = 750,000
        $creditDetail = $journal->details->where('credit', '>', 0)->first();
        $this->assertNotNull($creditDetail);
        $this->assertEquals(750000, (float) $creditDetail->credit);
        $this->assertEquals('1100', $creditDetail->coa->code);

        // Balance check
        $this->assertEquals($journal->details->sum('debit'), $journal->details->sum('credit'));

        // Saldo Kas berkurang
        $bankCash->refresh();
        $this->assertEquals(9250000, (float) $bankCash->current_balance);
    }

    public function test_admin_can_access_driver_advance_filament_pages(): void
    {
        $admin = User::create([
            'name' => 'Finance Admin',
            'email' => 'finance-uj@sinarwardana.com',
            'password' => bcrypt('password'),
        ]);

        Role::firstOrCreate(['name' => 'Superadmin']);
        $admin->assignRole('Superadmin');

        $customer = Customer::create(['code' => 'CUST-003', 'name' => 'SPBU Karawang', 'is_active' => true]);
        $driver = Driver::create(['name' => 'Pak Joko', 'phone' => '081234567890', 'is_active' => true]);
        $vehicle = Vehicle::create(['license_plate' => 'B 3333 SWA', 'capacity' => 16, 'status' => 'aktif']);
        $tariff = Tariff::create([
            'name' => 'Tarif Karawang',
            'customer_price' => 120000,
            'fee_type' => 'fixed',
            'pt_margin' => 20000,
        ]);

        $trip = Trip::create([
            'trip_number' => 'TRIP-ADV-003',
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'tariff_id' => $tariff->id,
            'route_origin' => 'Plumpang',
            'route_destination' => 'Karawang',
            'volume' => 16,
            'status' => 'Pending',
        ]);

        $bankCoa = Coa::where('code', '1100')->first();
        $advance = DriverAdvance::create([
            'trip_id' => $trip->id,
            'driver_id' => $driver->id,
            'amount' => 400000,
            'issue_date' => now()->toDateString(),
            'bank_account_id' => $bankCoa->id,
        ]);

        $responseIndex = $this->actingAs($admin)->get('/admin/driver-advances');
        $responseIndex->assertSuccessful();

        $responseCreate = $this->actingAs($admin)->get('/admin/driver-advances/create');
        $responseCreate->assertSuccessful();

        $responseEdit = $this->actingAs($admin)->get('/admin/driver-advances/'.$advance->id.'/edit');
        $responseEdit->assertSuccessful();
    }
}
