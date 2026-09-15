<?php

namespace Tests\Feature;

use App\Models\Investor;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvestorVehicleTest extends TestCase
{
    use RefreshDatabase;

    public function test_investor_can_be_created(): void
    {
        $investor = Investor::create([
            'name' => 'PT Mitra Sejahtera',
            'phone' => '081234567890',
            'bank_account_info' => 'BCA 123456789 a/n PT Mitra Sejahtera',
        ]);

        $this->assertDatabaseHas('investors', [
            'id' => $investor->id,
            'name' => 'PT Mitra Sejahtera',
            'phone' => '081234567890',
            'bank_account_info' => 'BCA 123456789 a/n PT Mitra Sejahtera',
        ]);
    }

    public function test_vehicle_defaults_to_milik_pt_ownership(): void
    {
        $vehicle = Vehicle::create([
            'license_plate' => 'B 1234 ABC',
            'capacity' => 16,
            'driver_name' => 'Budi',
            'status' => 'aktif',
        ]);

        $this->assertEquals('Milik PT', $vehicle->ownership_status);
        $this->assertNull($vehicle->investor_id);
    }

    public function test_vehicle_can_be_assigned_to_investor(): void
    {
        $investor = Investor::create([
            'name' => 'Mitra Transport Mandiri',
            'phone' => '08987654321',
            'bank_account_info' => 'Mandiri 9876543210',
        ]);

        $vehicle = Vehicle::create([
            'license_plate' => 'B 5678 XYZ',
            'capacity' => 24,
            'driver_name' => 'Joko',
            'status' => 'aktif',
            'ownership_status' => 'Milik Mitra',
            'investor_id' => $investor->id,
        ]);

        $this->assertEquals('Milik Mitra', $vehicle->ownership_status);
        $this->assertEquals($investor->id, $vehicle->investor_id);
        $this->assertInstanceOf(Investor::class, $vehicle->investor);
        $this->assertEquals('Mitra Transport Mandiri', $vehicle->investor->name);

        $this->assertCount(1, $investor->vehicles);
        $this->assertEquals('B 5678 XYZ', $investor->vehicles->first()->license_plate);
    }

    public function test_deleting_investor_sets_vehicle_investor_id_to_null(): void
    {
        $investor = Investor::create([
            'name' => 'Investor Sementara',
            'phone' => '0811111111',
            'bank_account_info' => 'BRI 1111222233',
        ]);

        $vehicle = Vehicle::create([
            'license_plate' => 'B 9999 DEF',
            'capacity' => 32,
            'status' => 'aktif',
            'ownership_status' => 'Milik Mitra',
            'investor_id' => $investor->id,
        ]);

        $investor->delete();

        $vehicle->refresh();
        $this->assertNull($vehicle->investor_id);
    }

    public function test_admin_can_access_investor_and_vehicle_filament_pages(): void
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => bcrypt('password'),
        ]);

        // Spatie role
        Role::firstOrCreate(['name' => 'Superadmin']);
        $admin->assignRole('Superadmin');

        $responseInvestors = $this->actingAs($admin)->get('/admin/investors');
        $responseInvestors->assertSuccessful();

        $responseVehicles = $this->actingAs($admin)->get('/admin/vehicles');
        $responseVehicles->assertSuccessful();
    }
}
