<?php

namespace Tests\Feature;

use App\Models\Coa;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\Journal;
use App\Models\Tariff;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceTripTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic accounts
        Coa::firstOrCreate(['code' => '1300'], ['name' => 'Piutang Usaha', 'type' => 'asset', 'is_active' => true]);
        Coa::firstOrCreate(['code' => '4000'], ['name' => 'Pendapatan Jasa Angkut BBM', 'type' => 'revenue', 'is_active' => true]);
    }

    public function test_invoice_can_be_created_with_default_draft_status(): void
    {
        $customer = Customer::create([
            'name' => 'SPBU 34-12345 Bekasi',
            'code' => 'CUST-001',
            'is_active' => true,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-20260915-0001',
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
        ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'invoice_number' => 'INV-20260915-0001',
            'customer_id' => $customer->id,
            'status' => 'DRAFT',
            'total_amount' => 0,
        ]);
    }

    public function test_trip_can_be_associated_with_invoice(): void
    {
        $customer = Customer::create(['code' => 'CUST-002', 'name' => 'SPBU Cikarang', 'is_active' => true]);
        $vehicle = Vehicle::create(['license_plate' => 'B 1234 SWA', 'capacity' => 16, 'status' => 'aktif']);
        $driver = Driver::create(['name' => 'Ahmad', 'phone' => '0812345678', 'is_active' => true]);
        $tariff = Tariff::create([
            'name' => 'Rute Plumpang - Cikarang',
            'customer_price' => 150000,
            'fee_type' => 'fixed',
            'pt_margin' => 30000,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-20260915-0002',
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
        ]);

        $trip = Trip::create([
            'trip_number' => 'TRIP-20260915-001',
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'tariff_id' => $tariff->id,
            'invoice_id' => $invoice->id,
            'route_origin' => 'Plumpang',
            'route_destination' => 'Cikarang',
            'volume' => 16,
            'status' => 'Completed',
        ]);

        $this->assertEquals($invoice->id, $trip->invoice_id);
        $this->assertInstanceOf(Invoice::class, $trip->invoice);
        $this->assertCount(1, $invoice->trips);
        $this->assertEquals('TRIP-20260915-001', $invoice->trips->first()->trip_number);
    }

    public function test_associating_and_dissociating_trips_recalculates_invoice_total_amount(): void
    {
        $customer = Customer::create(['code' => 'CUST-003', 'name' => 'SPBU Bekasi', 'is_active' => true]);
        $vehicle = Vehicle::create(['license_plate' => 'B 9999 SWA', 'capacity' => 24, 'status' => 'aktif']);
        $driver = Driver::create(['name' => 'Budi', 'phone' => '08123456789', 'is_active' => true]);
        $tariff = Tariff::create([
            'name' => 'Tarif 200rb/KL',
            'customer_price' => 200000,
            'fee_type' => 'fixed',
            'pt_margin' => 40000,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-20260915-0003',
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'total_amount' => 0,
            'status' => 'DRAFT',
        ]);

        // Trip 1: 16 KL * 200,000 = 3,200,000
        $trip1 = Trip::create([
            'trip_number' => 'TRIP-AUTO-01',
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'tariff_id' => $tariff->id,
            'route_origin' => 'Plumpang',
            'route_destination' => 'Bekasi',
            'volume' => 16,
            'status' => 'Completed',
        ]);

        // Tautkan trip 1
        $trip1->update(['invoice_id' => $invoice->id]);
        $invoice->refresh();

        $this->assertEquals(3200000, (float) $invoice->total_amount);

        // Trip 2: 8 KL * 200,000 = 1,600,000
        $trip2 = Trip::create([
            'trip_number' => 'TRIP-AUTO-02',
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'tariff_id' => $tariff->id,
            'route_origin' => 'Plumpang',
            'route_destination' => 'Bekasi',
            'volume' => 8,
            'status' => 'Completed',
        ]);

        // Tautkan trip 2
        $trip2->update(['invoice_id' => $invoice->id]);
        $invoice->refresh();

        $this->assertEquals(4800000, (float) $invoice->total_amount);

        // Lepas trip 1 (dissociate)
        $trip1->update(['invoice_id' => null]);
        $invoice->refresh();

        $this->assertEquals(1600000, (float) $invoice->total_amount);
    }

    public function test_changing_invoice_status_from_draft_to_unpaid_creates_automatic_double_entry_journal(): void
    {
        $customer = Customer::create(['code' => 'CUST-004', 'name' => 'SPBU Jakarta Timur', 'is_active' => true]);
        $vehicle = Vehicle::create(['license_plate' => 'B 7777 SWA', 'capacity' => 16, 'status' => 'aktif']);
        $driver = Driver::create(['name' => 'Dedi', 'phone' => '08129876543', 'is_active' => true]);
        $tariff = Tariff::create([
            'name' => 'Tarif Jaktim',
            'customer_price' => 150000,
            'fee_type' => 'fixed',
            'pt_margin' => 25000,
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-20260915-0004',
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'total_amount' => 0,
            'status' => 'DRAFT',
        ]);

        // Muatan 20 KL * 150.000 = 3.000.000
        $trip = Trip::create([
            'trip_number' => 'TRIP-JRN-01',
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'tariff_id' => $tariff->id,
            'route_origin' => 'Depo Plumpang',
            'route_destination' => 'SPBU Rawamangun',
            'volume' => 20,
            'status' => 'Completed',
            'invoice_id' => $invoice->id,
        ]);

        $invoice->refresh();
        $this->assertEquals(3000000, (float) $invoice->total_amount);
        $this->assertEquals('DRAFT', $invoice->status);

        // Pastikan belum ada jurnal sebelum status berubah
        $this->assertDatabaseMissing('journals', [
            'reference' => 'Invoice #'.$invoice->invoice_number,
        ]);

        // Ubah status dari DRAFT menjadi UNPAID
        $invoice->update(['status' => 'UNPAID']);

        // Pastikan Jurnal otomatis terbuat
        $this->assertDatabaseHas('journals', [
            'reference' => 'Invoice #'.$invoice->invoice_number,
            'status' => 'approved',
        ]);

        $journal = Journal::where('reference', 'Invoice #'.$invoice->invoice_number)->first();
        $this->assertNotNull($journal);

        // Periksa 2 baris journal details
        $this->assertCount(2, $journal->details);

        // Sisi DEBIT: Piutang Usaha (1300) = 3.000.000
        $debitDetail = $journal->details->where('debit', '>', 0)->first();
        $this->assertNotNull($debitDetail);
        $this->assertEquals(3000000, (float) $debitDetail->debit);
        $this->assertEquals('1300', $debitDetail->coa->code);

        // Sisi KREDIT: Pendapatan Jasa (4000) = 3.000.000
        $creditDetail = $journal->details->where('credit', '>', 0)->first();
        $this->assertNotNull($creditDetail);
        $this->assertEquals(3000000, (float) $creditDetail->credit);
        $this->assertEquals('4000', $creditDetail->coa->code);

        // Pastikan Jurnal Balance
        $this->assertEquals($journal->details->sum('debit'), $journal->details->sum('credit'));
    }

    public function test_admin_can_access_invoice_filament_pages(): void
    {
        $admin = User::create([
            'name' => 'Finance Admin',
            'email' => 'finance@sinarwardana.com',
            'password' => bcrypt('password'),
        ]);

        Role::firstOrCreate(['name' => 'Superadmin']);
        $admin->assignRole('Superadmin');

        $responseIndex = $this->actingAs($admin)->get('/admin/invoices');
        $responseIndex->assertSuccessful();

        $responseCreate = $this->actingAs($admin)->get('/admin/invoices/create');
        $responseCreate->assertSuccessful();

        $customer = Customer::create(['code' => 'CUST-005', 'name' => 'SPBU Edit Test', 'is_active' => true]);
        $invoice = Invoice::create([
            'invoice_number' => 'INV-20260915-0005',
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'status' => 'DRAFT',
        ]);

        $responseEdit = $this->actingAs($admin)->get('/admin/invoices/'.$invoice->id.'/edit');
        $responseEdit->assertSuccessful();
    }
}
