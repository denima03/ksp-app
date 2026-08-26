<?php

namespace Database\Seeders;

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
        // 1. Buat Role Dasar Koperasi
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $pengurusRole   = Role::firstOrCreate(['name' => 'pengurus', 'guard_name' => 'web']);
        $anggotaRole    = Role::firstOrCreate(['name' => 'anggota', 'guard_name' => 'web']);

        // 2. Buat User Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@koperasi.com'],
            [
                'name' => 'Super Admin Koperasi',
                'password' => Hash::make('password'),
            ]
        );
        $superAdmin->assignRole($superAdminRole);

        // 3. Buat User Pengurus
        $pengurus = User::firstOrCreate(
            ['email' => 'pengurus@koperasi.com'],
            [
                'name' => 'Pengurus Koperasi',
                'password' => Hash::make('password'),
            ]
        );
        $pengurus->assignRole($pengurusRole);
    }
}
