<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RootRedirectAndLoginIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function createSuperadmin(): User
    {
        return User::create([
            'name' => 'Global Superadmin',
            'email' => 'superadmin@mambacode.com',
            'password' => bcrypt('SuperAdminSecret123!'),
            'pin_hash' => bcrypt('9999'),
            'is_superadmin' => true,
            'is_active' => true,
        ]);
    }

    protected function createTenantUser(string $roleName = 'admin'): array
    {
        $plan = Plan::create([
            'name' => 'Pro Plan',
            'slug' => 'pro-plan',
            'price' => 99000,
            'billing_cycle' => 'monthly',
            'max_users' => 10,
            'max_branches' => 3,
            'max_products' => 500,
            'has_reports' => true,
            'has_inventory' => true,
            'has_invoicing' => true,
            'is_active' => true,
        ]);

        $tenant = Tenant::create([
            'name' => 'Licorera El Refugio',
            'slug' => 'licorera-el-refugio',
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        $user = User::create([
            'name' => 'Tenant Admin User',
            'email' => 'admin@refugio.com',
            'password' => bcrypt('password123'),
            'pin_hash' => bcrypt('1234'),
            'is_superadmin' => false,
            'is_active' => true,
        ]);

        $user->tenants()->attach($tenant->id, ['role' => $roleName, 'is_active' => true]);

        return [$tenant, $user];
    }

    public function test_root_url_always_redirects_to_login_for_unauthenticated_guests(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('login'));

        $loginResponse = $this->get(route('login'));
        $loginResponse->assertStatus(200);
        $loginResponse->assertSee('Iniciar Sesión');
        $loginResponse->assertSee('LICORERA POS');
    }

    public function test_root_url_redirects_to_login_even_if_user_is_authenticated_superadmin(): void
    {
        $superadmin = $this->createSuperadmin();

        $response = $this->actingAs($superadmin)->get('/');
        $response->assertRedirect(route('login'));

        // Navigating to /login should display login view without looping or bouncing to superadmin
        $loginResponse = $this->actingAs($superadmin)->get(route('login'));
        $loginResponse->assertStatus(200);
        $loginResponse->assertSee('Iniciar Sesión');
        $loginResponse->assertSee('Sesión iniciada:');
        $loginResponse->assertSee($superadmin->name);
        $loginResponse->assertSee(route('superadmin.index'));
    }

    public function test_root_url_redirects_to_login_for_authenticated_tenant_user(): void
    {
        [$tenant, $user] = $this->createTenantUser('admin');

        $response = $this->actingAs($user)->get('/');
        $response->assertRedirect(route('login'));

        $loginResponse = $this->actingAs($user)->get(route('login'));
        $loginResponse->assertStatus(200);
        $loginResponse->assertSee('Iniciar Sesión');
        $loginResponse->assertSee('Sesión iniciada:');
        $loginResponse->assertSee($user->name);
    }

    public function test_superadmin_accessing_superadmin_index_does_not_loop_with_err_too_many_redirects(): void
    {
        $superadmin = $this->createSuperadmin();

        // Even with simulated idle last_activity_time from 2 hours ago
        $response = $this->actingAs($superadmin)
            ->withSession([
                'last_activity_time' => time() - 7200,
                'screen_locked' => false,
            ])
            ->get(route('superadmin.index'));

        $response->assertStatus(200);
        $response->assertSee('Copias de Seguridad');
    }

    public function test_superadmin_can_access_unlock_screen_without_identify_tenant_loop(): void
    {
        $superadmin = $this->createSuperadmin();

        $response = $this->actingAs($superadmin)->get(route('unlock'));
        $response->assertStatus(200);
    }

    public function test_superadmin_can_logout_without_identify_tenant_redirection(): void
    {
        $superadmin = $this->createSuperadmin();

        $response = $this->actingAs($superadmin)->post(route('logout'));
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
