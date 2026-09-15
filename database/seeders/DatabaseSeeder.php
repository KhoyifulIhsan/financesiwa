<?php

namespace Database\Seeders;

use App\Models\Coa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seeding Roles
        $roles = [
            'Superadmin',
            'Admin Keuangan',
            'Akuntan',
            'Manajer Keuangan',
            'Direktur',
            'Admin Operasional',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }

        // Create Default Superadmin
        $superadmin = User::firstOrCreate(
            ['email' => 'admin@sinarwardana.com'],
            [
                'name' => 'Superadmin',
                'password' => Hash::make('password'),
            ]
        );
        $superadmin->assignRole('Superadmin');

        // 2. Seeding Chart of Accounts (CoA) - Transportir BBM

        // Asset
        $asetLancar = Coa::firstOrCreate(['code' => '1000'], ['name' => 'Aset Lancar', 'type' => 'asset', 'is_active' => true]);
        Coa::firstOrCreate(['code' => '1100'], ['name' => 'Kas Kecil Operasional', 'type' => 'asset', 'parent_id' => $asetLancar->id, 'is_active' => true]);
        Coa::firstOrCreate(['code' => '1200'], ['name' => 'Bank Utama', 'type' => 'asset', 'parent_id' => $asetLancar->id, 'is_active' => true]);
        Coa::firstOrCreate(['code' => '1300'], ['name' => 'Piutang Usaha B2B', 'type' => 'asset', 'parent_id' => $asetLancar->id, 'is_active' => true]);

        // Liability
        $kewajibanJangkaPendek = Coa::firstOrCreate(['code' => '2000'], ['name' => 'Kewajiban Jangka Pendek', 'type' => 'liability', 'is_active' => true]);
        Coa::firstOrCreate(['code' => '2100'], ['name' => 'Hutang Vendor BBM', 'type' => 'liability', 'parent_id' => $kewajibanJangkaPendek->id, 'is_active' => true]);
        Coa::firstOrCreate(['code' => '2200'], ['name' => 'Hutang Bengkel/Sparepart', 'type' => 'liability', 'parent_id' => $kewajibanJangkaPendek->id, 'is_active' => true]);

        // Equity
        Coa::firstOrCreate(['code' => '3000'], ['name' => 'Modal Disetor', 'type' => 'equity', 'is_active' => true]);
        Coa::firstOrCreate(['code' => '3100'], ['name' => 'Laba Ditahan', 'type' => 'equity', 'is_active' => true]);

        // Revenue
        Coa::firstOrCreate(['code' => '4000'], ['name' => 'Pendapatan Jasa Angkut BBM', 'type' => 'revenue', 'is_active' => true]);

        // Expense
        $biayaOperasional = Coa::firstOrCreate(['code' => '5000'], ['name' => 'Biaya Operasional Armada', 'type' => 'expense', 'is_active' => true]);
        Coa::firstOrCreate(['code' => '5100'], ['name' => 'Beban Solar Kendaraan', 'type' => 'expense', 'parent_id' => $biayaOperasional->id, 'is_active' => true]);
        Coa::firstOrCreate(['code' => '5200'], ['name' => 'Beban Tol & Retribusi', 'type' => 'expense', 'parent_id' => $biayaOperasional->id, 'is_active' => true]);
        Coa::firstOrCreate(['code' => '5300'], ['name' => 'Beban Uang Jalan Sopir/Kernet', 'type' => 'expense', 'parent_id' => $biayaOperasional->id, 'is_active' => true]);
        Coa::firstOrCreate(['code' => '5400'], ['name' => 'Beban Susut/Klaim Muatan', 'type' => 'expense', 'parent_id' => $biayaOperasional->id, 'is_active' => true]);

        $this->call([
            DummyDataSeeder::class,
            TestUserSeeder::class,
        ]);
    }
}
