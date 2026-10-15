<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\WompiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WompiBillingAndSaaSTrialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic plans for SaaS pricing
        Plan::create([
            'name' => 'Plan Estándar Licorera',
            'slug' => 'estandar',
            'price_monthly' => 79000,
            'max_users' => 3,
            'max_branches' => 1,
            'max_products' => 500,
            'is_active' => true,
            'features' => ['1 Sucursal', 'Venta Rápida', 'Inventario'],
        ]);

        Plan::create([
            'name' => 'Plan Pro Bar & Licorera',
            'slug' => 'pro',
            'price_monthly' => 129000,
            'max_users' => 10,
            'max_branches' => 2,
            'max_products' => 2000,
            'is_active' => true,
            'features' => ['Mesas Ilimitadas', 'Ticket WhatsApp', 'Wompi Integrado', 'Backups Automáticos'],
        ]);
    }

    protected function createAdminUser(Tenant $tenant, string $email = 'admin@licorera.test'): User
    {
        $user = User::create([
            'name' => 'Administrador Prueba',
            'email' => $email,
            'phone' => '3009998877',
            'pin_hash' => Hash::make('1234'),
            'password' => Hash::make('Secret123*'),
            'is_active' => true,
        ]);

        $user->tenants()->attach($tenant->id, ['role' => 'admin', 'is_active' => true]);

        return $user;
    }

    protected function createSuperadmin(): User
    {
        return User::create([
            'name' => 'Super Administrador Mambacode',
            'email' => 'superadmin@mambacode.test',
            'phone' => '3001112233',
            'pin_hash' => Hash::make('9999'),
            'password' => Hash::make('Admin1234*'),
            'is_superadmin' => true,
            'is_active' => true,
        ]);
    }

    public function test_landing_page_renders_for_unauthenticated_guests(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('LICORERA POS');
        $response->assertSee('PROBAR 14 DÍAS GRATIS');
        $response->assertSee('Wompi');
        $response->assertSee('Plan Básico');
        $response->assertSee('Plan Pro');
        $response->assertSee('Plan Enterprise');
    }

    public function test_business_auto_registration_creates_tenant_with_14_day_free_trial(): void
    {
        $payload = [
            'business_name' => 'Licorera La Ronda Express',
            'admin_name' => 'Carlos Dueño',
            'email' => 'carlos@laronda.co',
            'phone' => '3001234567',
            'password' => 'Secreta123*',
            'pin' => '7788',
            'plan_slug' => 'pro',
        ];

        $response = $this->post(route('business.register.submit'), $payload);

        $response->assertRedirect(route('pos.index'));
        $this->assertAuthenticated();

        /** @var Tenant $tenant */
        $tenant = Tenant::withoutGlobalScopes()->where('name', 'Licorera La Ronda Express')->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('trial', $tenant->subscription_status);
        $this->assertNotNull($tenant->trial_ends_at);
        $this->assertTrue($tenant->trial_ends_at->isFuture());
        $this->assertTrue($tenant->isOnTrial());
        $this->assertTrue($tenant->hasAccess());
        $this->assertFalse($tenant->isExpired());
        $this->assertGreaterThanOrEqual(13, $tenant->daysRemaining());

        // Check admin user created and linked
        $user = User::where('email', 'carlos@laronda.co')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRoleInTenant($tenant->id, 'admin'));
    }

    public function test_billing_index_displays_subscription_status_and_wompi_plans(): void
    {
        $tenant = Tenant::create([
            'name' => 'Licorera El Barril',
            'slug' => 'licorera-el-barril',
            'status' => 'active',
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(10),
        ]);

        $branch = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Principal',
        ]);

        $user = $this->createAdminUser($tenant, 'barril@licorera.test');

        $response = $this->actingAs($user)
            ->withSession([
                'active_tenant_id' => $tenant->id,
                'active_branch_id' => $branch->id,
                'screen_locked' => false,
            ])
            ->get(route('billing.index'));

        $response->assertStatus(200);
        $response->assertSee('Suscripción SaaS y Facturación');
        $response->assertSee('PRUEBA GRATUITA');
        $response->assertSee('Plan Pro Bar & Licorera');
        $response->assertSee('PAGAR CON WOMPI');
    }

    public function test_checkout_data_endpoint_generates_valid_integrity_signature_and_fee_surcharge(): void
    {
        config(['wompi.integrity_secret' => 'test_integrity_secret_key_123']);
        config(['wompi.public_key' => 'pub_test_wompi_12345']);
        config(['wompi.apply_surcharge' => true]);

        $plan = Plan::where('slug', 'pro')->first();

        $tenant = Tenant::create([
            'name' => 'Discoteca Tabú',
            'slug' => 'discoteca-tabu',
            'status' => 'active',
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(2),
        ]);

        $branch = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Sede Centro',
        ]);

        $user = $this->createAdminUser($tenant, 'admin@tabu.com');

        $response = $this->actingAs($user)
            ->withSession([
                'active_tenant_id' => $tenant->id,
                'active_branch_id' => $branch->id,
                'screen_locked' => false,
            ])
            ->postJson(route('billing.checkout-data'), [
                'plan_id' => $plan->id,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'public_key',
                'currency',
                'amount_in_cents',
                'base_amount',
                'surcharge',
                'total_amount',
                'reference',
                'signature',
                'redirect_url',
                'customer_email',
                'plan_name',
            ],
        ]);

        $data = $response->json('data');
        $this->assertEquals('pub_test_wompi_12345', $data['public_key']);
        $this->assertEquals(129000, $data['base_amount']);
        $this->assertGreaterThan(0, $data['surcharge']); // Wompi fee surcharge
        $this->assertGreaterThan(129000, $data['total_amount']);
        $this->assertEquals($data['total_amount'] * 100, $data['amount_in_cents']);

        // Verify SHA256 integrity signature matching total amount in cents
        $expectedSignature = hash('sha256', $data['reference'] . $data['amount_in_cents'] . 'COP' . 'test_integrity_secret_key_123');
        $this->assertEquals($expectedSignature, $data['signature']);

        // Verify pending payment recorded in DB with full total in cents
        $payment = SubscriptionPayment::where('reference', $data['reference'])->first();
        $this->assertNotNull($payment);
        $this->assertEquals('PENDING', $payment->status);
        $this->assertEquals($data['amount_in_cents'], $payment->amount_in_cents);
    }

    public function test_wompi_webhook_activates_subscription_for_approved_transaction(): void
    {
        $plan = Plan::where('slug', 'pro')->first();

        $tenant = Tenant::create([
            'name' => 'Licorería San Juan',
            'slug' => 'licoreria-san-juan',
            'status' => 'active',
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->addDays(1),
        ]);

        $reference = 'LIC-SUB-' . $tenant->id . '-' . $plan->id . '-999999';

        $pendingPayment = SubscriptionPayment::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'reference' => $reference,
            'amount_in_cents' => 13406100,
            'currency' => 'COP',
            'status' => 'PENDING',
        ]);

        $webhookPayload = [
            'event' => 'transaction.updated',
            'data' => [
                'transaction' => [
                    'id' => 'wompi_trx_789456',
                    'reference' => $reference,
                    'status' => 'APPROVED',
                    'amount_in_cents' => 13406100,
                    'currency' => 'COP',
                    'payment_method_type' => 'NEQUI',
                ],
            ],
            'timestamp' => time(),
        ];

        $response = $this->postJson(route('wompi.webhook'), $webhookPayload);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'ok',
            'payment_id' => $pendingPayment->id,
            'status_processed' => 'APPROVED',
        ]);

        // Verify payment updated
        $pendingPayment->refresh();
        $this->assertEquals('APPROVED', $pendingPayment->status);
        $this->assertEquals('wompi_trx_789456', $pendingPayment->wompi_transaction_id);
        $this->assertEquals('NEQUI', $pendingPayment->payment_method_type);
        $this->assertNotNull($pendingPayment->paid_at);

        // Verify tenant subscription renewed for 30 days
        $tenant->refresh();
        $this->assertEquals('active', $tenant->subscription_status);
        $this->assertNotNull($tenant->subscription_ends_at);
        $this->assertTrue($tenant->hasActiveSubscription());
        $this->assertTrue($tenant->hasAccess());
        $this->assertGreaterThanOrEqual(29, $tenant->daysRemaining());
    }

    public function test_backup_controller_can_generate_and_download_database_snapshots(): void
    {
        $tenant = Tenant::create([
            'name' => 'Licorera Los Sauces',
            'slug' => 'licorera-los-sauces',
            'status' => 'active',
        ]);

        $branch = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Principal',
        ]);

        $superadmin = $this->createSuperadmin();

        // 1. Visit backup index as superadmin
        $response = $this->actingAs($superadmin)
            ->withSession([
                'screen_locked' => false,
                'pin_authenticated' => true,
            ])
            ->get(route('superadmin.backups'));

        $response->assertStatus(200);
        $response->assertSee('Copias de Seguridad');

        // 2. Create backup
        $createResponse = $this->actingAs($superadmin)
            ->withSession([
                'screen_locked' => false,
                'pin_authenticated' => true,
            ])
            ->post(route('superadmin.backups.create'), [
                'backup_type' => 'general',
            ]);

        $createResponse->assertRedirect(route('superadmin.backups'));
        $createResponse->assertSessionHas('success');

        // Verify backup file exists in storage
        $backupDir = storage_path('app/backups');
        $files = File::files($backupDir);
        $this->assertNotEmpty($files);

        $latestFile = $files[0]->getFilename();

        // 3. Download backup
        $downloadResponse = $this->actingAs($superadmin)
            ->withSession([
                'screen_locked' => false,
                'pin_authenticated' => true,
            ])
            ->get(route('superadmin.backups.download', ['filename' => $latestFile]));

        $downloadResponse->assertStatus(200);

        // 4. Delete backup
        $deleteResponse = $this->actingAs($superadmin)
            ->withSession([
                'screen_locked' => false,
                'pin_authenticated' => true,
            ])
            ->delete(route('superadmin.backups.destroy', ['filename' => $latestFile]));

        $deleteResponse->assertRedirect(route('superadmin.backups'));
        $deleteResponse->assertSessionHas('success');
    }

    public function test_superadmin_has_exclusive_saas_menu_without_operational_links(): void
    {
        $superadmin = $this->createSuperadmin();

        $response = $this->actingAs($superadmin)
            ->withSession([
                'screen_locked' => false,
                'superadmin_impersonating' => false,
            ])
            ->get(route('superadmin.index'));

        $response->assertStatus(200);

        // Superadmin navigation options are present
        $response->assertSee('Empresas');
        $response->assertSee('Planes y Precios SaaS');
        $response->assertSee('Copias de Seguridad');
        $response->assertSee('Auditoría Global SaaS');

        // Operational liquor store options are hidden
        $response->assertDontSee('Venta Rápida (POS)');
        $response->assertDontSee('Mesas y Cuentas');
        $response->assertDontSee('Gastos Operativos');
        $response->assertDontSee('Compras de Stock');
    }

    public function test_superadmin_can_manage_saas_plans(): void
    {
        $superadmin = $this->createSuperadmin();

        // 1. Visit plans index
        $indexResponse = $this->actingAs($superadmin)
            ->withSession(['screen_locked' => false])
            ->get(route('superadmin.plans.index'));

        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Planes y Precios de Suscripción SaaS');
        $indexResponse->assertSee('Plan Pro Bar & Licorera');

        // 2. Create new plan
        $createResponse = $this->actingAs($superadmin)
            ->withSession(['screen_locked' => false])
            ->post(route('superadmin.plans.store'), [
                'name' => 'Plan Mega Discoteca',
                'slug' => 'mega-discoteca',
                'price_monthly' => 249000,
                'max_users' => 25,
                'max_branches' => 5,
                'max_products' => 10000,
                'features' => "Soporte VIP\nMesas Ilimitadas\nBackups en la nube cada hora",
                'is_active' => 1,
            ]);

        $createResponse->assertRedirect(route('superadmin.plans.index'));
        $createResponse->assertSessionHas('success');

        $createdPlan = Plan::where('slug', 'mega-discoteca')->first();
        $this->assertNotNull($createdPlan);
        $this->assertEquals(249000, $createdPlan->price_monthly);
        $this->assertEquals(25, $createdPlan->max_users);
        $this->assertTrue($createdPlan->is_active);

        // 3. Edit plan
        $updateResponse = $this->actingAs($superadmin)
            ->withSession(['screen_locked' => false])
            ->put(route('superadmin.plans.update', $createdPlan), [
                'name' => 'Plan Mega Discoteca VIP',
                'slug' => 'mega-discoteca',
                'price_monthly' => 289000,
                'max_users' => 30,
                'max_branches' => 6,
                'max_products' => 15000,
                'features' => "Soporte VIP 24/7\nMesas Ilimitadas",
                'is_active' => 1,
            ]);

        $updateResponse->assertRedirect(route('superadmin.plans.index'));
        $createdPlan->refresh();
        $this->assertEquals('Plan Mega Discoteca VIP', $createdPlan->name);
        $this->assertEquals(289000, $createdPlan->price_monthly);

        // 4. Toggle plan active status
        $toggleResponse = $this->actingAs($superadmin)
            ->withSession(['screen_locked' => false])
            ->post(route('superadmin.plans.toggle', $createdPlan));

        $toggleResponse->assertRedirect(route('superadmin.plans.index'));
        $createdPlan->refresh();
        $this->assertFalse($createdPlan->is_active);
    }

    public function test_expired_tenant_within_5_day_grace_period_can_still_operate_with_warning(): void
    {
        $tenant = Tenant::create([
            'name' => 'Licorera Los Pinos',
            'slug' => 'licorera-los-pinos',
            'status' => 'active',
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->subDays(2), // Expired 2 days ago (in 5-day grace period)
        ]);

        $branch = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Principal',
        ]);

        $user = $this->createAdminUser($tenant, 'pinos@licorera.test');

        $this->assertTrue($tenant->isPastDue());
        $this->assertFalse($tenant->isGracePeriodExceeded());
        $this->assertEquals(3, $tenant->graceDaysRemaining()); // 5 - 2 = 3 days remaining

        // Can access POS during grace period
        $response = $this->actingAs($user)
            ->withSession([
                'active_tenant_id' => $tenant->id,
                'active_branch_id' => $branch->id,
                'screen_locked' => false,
            ])
            ->get(route('pos.index'));

        $response->assertStatus(200);
        $response->assertSee('Período de Gracia');
        $response->assertSee('3 días de gracia');
    }

    public function test_expired_tenant_beyond_5_day_grace_period_is_strictly_blocked_from_operations(): void
    {
        $tenant = Tenant::create([
            'name' => 'Licorera La Bloqueada',
            'slug' => 'licorera-la-bloqueada',
            'status' => 'active',
            'subscription_status' => 'trial',
            'trial_ends_at' => now()->subDays(6), // Expired 6 days ago (exceeded 5 days grace)
        ]);

        $branch = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Principal',
        ]);

        $user = $this->createAdminUser($tenant, 'bloqueada@licorera.test');

        $this->assertTrue($tenant->isPastDue());
        $this->assertTrue($tenant->isGracePeriodExceeded());
        $this->assertEquals(0, $tenant->graceDaysRemaining());

        // Attempting to access POS must be intercepted and redirected to billing
        $posResponse = $this->actingAs($user)
            ->withSession([
                'active_tenant_id' => $tenant->id,
                'active_branch_id' => $branch->id,
                'screen_locked' => false,
            ])
            ->get(route('pos.index'));

        $posResponse->assertRedirect(route('billing.index'));
        $posResponse->assertSessionHas('error');

        // Attempting to access Tables must also be redirected to billing
        $tablesResponse = $this->actingAs($user)
            ->withSession([
                'active_tenant_id' => $tenant->id,
                'active_branch_id' => $branch->id,
                'screen_locked' => false,
            ])
            ->get(route('tables.index'));

        $tablesResponse->assertRedirect(route('billing.index'));

        // But accessing billing to pay is allowed!
        $billingResponse = $this->actingAs($user)
            ->withSession([
                'active_tenant_id' => $tenant->id,
                'active_branch_id' => $branch->id,
                'screen_locked' => false,
            ])
            ->get(route('billing.index'));

        $billingResponse->assertStatus(200);
        $billingResponse->assertSee('SERVICIO SUSPENDIDO POR FALTA DE PAGO');
    }
}
