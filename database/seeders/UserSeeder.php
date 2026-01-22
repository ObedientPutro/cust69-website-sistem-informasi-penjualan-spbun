<?php

namespace Database\Seeders;

use App\Enums\UserRoleEnum;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. OWNER (Pemilik)
        User::create([
            'name' => 'Bapak Owner',
            'email' => 'owner@spbun.com',
            'password' => Hash::make('password'),
            'nip' => 'OWN-001',
            'birth_date' => '1980-01-01',
            'address' => 'Jl. Merdeka No. 1, Jakarta',
            'phone' => '08110000111',
            'role' => UserRoleEnum::OWNER,
            'is_active' => true,
        ]);

        // 2. ADMIN (Kepala Admin/Staff)
        User::create([
            'name' => 'Siti Aminah',
            'email' => 'admin@spbun.com',
            'password' => Hash::make('password'),
            'nip' => 'ADM-001',
            'birth_date' => '1992-05-12',
            'address' => 'Jl. Melati Indah No. 45',
            'phone' => '081234567890',
            'role' => UserRoleEnum::ADMIN,
            'is_active' => true,
        ]);

        // 3. OPERATOR 1
        User::create([
            'name' => 'Budi Santoso',
            'email' => 'op1@spbun.com',
            'password' => Hash::make('password'),
            'nip' => 'OPR-001',
            'birth_date' => '1995-08-17',
            'address' => 'Gang Kelinci No. 3',
            'phone' => '08567890123',
            'role' => UserRoleEnum::OPERATOR,
            'is_active' => true,
        ]);

        // 4. OPERATOR 2
        User::create([
            'name' => 'Joko Anwar',
            'email' => 'op2@spbun.com',
            'password' => Hash::make('password'),
            'nip' => 'OPR-002',
            'birth_date' => '1998-12-01',
            'address' => 'Perumahan Damai Blok C2',
            'phone' => '08987654321',
            'role' => UserRoleEnum::OPERATOR,
            'is_active' => true,
        ]);

        // 5. OPERATOR 3
        User::create([
            'name' => 'Rina Wati',
            'email' => 'op3@spbun.com',
            'password' => Hash::make('password'),
            'nip' => 'OPR-003',
            'birth_date' => '2000-02-14',
            'address' => 'Jl. Kenangan Mantan No. 99',
            'phone' => '087712345678',
            'role' => UserRoleEnum::OPERATOR,
            'is_active' => true,
        ]);
    }
}
