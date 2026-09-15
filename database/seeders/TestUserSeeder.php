<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $testUsers = [
            [
                'name' => 'Budi Akuntan',
                'email' => 'akuntan@sinarwardana.com',
                'role' => 'Akuntan',
            ],
            [
                'name' => 'Siti Finance',
                'email' => 'finance@sinarwardana.com',
                'role' => 'Admin Keuangan',
            ],
            [
                'name' => 'Pak Manajer',
                'email' => 'manajer@sinarwardana.com',
                'role' => 'Manajer Keuangan',
            ],
            [
                'name' => 'Bu Direktur',
                'email' => 'direktur@sinarwardana.com',
                'role' => 'Direktur',
            ],
            [
                'name' => 'Joko Operasional',
                'email' => 'operasional@sinarwardana.com',
                'role' => 'Admin Operasional',
            ],
        ];

        foreach ($testUsers as $userData) {
            // Pastikan role sudah ada
            Role::firstOrCreate(['name' => $userData['role']]);

            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                ]
            );

            $user->syncRoles([$userData['role']]);
        }
    }
}
