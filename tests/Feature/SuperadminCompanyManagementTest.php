<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperadminCompanyManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private Plan $starterPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->starterPlan = Plan::create([
            'name' => 'Plan Starter',
            'slug' => 'starter',
            'price_monthly' => 79000,
            'max_users' => 5,
            'max_branches' => 1,
            'max_products' => 500,
            'is_active' => true,
        ]);

        $this->superadmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@test.com',
            'pin_hash' => Hash::make('9999'),
            'is_superadmin' => true,
            'is_active' => true,
        ]);
    }

    public function test_superadmin_can_view_companies_list_and_create_form(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->withSession(['pin_authenticated' => true])
            ->get(route('superadmin.index'));

        $response->assertStatus(200);
        $response->assertSee('Plataforma SaaS — Panel Superadministrador');
        $response->assertSee('Nueva Empresa / Licorera');

        $createResponse = $this->actingAs($this->superadmin)
            ->withSession(['pin_authenticated' => true])
            ->get(route('superadmin.tenants.create'));

        $createResponse->assertStatus(200);
        $createResponse->assertSee('Crear Nueva Empresa / Licorera');
    }

    public function test_superadmin_can_create_a_new_company_with_auto_provisioning(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->withSession(['pin_authenticated' => true])
            ->post(route('superadmin.tenants.store'), [
                'business_name' => 'Licorera Medellín Premium',
                'admin_name' => 'Sara Morales',
                'email' => 'sara@licorerapremium.com',
                'phone' => '3119876543',
                'tax_id' => '900.123.456-7',
                'address' => 'Circular 4ta # 70-15',
                'pin' => '4321',
                'password' => 'Secreta2026*',
                'plan_id' => $this->starterPlan->id,
            ]);

        $response->assertRedirect(route('superadmin.index'));
        $response->assertSessionHas('success');

        $tenant = Tenant::where('name', 'Licorera Medellín Premium')->first();
        $this->assertNotNull($tenant);
        $this->assertEquals('active', $tenant->status);
        $this->assertCount(1, $tenant->branches);
        $this->assertCount(1, $tenant->users);

        $adminUser = $tenant->users()->first();
        $this->assertEquals('Sara Morales', $adminUser->name);
        $this->assertTrue(Hash::check('Secreta2026*', $adminUser->password));
    }

    public function test_superadmin_can_edit_company_information(): void
    {
        $tenant = Tenant::create([
            'name' => 'Licorera Antigua',
            'slug' => 'licorera-antigua',
            'email' => 'info@antigua.com',
            'status' => 'active',
            'plan_id' => $this->starterPlan->id,
        ]);

        $editViewResponse = $this->actingAs($this->superadmin)
            ->withSession(['pin_authenticated' => true])
            ->get(route('superadmin.tenants.edit', $tenant->id));

        $editViewResponse->assertStatus(200);
        $editViewResponse->assertSee('Editar Empresa: Licorera Antigua');

        $updateResponse = $this->actingAs($this->superadmin)
            ->withSession(['pin_authenticated' => true])
            ->put(route('superadmin.tenants.update', $tenant->id), [
                'name' => 'Licorera Antigua Renovada',
                'email' => 'nuevo@antigua.com',
                'phone' => '3001234567',
                'tax_id' => '800.111.222-3',
                'address' => 'Avenida El Poblado # 10-20',
                'status' => 'active',
                'plan_id' => $this->starterPlan->id,
            ]);

        $updateResponse->assertRedirect(route('superadmin.index'));
        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Licorera Antigua Renovada',
            'email' => 'nuevo@antigua.com',
        ]);
    }

    public function test_superadmin_can_impersonate_and_leave_company(): void
    {
        $tenant = Tenant::create([
            'name' => 'Licorera Oasis',
            'slug' => 'licorera-oasis',
            'status' => 'active',
            'plan_id' => $this->starterPlan->id,
        ]);

        $enterResponse = $this->actingAs($this->superadmin)
            ->withSession(['pin_authenticated' => true])
            ->post(route('superadmin.tenants.enter', $tenant->id));

        $enterResponse->assertRedirect(route('dashboard'));
        $enterResponse->assertSessionHas('active_tenant_id', $tenant->id);
        $enterResponse->assertSessionHas('superadmin_impersonating', true);

        // Leave back to superadmin
        $leaveResponse = $this->actingAs($this->superadmin)
            ->withSession(['pin_authenticated' => true, 'active_tenant_id' => $tenant->id])
            ->get(route('superadmin.leave'));

        $leaveResponse->assertRedirect(route('superadmin.index'));
        $leaveResponse->assertSessionMissing('superadmin_impersonating');
    }
}
