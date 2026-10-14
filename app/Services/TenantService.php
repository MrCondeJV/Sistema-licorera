<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantService
{
    /**
     * Register a new business with all essential seeds idempotently.
     */
    public function registerBusiness(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // 1. Create or resolve plan
            $plan = null;
            if (!empty($data['plan_slug'])) {
                $plan = Plan::where('slug', $data['plan_slug'])->first();
            }
            if (!$plan) {
                $plan = Plan::firstOrCreate(
                    ['slug' => 'starter'],
                    [
                        'name' => 'Plan Starter',
                        'price_monthly' => 79000,
                        'max_users' => 5,
                        'max_branches' => 1,
                        'max_products' => 500,
                        'is_active' => true,
                    ]
                );
            }

            // 2. Create Tenant
            $slug = Str::slug($data['business_name']);
            $baseSlug = $slug;
            $counter = 1;
            while (Tenant::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $counter++;
            }

            $tenant = Tenant::create([
                'uuid' => (string) Str::uuid(),
                'name' => $data['business_name'],
                'slug' => $slug,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'tax_id' => $data['tax_id'] ?? null,
                'address' => $data['address'] ?? null,
                'timezone' => 'America/Bogota',
                'currency' => 'COP',
                'status' => 'active',
                'plan_id' => $plan->id,
            ]);

            // Set context for child creation
            $context = app(TenantContext::class);
            $context->setTenant($tenant);

            // 3. Create Main Branch
            $branch = Branch::create([
                'tenant_id' => $tenant->id,
                'name' => 'Sede Principal',
                'address' => $data['address'] ?? 'Calle Principal',
                'phone' => $data['phone'] ?? null,
                'is_main' => true,
                'is_active' => true,
            ]);
            $context->setBranch($branch);

            // 4. Create Initial Admin User
            $user = User::create([
                'name' => $data['admin_name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'pin_hash' => !empty($data['pin']) ? bcrypt($data['pin']) : bcrypt(Str::random(32)),
                'password' => !empty($data['password']) ? bcrypt($data['password']) : null,
                'is_superadmin' => false,
                'is_active' => true,
            ]);

            // 5. Associate User to Tenant with 'admin' role
            TenantUser::create([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'role' => 'admin',
                'is_active' => true,
            ]);

            // 6. Default Categories for Liquor Store
            $categories = [
                ['name' => 'Cervezas', 'slug' => 'cervezas', 'icon' => 'beer', 'sort_order' => 1],
                ['name' => 'Aguardientes', 'slug' => 'aguardientes', 'icon' => 'wine', 'sort_order' => 2],
                ['name' => 'Rones', 'slug' => 'rones', 'icon' => 'wine', 'sort_order' => 3],
                ['name' => 'Whiskies', 'slug' => 'whiskies', 'icon' => 'glass-water', 'sort_order' => 4],
                ['name' => 'Vodkas', 'slug' => 'vodkas', 'icon' => 'glass-water', 'sort_order' => 5],
                ['name' => 'Tequilas', 'slug' => 'tequilas', 'icon' => 'flame', 'sort_order' => 6],
                ['name' => 'Ginebras', 'slug' => 'ginebras', 'icon' => 'sparkles', 'sort_order' => 7],
                ['name' => 'Otros licores', 'slug' => 'otros-licores', 'icon' => 'bottle', 'sort_order' => 8],
                ['name' => 'Gaseosas', 'slug' => 'gaseosas', 'icon' => 'cup-soda', 'sort_order' => 9],
                ['name' => 'Mezcladores', 'slug' => 'mezcladores', 'icon' => 'citrus', 'sort_order' => 10],
                ['name' => 'Agua', 'slug' => 'agua', 'icon' => 'droplet', 'sort_order' => 11],
                ['name' => 'Energizantes', 'slug' => 'energizantes', 'icon' => 'zap', 'sort_order' => 12],
                ['name' => 'Snacks', 'slug' => 'snacks', 'icon' => 'cookie', 'sort_order' => 13],
                ['name' => 'Otros productos', 'slug' => 'otros-productos', 'icon' => 'package', 'sort_order' => 14],
            ];

            foreach ($categories as $cat) {
                Category::create([
                    'tenant_id' => $tenant->id,
                    'name' => $cat['name'],
                    'slug' => $cat['slug'],
                    'icon' => $cat['icon'],
                    'sort_order' => $cat['sort_order'],
                    'is_active' => true,
                ]);
            }

            // 7. Default 10 Tables (M1 to M10)
            for ($i = 1; $i <= 10; $i++) {
                Table::create([
                    'tenant_id' => $tenant->id,
                    'branch_id' => $branch->id,
                    'name' => 'M' . $i,
                    'status' => 'free',
                    'capacity' => 4,
                    'sort_order' => $i,
                    'is_active' => true,
                ]);
            }

            // 8. Default Payment Methods (Efectivo y Transacción Bancaria)
            $paymentMethods = [
                ['name' => 'Efectivo', 'code' => 'cash', 'is_cash' => true, 'sort_order' => 1],
                ['name' => 'Transacción Bancaria', 'code' => 'transfer', 'is_cash' => false, 'sort_order' => 2],
            ];

            foreach ($paymentMethods as $pm) {
                PaymentMethod::create([
                    'tenant_id' => $tenant->id,
                    'name' => $pm['name'],
                    'code' => $pm['code'],
                    'is_cash' => $pm['is_cash'],
                    'sort_order' => $pm['sort_order'],
                    'is_active' => true,
                ]);
            }

            // 9. Default Cash Register
            CashRegister::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'name' => 'Caja Principal 01',
                'is_active' => true,
            ]);

            // 10. Default Settings
            $defaultSettings = [
                'business_name' => $tenant->name,
                'tax_id' => $tenant->tax_id ?? '900.000.000-1',
                'address' => $tenant->address ?? '',
                'phone' => $tenant->phone ?? '',
                'receipt_message' => '¡Gracias por visitarnos!',
                'printer_width' => '80mm', // 58mm or 80mm
                'lock_timeout_minutes' => '15',
                'debts_module_enabled' => '1',
                'sound_enabled' => '1',
            ];

            foreach ($defaultSettings as $key => $val) {
                $tenant->setSetting($key, $val);
            }

            // Log creation
            AuditService::log('tenant_registered', 'Tenant', $tenant->id, null, ['name' => $tenant->name], 'Alta inicial de negocio SaaS');

            return [
                'tenant' => $tenant,
                'branch' => $branch,
                'user' => $user,
            ];
        });
    }
}
