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

    public function test_screen_unlock_with_password(): void
    {
        $tenantService = new TenantService();
        $res = $tenantService->registerBusiness([
            'business_name' => 'Licorera La 80',
            'admin_name' => 'Admin La 80',
            'email' => 'la80@test.com',
            'password' => 'Secreto123*',
            'pin' => '4321',
        ]);
        $user = $res['user'];
        $tenant = $res['tenant'];

        $this->actingAs($user);
        session(['screen_locked' => true, 'active_tenant_id' => $tenant->id]);

        $response = $this->post(route('unlock.submit'), [
            'password' => 'Secreto123*',
        ]);

        $response->assertRedirect(route('pos.index'));
        $this->assertFalse(session('screen_locked'));
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
