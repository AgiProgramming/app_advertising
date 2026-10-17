<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $ownerRole = Role::firstOrCreate(['name' => 'Owner']);
        $karyawanRole = Role::firstOrCreate(['name' => 'Karyawan']);

        // 1. Akun Admin Utama
        $userAdmin = User::firstOrCreate(
            ['username' => 'admin_utama'],
            [
                'role_id' => $adminRole->id,
                'password' => Hash::make('password123'),
            ]
        );

        Employee::firstOrCreate(
            ['nik' => 'EMP-001'],
            [
                'user_id' => $userAdmin->id,
                'name' => 'Administrator Bengkel',
                'position' => 'Kepala Admin',
                'phone' => '08123456789',
                'address' => 'Kantor Pusat',
                'is_active' => true,
            ]
        );

        // 2. Akun Owner (Pemilik Bengkel)
        User::firstOrCreate(
            ['username' => 'owner_bengkel'],
            [
                'role_id' => $ownerRole->id,
                'password' => Hash::make('password123'),
            ]
        );

        // 3. Akun Karyawan (Teknisi)
        $userKaryawan = User::firstOrCreate(
            ['username' => 'budi_teknisi'],
            [
                'role_id' => $karyawanRole->id,
                'password' => Hash::make('password123'),
            ]
        );

        Employee::firstOrCreate(
            ['nik' => 'EMP-002'],
            [
                'user_id' => $userKaryawan->id,
                'name' => 'Budi Santoso',
                'position' => 'Teknisi Advertising',
                'phone' => '08987654321',
                'address' => 'Workshop Bengkel',
                'is_active' => true,
            ]
        );
    }
}
