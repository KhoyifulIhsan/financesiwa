<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\BillDetail;
use App\Models\Coa;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Trip;
use App\Models\TripExpense;
use App\Models\Vehicle;
use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DummyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Pelanggan (Customers)
        $customers = [
            Customer::updateOrCreate(
                ['code' => 'CUST-001'],
                [
                    'name' => 'SPBU 34-12345 Bekasi',
                    'address' => 'Jl. Raya Bekasi No. 12',
                    'phone' => '081234567890 (Pak Budi)',
                    'email' => 'budi@spbu-bekasi.com',
                    'is_active' => true,
                ]
            ),
            Customer::updateOrCreate(
                ['code' => 'CUST-002'],
                [
                    'name' => 'PT Baja Nusantara Cikarang',
                    'address' => 'Kawasan Industri Cikarang',
                    'phone' => '082345678901 (Bu Siska)',
                    'email' => 'siska@bajanusantara.co.id',
                    'is_active' => true,
                ]
            ),
            Customer::updateOrCreate(
                ['code' => 'CUST-003'],
                [
                    'name' => 'SPBU 31-54321 Jakarta',
                    'address' => 'Jl. Jend. Sudirman, Jakarta Selatan',
                    'phone' => '083456789012 (Pak Anton)',
                    'email' => 'anton@spbu-jakarta.com',
                    'is_active' => true,
                ]
            ),
        ];

        // 2. Vendor / Supplier
        Vendor::updateOrCreate(
            ['code' => 'VEND-001'],
            [
                'name' => 'PT Pertamina Patra Niaga',
                'address' => 'Gedung Wisma Tugu, Jakarta',
                'phone' => '021-1234567',
                'type' => 'bbm',
                'is_active' => true,
            ]
        );
        Vendor::updateOrCreate(
            ['code' => 'VEND-002'],
            [
                'name' => 'Bengkel Mandiri Diesel',
                'address' => 'Jl. Raya Narogong',
                'phone' => '08111222333',
                'type' => 'maintenance',
                'is_active' => true,
            ]
        );
        Vendor::updateOrCreate(
            ['code' => 'VEND-003'],
            [
                'name' => 'Sinar Ban Truck',
                'address' => 'Kawasan Marunda',
                'phone' => '08222333444',
                'type' => 'sparepart',
                'is_active' => true,
            ]
        );

        // 3. Tagihan (Invoices) & Details
        $capacities = [8, 16, 24]; // Kapasitas tangki (KL)
        $prices = [150000, 200000]; // Harga satuan angkut per KL

        for ($i = 1; $i <= 5; $i++) {
            $customer = $customers[array_rand($customers)];

            $invoice = Invoice::firstOrCreate(
                ['invoice_number' => 'INV-DUMMY-'.str_pad($i, 3, '0', STR_PAD_LEFT)],
                [
                    'customer_id' => $customer->id,
                    'invoice_date' => Carbon::now()->subDays(rand(1, 15)),
                    'due_date' => Carbon::now()->addDays(rand(5, 15)),
                    'status' => 'UNPAID',
                    'subtotal' => 0,
                    'tax' => 0,
                    'total_amount' => 0,
                ]
            );

            $qty = $capacities[array_rand($capacities)];
            $unitPrice = $prices[array_rand($prices)];
            $totalPrice = $qty * $unitPrice;

            InvoiceDetail::create([
                'invoice_id' => $invoice->id,
                'description' => 'Jasa Angkut BBM DO Depo Plumpang',
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
            ]);

            // Update total_amount in invoice
            $invoice->update([
                'subtotal' => $totalPrice,
                'total_amount' => $totalPrice, // Asumsi belum ada pajak
            ]);
        }

        // 4. Sopir (Drivers)
        $driver1 = Driver::updateOrCreate(['phone' => '081299887766'], ['name' => 'Ahmad Supardi', 'is_active' => true]);
        $driver2 = Driver::updateOrCreate(['phone' => '081388776655'], ['name' => 'Bambang Irawan', 'is_active' => true]);
        $driver3 = Driver::updateOrCreate(['phone' => '081477665544'], ['name' => 'Dedi Kurniawan', 'is_active' => true]);

        // 5. Armada (Vehicles)
        $vehicle1 = Vehicle::updateOrCreate(['license_plate' => 'B 9123 SWA'], ['capacity' => 16, 'driver_name' => 'Ahmad Supardi', 'status' => 'aktif']);
        $vehicle2 = Vehicle::updateOrCreate(['license_plate' => 'B 9456 SWB'], ['capacity' => 24, 'driver_name' => 'Bambang Irawan', 'status' => 'aktif']);
        $vehicle3 = Vehicle::updateOrCreate(['license_plate' => 'B 9789 SWC'], ['capacity' => 8, 'driver_name' => 'Dedi Kurniawan', 'status' => 'aktif']);

        // 6. Contoh Trip Operasional
        $demoTrip = Trip::updateOrCreate(
            ['trip_number' => 'TRIP-DEMO-001'],
            [
                'date' => Carbon::now()->format('Y-m-d'),
                'customer_id' => $customers[0]->id,
                'vehicle_id' => $vehicle1->id,
                'driver_id' => $driver1->id,
                'route_origin' => 'Depo Pertamina Plumpang',
                'route_destination' => 'SPBU 34-12345 Bekasi',
                'volume' => 16,
                'status' => 'Pending',
            ]
        );

        $solarCoa = Coa::where('code', '5100')->first();
        $tolCoa = Coa::where('code', '5200')->first();
        $uangJalanCoa = Coa::where('code', '5300')->first();

        if ($solarCoa) {
            TripExpense::updateOrCreate(
                ['trip_id' => $demoTrip->id, 'coa_id' => $solarCoa->id],
                ['description' => 'Solar Solar/Dexlite 80 Liter', 'amount' => 1200000]
            );
        }
        if ($tolCoa) {
            TripExpense::updateOrCreate(
                ['trip_id' => $demoTrip->id, 'coa_id' => $tolCoa->id],
                ['description' => 'E-Toll Tol Becakayu & Cikampek', 'amount' => 150000]
            );
        }
        if ($uangJalanCoa) {
            TripExpense::updateOrCreate(
                ['trip_id' => $demoTrip->id, 'coa_id' => $uangJalanCoa->id],
                ['description' => 'Uang Jalan Sopir & Kernet', 'amount' => 350000]
            );
        }

        // 7. Tagihan Vendor (Bills)
        $pertaminaVendor = Vendor::where('code', 'VEND-001')->first();
        $bengkelVendor = Vendor::where('code', 'VEND-002')->first();

        if ($pertaminaVendor) {
            $bill1 = Bill::updateOrCreate(
                ['bill_number' => 'BILL-2026-001'],
                [
                    'vendor_id' => $pertaminaVendor->id,
                    'bill_date' => Carbon::now()->subDays(5)->format('Y-m-d'),
                    'due_date' => Carbon::now()->addDays(25)->format('Y-m-d'),
                    'status' => 'Unpaid',
                    'total_amount' => 25000000,
                    'paid_amount' => 0,
                    'notes' => 'Tagihan pembelian BBM Solar Industri DO Depo Plumpang',
                ]
            );

            BillDetail::updateOrCreate(
                ['bill_id' => $bill1->id, 'description' => 'Solar Industri B35 (1.500 Liter)'],
                ['amount' => 25000000]
            );
        }

        if ($bengkelVendor) {
            $bill2 = Bill::updateOrCreate(
                ['bill_number' => 'BILL-2026-002'],
                [
                    'vendor_id' => $bengkelVendor->id,
                    'bill_date' => Carbon::now()->subDays(2)->format('Y-m-d'),
                    'due_date' => Carbon::now()->addDays(12)->format('Y-m-d'),
                    'status' => 'Unpaid',
                    'total_amount' => 3500000,
                    'paid_amount' => 0,
                    'notes' => 'Servis berkala & tune-up armada truk tangki',
                ]
            );

            BillDetail::updateOrCreate(
                ['bill_id' => $bill2->id, 'description' => 'Ganti Oli Mesin & Filter Solar Truk B 9123 SWA'],
                ['amount' => 3500000]
            );
        }
    }
}
