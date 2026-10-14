<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setupTenantAndAdmin()
    {
        $tenant = Tenant::create(['name' => 'Licorera Los Alpes', 'slug' => 'los-alpes', 'status' => 'active']);
        $admin = User::create([
            'name' => 'Admin Alpes',
            'email' => 'admin@alpes.com',
            'password' => Hash::make('Admin1234*'),
            'pin_hash' => Hash::make('1234'),
            'is_superadmin' => false,
            'is_active' => true,
        ]);
        TenantUser::create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin);
        session(['active_tenant_id' => $tenant->id]);

        return [$tenant, $admin];
    }

    public function test_admin_can_view_suppliers_list_and_create_supplier(): void
    {
        [$tenant] = $this->setupTenantAndAdmin();

        $response = $this->get(route('suppliers.index'));
        $response->assertStatus(200);
        $response->assertSee('Proveedores y Distribuidores');

        $storeResponse = $this->post(route('suppliers.store'), [
            'name' => 'Distribuidora Central S.A.S.',
            'business_name' => 'Distribuidora Central de Bebidas',
            'tax_id' => '900.888.777-1',
            'phone' => '3109998877',
            'email' => 'ventas@central.com',
            'address' => 'Calle 10 # 40-50',
            'contact_person' => 'Pedro Asesor',
        ]);

        $storeResponse->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', [
            'tenant_id' => $tenant->id,
            'name' => 'Distribuidora Central S.A.S.',
            'tax_id' => '900.888.777-1',
        ]);
    }

    public function test_quick_supplier_creation_returns_json(): void
    {
        [$tenant] = $this->setupTenantAndAdmin();

        $response = $this->postJson(route('suppliers.quick'), [
            'name' => 'Bavaria Express',
            'tax_id' => '860.005.224-6',
            'phone' => '3001234567',
            'contact_person' => 'Carlos Bavaria',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'supplier' => [
                'name' => 'Bavaria Express',
                'tax_id' => '860.005.224-6',
            ],
        ]);

        $this->assertDatabaseHas('suppliers', [
            'tenant_id' => $tenant->id,
            'name' => 'Bavaria Express',
        ]);
    }

    public function test_admin_can_update_and_toggle_supplier(): void
    {
        [$tenant] = $this->setupTenantAndAdmin();

        $supplier = Supplier::create([
            'tenant_id' => $tenant->id,
            'name' => 'Antioquia Licores',
            'phone' => '3201112233',
            'is_active' => true,
        ]);

        $updateResponse = $this->put(route('suppliers.update', $supplier->id), [
            'name' => 'Antioquia Licores y Vinos',
            'phone' => '3209990000',
        ]);

        $updateResponse->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Antioquia Licores y Vinos',
            'phone' => '3209990000',
        ]);

        $toggleResponse = $this->post(route('suppliers.toggle', $supplier->id));
        $toggleResponse->assertRedirect();
        $this->assertFalse($supplier->fresh()->is_active);
    }
}
