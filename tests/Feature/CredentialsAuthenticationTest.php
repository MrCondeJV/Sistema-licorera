<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CredentialsAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_login_with_email_and_password_directly(): void
    {
        $superadmin = User::create([
            'name' => 'Super Administrador',
            'email' => 'superadmin@licorerapos.com',
            'password' => Hash::make('Admin1234*'),
            'pin_hash' => Hash::make('9999'),
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        $response = $this->post(route('login.submit'), [
            'email' => 'superadmin@licorerapos.com',
            'password' => 'Admin1234*',
        ]);

        $response->assertRedirect(route('superadmin.index'));
        $this->assertAuthenticatedAs($superadmin);
    }

    public function test_tenant_admin_can_login_with_email_and_password(): void
    {
        $tenantService = new TenantService();
        $res = $tenantService->registerBusiness([
            'business_name' => 'Licorera La 70',
            'admin_name' => 'Carlos Admin',
            'email' => 'admin@la70.com',
            'password' => 'Secreto123*',
            'pin' => '1234',
        ]);

        $user = $res['user'];
        $tenant = $res['tenant'];

        $response = $this->post(route('login.submit'), [
            'email' => 'admin@la70.com',
            'password' => 'Secreto123*',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertEquals($tenant->id, session('active_tenant_id'));
    }

    public function test_cannot_login_with_invalid_password(): void
    {
        User::create([
            'name' => 'Usuario Test',
            'email' => 'test@licorerapos.com',
            'password' => Hash::make('Correcto123*'),
            'pin_hash' => Hash::make('1234'),
            'is_superadmin' => false,
            'is_active' => true,
        ]);

        $response = $this->from(route('login'))->post(route('login.submit'), [
            'email' => 'test@licorerapos.com',
            'password' => 'ClaveErronea*',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
