<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CleanProductionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create SaaS Plans
        Plan::firstOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Plan Básico (Starter)',
                'price_monthly' => 79000,
                'max_users' => 3,
                'max_branches' => 1,
                'max_products' => 500,
                'is_active' => true,
            ]
        );

        Plan::firstOrCreate(
            ['slug' => 'pro'],
            [
                'name' => 'Plan Pro (Licorera Estándar)',
                'price_monthly' => 129000,
                'max_users' => 10,
                'max_branches' => 2,
                'max_products' => 2000,
                'is_active' => true,
            ]
        );

        Plan::firstOrCreate(
            ['slug' => 'enterprise'],
            [
                'name' => 'Plan Enterprise (Multi-Sede)',
                'price_monthly' => 199000,
                'max_users' => 30,
                'max_branches' => 5,
                'max_products' => 10000,
                'is_active' => true,
            ]
        );

        // 2. Create the only Superadministrator User
        User::firstOrCreate(
            ['email' => 'superadmin@licorerapos.com'],
            [
                'name' => 'Super Administrador',
                'phone' => '3001112233',
                'pin_hash' => Hash::make('9999'),
                'password' => Hash::make('Admin1234*'),
                'is_superadmin' => true,
                'is_active' => true,
            ]
        );
    }
}
