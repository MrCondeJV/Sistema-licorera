<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Product;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MembershipExpirationAndBackupManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;
    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected User $adminA;
    protected Plan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = Plan::create([
            'name' => 'Plan Premium SaaS',
            'slug' => 'plan-premium-saas',
            'price_monthly' => 180000,
            'max_users' => 10,
            'max_branches' => 3,
            'max_products' => 2000,
            'features' => ['Kardex', 'Wompi'],
            'is_active' => true,
        ]);

        $this->superadmin = User::create([
            'name' => 'Super Admin Test',
            'email' => 'superadmin@pos.test',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'pin_hash' => \Illuminate\Support\Facades\Hash::make('9999'),
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->tenantA = Tenant::create([
            'name' => 'Licorera Los Sauces',
            'slug' => 'licorera-los-sauces',
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'subscription_status' => 'active',
            'subscription_ends_at' => now()->addDays(20),
        ]);

        $this->tenantB = Tenant::create([
            'name' => 'Licorera El Roble',
            'slug' => 'licorera-el-roble',
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(8),
        ]);

        $this->adminA = User::create([
            'name' => 'Admin Sauces',
            'email' => 'admin@sauces.test',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'pin_hash' => \Illuminate\Support\Facades\Hash::make('1234'),
            'is_superadmin' => false,
            'is_active' => true,
        ]);

        $this->tenantA->users()->attach($this->adminA->id, ['role' => 'admin', 'is_active' => true]);
    }

    public function test_superadmin_index_displays_membership_expiration_column()
    {
        $response = $this->actingAs($this->superadmin)
            ->withSession(['pin_authenticated' => true])
            ->get(route('superadmin.index'));

        $response->assertStatus(200);
        $response->assertSee('Membresía / Vencimiento');
        $response->assertSee('Licorera Los Sauces');
        $response->assertSee('Licorera El Roble');
        $response->assertSee('ACTIVA');
        $response->assertSee('PRUEBA 14D');
        $response->assertSee($this->tenantA->subscription_ends_at->format('d/m/Y'));
    }

    public function test_superadmin_can_view_global_payments_history_and_filter()
    {
        // Create sample payments
        $paymentA = SubscriptionPayment::create([
            'tenant_id' => $this->tenantA->id,
            'plan_id' => $this->plan->id,
            'reference' => 'REF-SAUCES-001',
            'wompi_transaction_id' => 'WMP-11111',
            'amount_in_cents' => 18000000,
            'currency' => 'COP',
            'status' => 'APPROVED',
            'payment_method_type' => 'CARD',
            'paid_at' => now(),
        ]);

        $paymentB = SubscriptionPayment::create([
            'tenant_id' => $this->tenantB->id,
            'plan_id' => $this->plan->id,
            'reference' => 'REF-ROBLE-002',
            'wompi_transaction_id' => 'WMP-22222',
            'amount_in_cents' => 18000000,
            'currency' => 'COP',
            'status' => 'PENDING',
            'payment_method_type' => 'NEQUI',
            'paid_at' => null,
        ]);

        // Global list
        $response = $this->actingAs($this->superadmin)
            ->withSession(['pin_authenticated' => true])
            ->get(route('superadmin.payments'));

        $response->assertStatus(200);
        $response->assertSee('Historial de Pagos SaaS');
        $response->assertSee('REF-SAUCES-001');
        $response->assertSee('REF-ROBLE-002');
        $response->assertSee('APROBADO');
        $response->assertSee('PENDIENTE');

        // Filter by Tenant A
        $filterResponse = $this->actingAs($this->superadmin)
            ->withSession(['pin_authenticated' => true])
            ->get(route('superadmin.payments', ['tenant_id' => $this->tenantA->id]));

        $filterResponse->assertStatus(200);
        $filterResponse->assertSee('REF-SAUCES-001');
        $filterResponse->assertDontSee('REF-ROBLE-002');
    }

    public function test_tenant_admin_billing_displays_expiration_and_payments_history()
    {
        SubscriptionPayment::create([
            'tenant_id' => $this->tenantA->id,
            'plan_id' => $this->plan->id,
            'reference' => 'REF-MY-PAYMENT',
            'wompi_transaction_id' => 'WMP-999',
            'amount_in_cents' => 18000000,
            'currency' => 'COP',
            'status' => 'APPROVED',
            'payment_method_type' => 'CARD',
            'paid_at' => now(),
        ]);

        $response = $this->actingAs($this->adminA)
            ->withSession([
                'pin_authenticated' => true,
                'active_tenant_id' => $this->tenantA->id,
            ])
            ->get(route('billing.index'));

        $response->assertStatus(200);
        $response->assertSee('Vencimiento Membresía');
        $response->assertSee($this->tenantA->subscription_ends_at->format('d/m/Y'));
        $response->assertSee('REF-MY-PAYMENT');
        $response->assertSee('APROBADO');
    }

    public function test_can_generate_general_backup_and_specific_tenant_backup()
    {
        $category = \App\Models\Category::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Licores Nacionales',
            'slug' => 'licores-nacionales',
        ]);

        // Create products for tenant A
        Product::create([
            'tenant_id' => $this->tenantA->id,
            'category_id' => $category->id,
            'sku' => 'AGU-TEST-750',
            'name' => 'Aguardiente Antioqueño 750ml',
            'barcode' => '7701234567890',
            'price_buy' => 25000,
            'price_sell' => 45000,
            'stock' => 50,
            'stock_min' => 5,
            'base_unit' => 'botella',
            'is_liquor' => true,
            'is_active' => true,
        ]);

        // 1. General Backup
        $generalResponse = $this->actingAs($this->superadmin)
            ->withSession(['pin_authenticated' => true])
            ->post(route('superadmin.backups.create'), [
                'backup_type' => 'general',
            ]);

        $generalResponse->assertRedirect(route('superadmin.backups'));
        $generalResponse->assertSessionHas('success');

        // 2. Specific Tenant Backup
        $specificResponse = $this->actingAs($this->superadmin)
            ->withSession(['pin_authenticated' => true])
            ->post(route('superadmin.backups.create'), [
                'backup_type' => 'specific_tenant',
                'tenant_id' => $this->tenantA->id,
            ]);

        $specificResponse->assertRedirect(route('superadmin.backups'));
        $specificResponse->assertSessionHas('success');

        // Verify backups in storage
        $backupDir = storage_path('app/backups');
        $files = File::files($backupDir);
        $foundGeneral = false;
        $foundSpecific = false;

        foreach ($files as $file) {
            if (str_contains($file->getFilename(), 'backup_general_todos_los_equipos_')) {
                $foundGeneral = true;
            }
            if (str_contains($file->getFilename(), "backup_negocio_{$this->tenantA->id}_")) {
                $foundSpecific = true;
                $content = json_decode(File::get($file->getRealPath()), true);
                $this->assertEquals('specific_tenant', $content['metadata']['backup_type']);
                $this->assertEquals($this->tenantA->name, $content['metadata']['tenant_name']);
                $this->assertNotEmpty($content['products']);
            }
        }

        $this->assertTrue($foundGeneral, 'General backup was generated');
        $this->assertTrue($foundSpecific, 'Specific tenant backup was generated with structured JSON');
    }

    public function test_superadmin_impersonate_and_leave_navigation_does_not_bug_menu()
    {
        // 1. Enter Tenant A
        $enterResponse = $this->actingAs($this->superadmin)
            ->withSession(['pin_authenticated' => true])
            ->post(route('superadmin.tenants.enter', $this->tenantA->id));

        $enterResponse->assertRedirect(route('dashboard'));
        $enterResponse->assertSessionHas('superadmin_impersonating', true);
        $enterResponse->assertSessionHas('active_tenant_id', $this->tenantA->id);

        // 2. Access dashboard while impersonating -> should show tenant view with Leave button
        $dashResponse = $this->actingAs($this->superadmin)
            ->withSession([
                'pin_authenticated' => true,
                'superadmin_impersonating' => true,
                'active_tenant_id' => $this->tenantA->id,
            ])
            ->get(route('dashboard'));

        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('Regresar a Superadmin');

        // 3. Leave tenant impersonation
        $leaveResponse = $this->actingAs($this->superadmin)
            ->withSession([
                'pin_authenticated' => true,
                'superadmin_impersonating' => true,
                'active_tenant_id' => $this->tenantA->id,
            ])
            ->get(route('superadmin.leave'));

        $leaveResponse->assertRedirect(route('superadmin.index'));
        $leaveResponse->assertSessionMissing('superadmin_impersonating');
        $leaveResponse->assertSessionMissing('active_tenant_id');

        // 4. Try accessing dashboard without impersonating -> should cleanly redirect back to superadmin.index
        $blockedDashResponse = $this->actingAs($this->superadmin)
            ->withSession([
                'pin_authenticated' => true,
            ])
            ->get(route('dashboard'));

        $blockedDashResponse->assertRedirect(route('superadmin.index'));

        // 5. Try accessing pos without impersonating -> should cleanly redirect to superadmin.index without corrupted session
        $blockedPosResponse = $this->actingAs($this->superadmin)
            ->withSession([
                'pin_authenticated' => true,
            ])
            ->get(route('pos.index'));

        $blockedPosResponse->assertRedirect(route('superadmin.index'));
    }
}
