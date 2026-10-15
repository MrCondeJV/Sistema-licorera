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
            'is_active' => true,
            'features' => ['1 Sucursal', 'Venta Rápida', 'Inventario'],
        ]);

        Plan::create([
            'name' => 'Plan Pro Bar & Licorera',
            'slug' => 'pro',
            'price_monthly' => 129000,
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

    public function test_checkout_data_endpoint_generates_valid_integrity_signature_and_pending_payment(): void
    {
        config(['wompi.integrity_secret' => 'test_integrity_secret_key_123']);
        config(['wompi.public_key' => 'pub_test_wompi_12345']);

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
                'reference',
                'signature',
                'redirect_url',
                'customer_email',
                'plan_name',
            ],
        ]);

        $data = $response->json('data');
        $this->assertEquals(12900000, $data['amount_in_cents']); // 129,000 * 100
        $this->assertEquals('pub_test_wompi_12345', $data['public_key']);

        // Verify SHA256 integrity signature
        $expectedSignature = hash('sha256', $data['reference'] . '12900000COP' . 'test_integrity_secret_key_123');
        $this->assertEquals($expectedSignature, $data['signature']);

        // Verify pending payment recorded in DB
        $payment = SubscriptionPayment::where('reference', $data['reference'])->first();
        $this->assertNotNull($payment);
        $this->assertEquals('PENDING', $payment->status);
        $this->assertEquals(12900000, $payment->amount_in_cents);
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
            'amount_in_cents' => 12900000,
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
                    'amount_in_cents' => 12900000,
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

        $user = $this->createAdminUser($tenant, 'sauces@licorera.test');

        // 1. Visit backup index
        $response = $this->actingAs($user)
            ->withSession([
                'active_tenant_id' => $tenant->id,
                'active_branch_id' => $branch->id,
                'screen_locked' => false,
            ])
            ->get(route('backups.index'));

        $response->assertStatus(200);
        $response->assertSee('Copias de Seguridad');

        // 2. Create backup
        $createResponse = $this->actingAs($user)
            ->withSession([
                'active_tenant_id' => $tenant->id,
                'active_branch_id' => $branch->id,
                'screen_locked' => false,
            ])
            ->post(route('backups.create'));

        $createResponse->assertRedirect(route('backups.index'));
        $createResponse->assertSessionHas('success');

        // Verify backup file exists in storage
        $backupDir = storage_path('app/backups');
        $files = File::files($backupDir);
        $this->assertNotEmpty($files);

        $latestFile = $files[0]->getFilename();

        // 3. Download backup
        $downloadResponse = $this->actingAs($user)
            ->withSession([
                'active_tenant_id' => $tenant->id,
                'active_branch_id' => $branch->id,
                'screen_locked' => false,
            ])
            ->get(route('backups.download', ['filename' => $latestFile]));

        $downloadResponse->assertStatus(200);

        // 4. Delete backup
        $deleteResponse = $this->actingAs($user)
            ->withSession([
                'active_tenant_id' => $tenant->id,
                'active_branch_id' => $branch->id,
                'screen_locked' => false,
            ])
            ->delete(route('backups.destroy', ['filename' => $latestFile]));

        $deleteResponse->assertRedirect(route('backups.index'));
        $deleteResponse->assertSessionHas('success');
    }
}
