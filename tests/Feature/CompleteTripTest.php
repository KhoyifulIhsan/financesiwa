<?php

namespace Tests\Feature;

use App\Filament\Resources\Trips\Pages\ListTrips;
use App\Models\BankCash;
use App\Models\Coa;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Investor;
use App\Models\Journal;
use App\Models\Tariff;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CompleteTripTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // CoA
        Coa::firstOrCreate(['code' => '1100'], ['name' => 'Kas Operasional', 'type' => 'asset', 'is_active' => true]);
        Coa::firstOrCreate(['code' => '5500'], ['name' => 'Beban Bagi Hasil Mitra', 'type' => 'expense', 'is_active' => true]);
        Coa::firstOrCreate(['code' => '2150'], ['name' => 'Hutang Mitra / Investor', 'type' => 'liability', 'is_active' => true]);
        Role::firstOrCreate(['name' => 'Superadmin']);
    }

    public function test_completing_trip_with_pt_vehicle_sets_zero_mitra_share_and_creates_no_mitra_journal(): void
    {
        $admin = User::create([
            'name' => 'Admin Ops',
            'email' => 'admin-ops@sinarwardana.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('Superadmin');

        $customer = Customer::create([
            'name' => 'PT Pelanggan A',
            'code' => 'CUST-A',
            'phone' => '0812345678',
            'is_active' => true,
        ]);

        $driver = Driver::create([
            'code' => 'DRV-A',
            'name' => 'Driver Joko',
            'phone' => '0812999999',
            'is_active' => true,
        ]);

        // Armada Milik PT (investor_id is null)
        $vehiclePt = Vehicle::create([
            'license_plate' => 'B 1000 PT',
            'capacity' => 16,
            'status' => 'aktif',
            'ownership_status' => 'Milik PT',
            'investor_id' => null,
        ]);

        $tariff = Tariff::create([
            'name' => 'Jakarta - Bandung',
            'customer_price' => 3000000,
            'fee_type' => 'fixed',
            'pt_margin' => 500000,
        ]);

        $kasCoa = Coa::where('code', '1100')->first();
        $bankCash = BankCash::create([
            'name' => 'Kas Operasional',
            'type' => 'cash',
            'coa_id' => $kasCoa->id,
            'current_balance' => 10000000,
        ]);

        $trip = Trip::create([
            'trip_number' => 'TRP-PT-001',
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'vehicle_id' => $vehiclePt->id,
            'driver_id' => $driver->id,
            'tariff_id' => $tariff->id,
            'route_origin' => 'Jakarta',
            'route_destination' => 'Bandung',
            'volume' => 1,
            'status' => 'Pending',
        ]);

        // Eksekusi action completeTrip melalui Livewire
        Livewire::actingAs($admin)
            ->test(ListTrips::class)
            ->callTableAction('completeTrip', $trip, data: [
                'bank_cash_id' => $bankCash->id,
                'journal_date' => now()->toDateString(),
            ]);

        $trip->refresh();
        $this->assertEquals('Completed', $trip->status);
        $this->assertEquals(0, (float) $trip->mitra_share_amount);

        // Pastikan TIDAK ADA jurnal bagi hasil mitra yang terbuat
        $mitraJournal = Journal::where('reference', 'Trip #'.$trip->trip_number)
            ->where('journal_number', 'like', 'BH-%')
            ->first();
        $this->assertNull($mitraJournal);
    }

    public function test_completing_trip_with_mitra_vehicle_calculates_mitra_share_and_creates_journal(): void
    {
        $admin = User::create([
            'name' => 'Admin Ops 2',
            'email' => 'admin-ops2@sinarwardana.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('Superadmin');

        $investor = Investor::create([
            'name' => 'Mitra Jaya Abadi',
            'phone' => '0812888888',
        ]);

        $customer = Customer::create([
            'name' => 'PT Pelanggan B',
            'code' => 'CUST-B',
            'phone' => '0812345679',
            'is_active' => true,
        ]);

        $driver = Driver::create([
            'code' => 'DRV-B',
            'name' => 'Driver Anto',
            'phone' => '0812777777',
            'is_active' => true,
        ]);

        // Armada Milik Mitra (investor_id is set)
        $vehicleMitra = Vehicle::create([
            'license_plate' => 'B 2000 MTR',
            'capacity' => 20,
            'status' => 'aktif',
            'ownership_status' => 'Milik Mitra',
            'investor_id' => $investor->id,
        ]);

        // Tarif fixed: customer_price 4,000,000, pt_margin 500,000 => hak mitra = 3,500,000 * 2 = 7,000,000
        $tariff = Tariff::create([
            'name' => 'Jakarta - Cirebon',
            'customer_price' => 4000000,
            'fee_type' => 'fixed',
            'pt_margin' => 500000,
        ]);

        $kasCoa = Coa::where('code', '1100')->first();
        $bankCash = BankCash::create([
            'name' => 'Kas Operasional',
            'type' => 'cash',
            'coa_id' => $kasCoa->id,
            'current_balance' => 10000000,
        ]);

        $trip = Trip::create([
            'trip_number' => 'TRP-MITRA-001',
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicleMitra->id,
            'driver_id' => $driver->id,
            'tariff_id' => $tariff->id,
            'route_origin' => 'Jakarta',
            'route_destination' => 'Cirebon',
            'volume' => 2,
            'status' => 'Pending',
        ]);

        // Eksekusi action completeTrip melalui Livewire
        Livewire::actingAs($admin)
            ->test(ListTrips::class)
            ->callTableAction('completeTrip', $trip, data: [
                'bank_cash_id' => $bankCash->id,
                'journal_date' => now()->toDateString(),
            ]);

        $trip->refresh();
        $this->assertEquals('Completed', $trip->status);
        // (4,000,000 - 500,000) * 2 = 7,000,000
        $this->assertEquals(7000000, (float) $trip->mitra_share_amount);

        // Pastikan Jurnal Bagi Hasil mitra terbuat dengan benar
        $mitraJournal = Journal::where('reference', 'Trip #'.$trip->trip_number)
            ->where('journal_number', 'like', 'BH-%')
            ->first();
        $this->assertNotNull($mitraJournal);
        $this->assertEquals('approved', $mitraJournal->status);

        // DEBIT: Beban Bagi Hasil Mitra (5500) = 7,000,000
        $debitDetail = $mitraJournal->details->where('debit', '>', 0)->first();
        $this->assertNotNull($debitDetail);
        $this->assertEquals(7000000, (float) $debitDetail->debit);
        $this->assertEquals('5500', $debitDetail->coa->code);

        // KREDIT: Hutang Mitra / Investor (2150) = 7,000,000
        $creditDetail = $mitraJournal->details->where('credit', '>', 0)->first();
        $this->assertNotNull($creditDetail);
        $this->assertEquals(7000000, (float) $creditDetail->credit);
        $this->assertEquals('2150', $creditDetail->coa->code);
    }
}
