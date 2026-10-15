<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperadminTenantUserCredentialsTest extends TestCase
{
    use RefreshDatabase;

    private User $superadmin;
    private Tenant $tenant;
    private Branch $branch;
    private User $tenantAdmin;
    private User $tenantCashier;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create([
            'name' => 'Plan Pro',
            'slug' => 'pro',
            'price_monthly' => 120000,
            'max_users' => 10,
            'max_branches' => 2,
            'max_products' => 1000,
            'is_active' => true,
        ]);

        $this->superadmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@mambacode.com',
            'password' => Hash::make('SuperSecret123!'),
            'pin_hash' => Hash::make('9999'),
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'Licorera La 70',
            'slug' => 'licorera-la-70',
            'email' => 'la70@licores.com',
            'status' => 'active',
            'plan_id' => $plan->id,
            'plan_expires_at' => now()->addMonth(),
        ]);

        $this->branch = Branch::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Sede Laureles',
            'address' => 'Circular 4ta #70-15',
            'is_main' => true,
            'is_active' => true,
        ]);

        $this->tenantAdmin = User::create([
            'name' => 'Carlos Administrador',
            'email' => 'carlos@licorera70.com',
            'password' => Hash::make('OldPassword123!'),
            'pin_hash' => Hash::make('1111'),
            'is_superadmin' => false,
            'is_active' => true,
        ]);

        $this->tenant->users()->attach($this->tenantAdmin->id, [
            'role' => 'admin',
            'is_active' => true,
        ]);
        $this->branch->users()->attach($this->tenantAdmin->id, ['tenant_id' => $this->tenant->id]);

        $this->tenantCashier = User::create([
            'name' => 'Andres Cajero',
            'email' => 'andres@licorera70.com',
            'password' => Hash::make('CajeroOld456!'),
            'pin_hash' => Hash::make('2222'),
            'is_superadmin' => false,
            'is_active' => true,
        ]);

        $this->tenant->users()->attach($this->tenantCashier->id, [
            'role' => 'cashier',
            'is_active' => true,
        ]);
        $this->branch->users()->attach($this->tenantCashier->id, ['tenant_id' => $this->tenant->id]);
    }

    public function test_superadmin_can_view_tenant_users_list(): void
    {
        $response = $this->actingAs($this->superadmin)
            ->withSession(['pin_authenticated' => true])
            ->get(route('superadmin.tenants.users', $this->tenant->id));

        $response->assertStatus(200);
        $response->assertSee('Empleados y Credenciales: Licorera La 70');
        $response->assertSee('Carlos Administrador');
        $response->assertSee('carlos@licorera70.com');
        $response->assertSee('Andres Cajero');
        $response->assertSee('andres@licorera70.com');
        $response->assertSee('Sede Laureles');
    }

    public function test_non_superadmin_cannot_access_tenant_users_management(): void
    {
        $response = $this->actingAs($this->tenantAdmin)
            ->withSession([
                'tenant_id' => $this->tenant->id,
                'branch_id' => $this->branch->id,
                'pin_authenticated' => true,
            ])
            ->get(route('superadmin.tenants.users', $this->tenant->id));

        $response->assertStatus(403);
    }

    public function test_superadmin_can_update_user_credentials_and_pin(): void
    {
        $payload = [
            'name' => 'Andres Martinez Cajero',
            'email' => 'andres.martinez@licorera70.com',
            'phone' => '3009988776',
            'password' => 'NewStrongPass987!',
            'pin' => '7890',
            'role' => 'waiter',
            'is_active' => '1',
            'branch_ids' => [$this->branch->id],
        ];

        $response = $this->actingAs($this->superadmin)
            ->withSession(['pin_authenticated' => true])
            ->put(route('superadmin.tenants.users.update', [$this->tenant->id, $this->tenantCashier->id]), $payload);

        $response->assertRedirect(route('superadmin.tenants.users', $this->tenant->id));
        $response->assertSessionHas('success');

        $this->tenantCashier->refresh();

        $this->assertEquals('Andres Martinez Cajero', $this->tenantCashier->name);
        $this->assertEquals('andres.martinez@licorera70.com', $this->tenantCashier->email);
        $this->assertEquals('3009988776', $this->tenantCashier->phone);

        // Check password changed
        $this->assertTrue(Hash::check('NewStrongPass987!', $this->tenantCashier->password));

        // Check PIN changed
        $this->assertTrue($this->tenantCashier->verifyPin('7890'));

        // Check role in tenant updated to waiter
        $this->assertEquals('waiter', $this->tenantCashier->getRoleInTenant($this->tenant->id));

        // Check audit log was written (superadmin actions are globally audited)
        $auditLog = AuditLog::where('action', 'superadmin_user_credentials_updated')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals($this->superadmin->id, $auditLog->user_id);
        $this->assertStringContainsString('Andres Martinez Cajero', $auditLog->reason);
        $this->assertEquals('andres.martinez@licorera70.com', $auditLog->new_values['email']);
    }

    public function test_superadmin_can_create_new_tenant_user_directly(): void
    {
        $payload = [
            'name' => 'Mariana Mesera',
            'email' => 'mariana@licorera70.com',
            'phone' => '3123456789',
            'password' => 'MarianaPass2026!',
            'pin' => '3344',
            'role' => 'waiter',
            'is_active' => '1',
            'branch_ids' => [$this->branch->id],
        ];

        $response = $this->actingAs($this->superadmin)
            ->withSession(['pin_authenticated' => true])
            ->post(route('superadmin.tenants.users.store', $this->tenant->id), $payload);

        $response->assertRedirect(route('superadmin.tenants.users', $this->tenant->id));
        $response->assertSessionHas('success');

        $newUser = User::where('email', 'mariana@licorera70.com')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals('Mariana Mesera', $newUser->name);
        $this->assertTrue(Hash::check('MarianaPass2026!', $newUser->password));
        $this->assertTrue($newUser->verifyPin('3344'));

        // Check role and branches
        $this->assertEquals('waiter', $newUser->getRoleInTenant($this->tenant->id));
        $this->assertTrue($newUser->branches->contains($this->branch->id));

        // Check audit log
        $auditLog = AuditLog::where('action', 'superadmin_user_created')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals($this->superadmin->id, $auditLog->user_id);
    }

    public function test_superadmin_updating_user_validates_email_uniqueness(): void
    {
        // Attempting to set Carlos's email to Andres's email must fail
        $payload = [
            'name' => 'Carlos Modificado',
            'email' => 'andres@licorera70.com', // Duplicate!
            'role' => 'admin',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->superadmin)
            ->withSession(['pin_authenticated' => true])
            ->put(route('superadmin.tenants.users.update', [$this->tenant->id, $this->tenantAdmin->id]), $payload);

        $response->assertSessionHasErrors(['email']);
    }
}
