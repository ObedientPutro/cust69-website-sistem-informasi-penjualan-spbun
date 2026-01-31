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
        // OWNER (Pemilik)
        User::create([
            'name' => 'Bapak Owner',
            'email' => 'owner@spbun.com',
            'password' => Hash::make('password'),
            'nip' => 'OWN-001',
            'address' => 'Jl. Merdeka No. 1, Jakarta',
            'phone' => '08110000111',
            'role' => UserRoleEnum::OWNER,
            'is_active' => true,
        ]);

        // OPERATOR 1
        User::create([
            'name' => 'Budi Santoso',
            'email' => 'op1@spbun.com',
            'password' => Hash::make('password'),
            'nip' => 'OPR-001',
            'address' => 'Gang Kelinci No. 3',
            'phone' => '08567890123',
            'role' => UserRoleEnum::OPERATOR,
            'is_active' => true,
        ]);

        // OPERATOR 2
        User::create([
            'name' => 'Joko Anwar',
            'email' => 'op2@spbun.com',
            'password' => Hash::make('password'),
            'nip' => 'OPR-002',
            'address' => 'Perumahan Damai Blok C2',
            'phone' => '08987654321',
            'role' => UserRoleEnum::OPERATOR,
            'is_active' => true,
        ]);

        // OPERATOR 3
        User::create([
            'name' => 'Rina Wati',
            'email' => 'op3@spbun.com',
            'password' => Hash::make('password'),
            'nip' => 'OPR-003',
            'address' => 'Jl. Kenangan Mantan No. 99',
            'phone' => '087712345678',
            'role' => UserRoleEnum::OPERATOR,
            'is_active' => true,
        ]);
    }
}
