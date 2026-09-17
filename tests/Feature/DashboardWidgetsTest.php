<?php

namespace Tests\Feature;

use App\Filament\Widgets\RevenueChart;
use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\TripStatusChart;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\Tariff;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Vendor;
use App\Models\VendorBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardWidgetsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Superadmin']);
    }

    public function test_admin_can_access_dashboard_with_all_widgets(): void
    {
        $admin = User::create([
            'name' => 'Executive Admin',
            'email' => 'exec@sinarwardana.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('Superadmin');

        $response = $this->actingAs($admin)->get('/admin');
        $response->assertSuccessful();
    }

    public function test_stats_overview_widget_calculates_correct_values(): void
    {
        $customer = Customer::create([
            'name' => 'PT Mitra Sejati',
            'code' => 'CUST-001',
            'phone' => '08123456789',
            'is_active' => true,
        ]);

        // 1. Unpaid Invoice (Piutang) = 15,000,000
        Invoice::create([
            'invoice_number' => 'INV-STAT-001',
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'total_amount' => 15000000,
            'status' => 'UNPAID',
        ]);

        // 2. Draft Invoice (harus diabaikan dari piutang unpaid)
        Invoice::create([
            'invoice_number' => 'INV-STAT-002',
            'customer_id' => $customer->id,
            'invoice_date' => now()->toDateString(),
            'total_amount' => 5000000,
            'status' => 'DRAFT',
        ]);

        // 3. Vendor Bill Unpaid (Hutang) = 4,000,000
        $vendor = Vendor::create([
            'code' => 'VEND-001',
            'name' => 'Supplier Ban',
            'type' => 'sparepart',
            'is_active' => true,
        ]);

        VendorBill::create([
            'vendor_id' => $vendor->id,
            'bill_number' => 'VB-STAT-001',
            'bill_date' => now()->toDateString(),
            'total_amount' => 4000000,
            'status' => 'UNPAID',
        ]);

        // 4. Trips
        $driver = Driver::create([
            'code' => 'DRV-001',
            'name' => 'Sopir Budi',
            'phone' => '0811111111',
            'is_active' => true,
        ]);

        $vehicle = Vehicle::create([
            'license_plate' => 'B 9999 SWA',
            'capacity' => 32000,
            'status' => 'aktif',
        ]);

        $tariff = Tariff::create([
            'name' => 'Jakarta - Surabaya 32KL',
            'customer_price' => 5000000,
            'fee_type' => 'fixed',
            'pt_margin' => 500000,
        ]);

        // Active trip (In Transit)
        Trip::create([
            'trip_number' => 'TRP-STAT-001',
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'tariff_id' => $tariff->id,
            'route_origin' => 'Jakarta',
            'route_destination' => 'Surabaya',
            'volume' => 1,
            'status' => 'In Transit',
        ]);

        // Completed trip
        Trip::create([
            'trip_number' => 'TRP-STAT-002',
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'tariff_id' => $tariff->id,
            'route_origin' => 'Jakarta',
            'route_destination' => 'Surabaya',
            'volume' => 1,
            'status' => 'Completed',
        ]);

        Livewire::test(StatsOverview::class)
            ->assertSuccessful()
            ->assertSee('Total Piutang (Unpaid Invoices)')
            ->assertSee('15.000.000')
            ->assertSee('Total Hutang Mitra & Vendor')
            ->assertSee('4.000.000')
            ->assertSee('Trip Sedang Berjalan')
            ->assertSee('1 Trip');
    }

    public function test_revenue_chart_widget_renders_correctly(): void
    {
        Livewire::test(RevenueChart::class)
            ->assertSuccessful()
            ->assertSee('Tren Pendapatan (6 Bulan Terakhir)');
    }

    public function test_trip_status_chart_widget_renders_correctly(): void
    {
        Livewire::test(TripStatusChart::class)
            ->assertSuccessful()
            ->assertSee('Rasio Status Pengiriman');
    }
}
