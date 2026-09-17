<?php

namespace Tests\Feature;

use App\Models\BankCash;
use App\Models\Coa;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\Investor;
use App\Models\Journal;
use App\Models\PartnerPayment;
use App\Models\Tariff;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
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

        $investor = Investor::create([
            'name' => 'Investor Edit Test',
        ]);
        $bankCoa = Coa::where('code', '1200')->first();
        $payment = PartnerPayment::create([
            'investor_id' => $investor->id,
            'payment_date' => now()->toDateString(),
            'amount' => 0,
            'bank_account_id' => $bankCoa->id,
            'reference_number' => 'TRF-EDIT-TEST',
        ]);

        $responseEdit = $this->actingAs($admin)->get('/admin/partner-payments/'.$payment->id.'/edit');
        $responseEdit->assertSuccessful();
    }

    public function test_trip_can_be_associated_with_partner_payment_and_automatically_recalculates_amount(): void
    {
        $investor = Investor::create([
            'name' => 'CV Mitra Sejahtera',
            'phone' => '0855667788',
        ]);

        $bankCoa = Coa::where('code', '1200')->first();
        $bankCash = BankCash::create([
            'name' => 'Kas Operasional',
            'type' => 'bank',
            'coa_id' => $bankCoa->id,
            'current_balance' => 20000000,
        ]);

        $customer = Customer::create(['code' => 'CUST-MITRA', 'name' => 'SPBU Pertamina', 'is_active' => true]);
        $vehicle = Vehicle::create([
            'license_plate' => 'B 8888 MIT',
            'capacity' => 24,
            'status' => 'aktif',
            'ownership_status' => 'Milik Mitra',
            'investor_id' => $investor->id,
        ]);
        $driver = Driver::create(['name' => 'Sopir Mitra', 'phone' => '0812345678', 'is_active' => true]);
        $tariff = Tariff::create([
            'name' => 'Tarif Mitra',
            'customer_price' => 100000,
            'fee_type' => 'fixed',
            'pt_margin' => 20000,
        ]);

        // Trip 1: 10 KL * (100k - 20k) = 800,000 mitra share
        $trip1 = Trip::create([
            'trip_number' => 'TRIP-MITRA-001',
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'tariff_id' => $tariff->id,
            'route_origin' => 'Depo',
            'route_destination' => 'SPBU',
            'volume' => 10,
            'mitra_share_amount' => 800000,
            'status' => 'Completed',
        ]);

        // Trip 2: 15 KL * (100k - 20k) = 1,200,000 mitra share
        $trip2 = Trip::create([
            'trip_number' => 'TRIP-MITRA-002',
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicle->id,
            'driver_id' => $driver->id,
            'tariff_id' => $tariff->id,
            'route_origin' => 'Depo',
            'route_destination' => 'SPBU 2',
            'volume' => 15,
            'mitra_share_amount' => 1200000,
            'status' => 'Completed',
        ]);

        // Buat PartnerPayment awal dengan amount 0 (seperti pada form create Filament)
        $payment = PartnerPayment::create([
            'investor_id' => $investor->id,
            'payment_date' => now()->toDateString(),
            'amount' => 0,
            'bank_account_id' => $bankCoa->id,
            'reference_number' => 'TRF-MITRA-AUTOSUM',
        ]);

        $this->assertEquals(0, (float) $payment->amount);
        $this->assertDatabaseMissing('journals', ['reference' => 'TRF-MITRA-AUTOSUM']);

        // 1. Tautkan Trip 1 (Associate)
        $trip1->update(['partner_payment_id' => $payment->id]);
        $payment->refresh();
        $payment->recalculateAmount();
        $payment->refresh();

        $this->assertEquals(800000, (float) $payment->amount);
        $this->assertCount(1, $payment->trips);

        // Pastikan Jurnal otomatis dibuat dengan nominal 800,000
        $journal = Journal::where('reference', 'TRF-MITRA-AUTOSUM')->first();
        $this->assertNotNull($journal);
        $debitDetail = $journal->details->where('debit', '>', 0)->first();
        $this->assertEquals(800000, (float) $debitDetail->debit);

        $bankCash->refresh();
        $this->assertEquals(19200000, (float) $bankCash->current_balance);

        // 2. Tautkan Trip 2 (Associate trip ke-2)
        $trip2->update(['partner_payment_id' => $payment->id]);
        $payment->refresh();
        $payment->recalculateAmount();
        $payment->refresh();

        $this->assertEquals(2000000, (float) $payment->amount);
        $this->assertCount(2, $payment->trips);

        // Jurnal otomatis tersinkronisasi menjadi 2,000,000
        $journal->refresh();
        $debitDetail = $journal->details->where('debit', '>', 0)->first();
        $this->assertEquals(2000000, (float) $debitDetail->debit);

        $creditDetail = $journal->details->where('credit', '>', 0)->first();
        $this->assertEquals(2000000, (float) $creditDetail->credit);

        $bankCash->refresh();
        $this->assertEquals(18000000, (float) $bankCash->current_balance);

        // 3. Lepaskan Trip 1 (Dissociate)
        $trip1->update(['partner_payment_id' => null]);
        $payment->refresh();
        $payment->recalculateAmount();
        $payment->refresh();

        $this->assertEquals(1200000, (float) $payment->amount);
        $this->assertCount(1, $payment->trips);

        $journal->refresh();
        $debitDetail = $journal->details->where('debit', '>', 0)->first();
        $this->assertEquals(1200000, (float) $debitDetail->debit);

        $bankCash->refresh();
        $this->assertEquals(18800000, (float) $bankCash->current_balance);
    }

    public function test_partner_payment_trip_filter_conditions(): void
    {
        $investorA = Investor::create(['name' => 'Mitra A']);
        $investorB = Investor::create(['name' => 'Mitra B']);

        $customer = Customer::create(['code' => 'CUST-FLT', 'name' => 'Pelanggan Filter', 'is_active' => true]);
        $driver = Driver::create(['name' => 'Driver Filter', 'phone' => '081234567890', 'is_active' => true]);
        $tariff = Tariff::create([
            'name' => 'Tarif Filter',
            'customer_price' => 100000,
            'fee_type' => 'fixed',
            'pt_margin' => 20000,
        ]);

        $vehicleA = Vehicle::create([
            'license_plate' => 'B 1111 AAA',
            'capacity' => 16,
            'ownership_status' => 'Milik Mitra',
            'investor_id' => $investorA->id,
        ]);

        $vehicleB = Vehicle::create([
            'license_plate' => 'B 2222 BBB',
            'capacity' => 16,
            'ownership_status' => 'Milik Mitra',
            'investor_id' => $investorB->id,
        ]);

        // Trip 1: Investor A, Completed, Belum dicairkan -> SHOULD MATCH
        $tripValid = Trip::create([
            'trip_number' => 'TRIP-VALID',
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicleA->id,
            'driver_id' => $driver->id,
            'tariff_id' => $tariff->id,
            'route_origin' => 'A',
            'route_destination' => 'B',
            'volume' => 10,
            'mitra_share_amount' => 800000,
            'status' => 'Completed',
        ]);

        // Trip 2: Investor B, Completed -> SHOULD NOT MATCH for Investor A
        $tripOtherInvestor = Trip::create([
            'trip_number' => 'TRIP-OTHER-INV',
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicleB->id,
            'driver_id' => $driver->id,
            'tariff_id' => $tariff->id,
            'route_origin' => 'A',
            'route_destination' => 'B',
            'volume' => 10,
            'mitra_share_amount' => 800000,
            'status' => 'Completed',
        ]);

        // Trip 3: Investor A, Status Pending -> SHOULD NOT MATCH
        $tripPending = Trip::create([
            'trip_number' => 'TRIP-PENDING',
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicleA->id,
            'driver_id' => $driver->id,
            'tariff_id' => $tariff->id,
            'route_origin' => 'A',
            'route_destination' => 'B',
            'volume' => 10,
            'mitra_share_amount' => 800000,
            'status' => 'Pending',
        ]);

        $bankCoa = Coa::where('code', '1200')->first();
        $payment = PartnerPayment::create([
            'investor_id' => $investorA->id,
            'payment_date' => now()->toDateString(),
            'amount' => 0,
            'bank_account_id' => $bankCoa->id,
        ]);

        // Trip 4: Investor A, Completed, but ALREADY ATTACHED to another payment -> SHOULD NOT MATCH
        $otherPayment = PartnerPayment::create([
            'investor_id' => $investorA->id,
            'payment_date' => now()->toDateString(),
            'amount' => 800000,
            'bank_account_id' => $bankCoa->id,
        ]);
        $tripAlreadyPaid = Trip::create([
            'trip_number' => 'TRIP-ALREADY-PAID',
            'date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'vehicle_id' => $vehicleA->id,
            'driver_id' => $driver->id,
            'tariff_id' => $tariff->id,
            'route_origin' => 'A',
            'route_destination' => 'B',
            'volume' => 10,
            'mitra_share_amount' => 800000,
            'status' => 'Completed',
            'partner_payment_id' => $otherPayment->id,
        ]);

        // Query persis seperti pada TripsRelationManager
        $availableTrips = Trip::query()
            ->whereIn('status', ['SELESAI', 'Selesai', 'selesai', 'Completed', 'completed'])
            ->whereNull('partner_payment_id')
            ->whereHas('vehicle', function ($q) use ($payment) {
                $q->where('investor_id', $payment->investor_id);
            })
            ->pluck('id');

        $this->assertContains($tripValid->id, $availableTrips);
        $this->assertNotContains($tripOtherInvestor->id, $availableTrips);
        $this->assertNotContains($tripPending->id, $availableTrips);
        $this->assertNotContains($tripAlreadyPaid->id, $availableTrips);
    }
}
