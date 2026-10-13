<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PinAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_pin(): void
    {
        $tenantService = new TenantService();
        $res = $tenantService->registerBusiness([
            'business_name' => 'Licorera La 80',
            'admin_name' => 'Admin La 80',
            'email' => 'la80@test.com',
            'pin' => '4321',
        ]);
        $tenant = $res['tenant'];

        $response = $this->post(route('login.pin'), [
            'tenant_id' => $tenant->id,
            'pin' => '4321',
        ]);

        $response->assertRedirect(route('pos.index'));
        $this->assertAuthenticated();
    }

    public function test_user_cannot_login_with_invalid_pin(): void
    {
        $tenantService = new TenantService();
        $res = $tenantService->registerBusiness([
            'business_name' => 'Licorera Centro',
            'admin_name' => 'Admin Centro',
            'email' => 'centro@test.com',
            'pin' => '1234',
        ]);
        $tenant = $res['tenant'];

        $response = $this->from(route('login'))->post(route('login.pin'), [
            'tenant_id' => $tenant->id,
            'pin' => '9999', // Wrong pin
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('pin');
        $this->assertGuest();
    }

    public function test_waiter_cannot_access_administrative_settings(): void
    {
        $tenantService = new TenantService();
        $res = $tenantService->registerBusiness([
            'business_name' => 'Licorera Norte',
            'admin_name' => 'Admin Norte',
            'email' => 'norte@test.com',
            'pin' => '1111',
        ]);
        $tenant = $res['tenant'];

        // Create Waiter
        $waiter = User::create([
            'name' => 'Mesero Juan',
            'email' => 'juan@test.com',
            'pin_hash' => Hash::make('5555'),
            'is_active' => true,
        ]);
        TenantUser::create([
            'tenant_id' => $tenant->id,
            'user_id' => $waiter->id,
            'role' => 'waiter',
            'is_active' => true,
        ]);

        // Login as Waiter
        $this->actingAs($waiter);
        session(['active_tenant_id' => $tenant->id]);

        // Attempt to access settings route (restricted to admin)
        $response = $this->get(route('settings.index'));
        $response->assertStatus(403);
    }
}
