<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    protected function setupTenantAndUsers()
    {
        $tenant = Tenant::create(['name' => 'Licorera La 70', 'slug' => 'licorera-la-70', 'status' => 'active']);

        $admin = User::create([
            'name' => 'Admin Juan',
            'email' => 'admin@la70.com',
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

        $cashier = User::create([
            'name' => 'Cajero Carlos',
            'email' => 'cajero@la70.com',
            'password' => Hash::make('Cajero1234*'),
            'pin_hash' => Hash::make('2222'),
            'is_superadmin' => false,
            'is_active' => true,
        ]);
        TenantUser::create([
            'tenant_id' => $tenant->id,
            'user_id' => $cashier->id,
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $waiter = User::create([
            'name' => 'Mesero Pedro',
            'email' => 'mesero@la70.com',
            'password' => Hash::make('Mesero1234*'),
            'pin_hash' => Hash::make('3333'),
            'is_superadmin' => false,
            'is_active' => true,
        ]);
        TenantUser::create([
            'tenant_id' => $tenant->id,
            'user_id' => $waiter->id,
            'role' => 'waiter',
            'is_active' => true,
        ]);

        return [$tenant, $admin, $cashier, $waiter];
    }

    public function test_cashier_product_creation_generates_audit_log(): void
    {
        [$tenant, $admin, $cashier] = $this->setupTenantAndUsers();
        $category = Category::create(['tenant_id' => $tenant->id, 'name' => 'Cervezas', 'slug' => 'cervezas', 'is_active' => true]);

        $this->actingAs($cashier);
        session(['active_tenant_id' => $tenant->id]);

        $response = $this->post(route('inventory.store'), [
            'name' => 'Cerveza Club Colombia 330ml',
            'category_id' => $category->id,
            'sku' => 'CERV-CLUB-01',
            'price_buy' => 3000,
            'price_sell' => 5000,
            'stock' => 24,
            'stock_min' => 6,
            'base_unit' => 'unidad',
        ]);

        $response->assertRedirect(route('inventory.index'));

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'user_id' => $cashier->id,
            'action' => 'product_created',
        ]);

        $log = AuditLog::where('action', 'product_created')->first();
        $this->assertNotNull($log);
        $this->assertEquals('Cerveza Club Colombia 330ml', $log->new_values['name']);
        $this->assertEquals(5000, $log->new_values['price_sell']);
        $this->assertStringContainsString('Cajero Carlos', $log->reason);
    }

    public function test_product_update_generates_audit_log_with_diff(): void
    {
        [$tenant, $admin, $cashier] = $this->setupTenantAndUsers();
        $category = Category::create(['tenant_id' => $tenant->id, 'name' => 'Licores', 'slug' => 'licores', 'is_active' => true]);
        $product = Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'name' => 'Aguardiente Antioqueño 750ml',
            'sku' => 'AGU-ANT-750',
            'price_buy' => 35000,
            'price_sell' => 50000,
            'stock' => 10,
            'stock_min' => 2,
            'base_unit' => 'botella',
            'is_active' => true,
        ]);

        $this->actingAs($cashier);
        session(['active_tenant_id' => $tenant->id]);

        $response = $this->put(route('inventory.update', $product->id), [
            'name' => 'Aguardiente Antioqueño 750ml (Azul)',
            'category_id' => $category->id,
            'sku' => 'AGU-ANT-750',
            'price_buy' => 36000,
            'price_sell' => 55000,
            'stock_min' => 4,
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('inventory.index'));

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'user_id' => $cashier->id,
            'action' => 'product_updated',
        ]);

        $log = AuditLog::where('action', 'product_updated')->latest('id')->first();
        $this->assertEquals(50000, $log->old_values['price_sell']);
        $this->assertEquals(55000, $log->new_values['price_sell']);
        $this->assertStringContainsString('price_sell', $log->reason);

        // Verify formatted changes accessor
        $changes = $log->formatted_changes;
        $this->assertNotEmpty($changes);
        $fields = array_column($changes, 'field');
        $this->assertContains('Precio Venta ($)', $fields);
    }

    public function test_quick_stock_adjust_generates_audit_log(): void
    {
        [$tenant, $admin, $cashier] = $this->setupTenantAndUsers();
        $category = Category::create(['tenant_id' => $tenant->id, 'name' => 'Rones', 'slug' => 'rones', 'is_active' => true]);
        $product = Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'name' => 'Ron Medellín 8 Años 750ml',
            'sku' => 'RON-MED-8A',
            'price_buy' => 45000,
            'price_sell' => 70000,
            'stock' => 15,
            'stock_min' => 3,
            'base_unit' => 'botella',
            'is_active' => true,
        ]);

        $this->actingAs($cashier);
        session(['active_tenant_id' => $tenant->id]);

        $response = $this->post(route('inventory.adjust', $product->id), [
            'new_stock' => 12,
            'reason' => 'Merma por botella rota en mostrador',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'user_id' => $cashier->id,
            'action' => 'stock_adjusted',
        ]);
    }

    public function test_expense_creation_generates_audit_log(): void
    {
        [$tenant, $admin, $cashier] = $this->setupTenantAndUsers();
        $cat = ExpenseCategory::create(['tenant_id' => $tenant->id, 'name' => 'Servicios Públicos', 'is_active' => true]);

        $this->actingAs($cashier);
        session(['active_tenant_id' => $tenant->id]);

        $response = $this->post(route('expenses.store'), [
            'expense_category_id' => $cat->id,
            'amount' => 45000,
            'payment_method' => 'cash',
            'description' => 'Pago de factura de energía EPM',
            'expense_date' => now()->format('Y-m-d'),
        ]);

        $response->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenant->id,
            'user_id' => $cashier->id,
            'action' => 'expense_created',
        ]);
    }

    public function test_admin_can_view_audit_dashboard_and_filter_by_cashier(): void
    {
        [$tenant, $admin, $cashier] = $this->setupTenantAndUsers();

        AuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $cashier->id,
            'action' => 'product_created',
            'new_values' => ['name' => 'Tequila Don Julio'],
            'reason' => 'Creación por Cajero',
            'created_at' => now(),
        ]);

        $this->actingAs($admin);
        session(['active_tenant_id' => $tenant->id]);

        $response = $this->get(route('audit.index'));
        $response->assertStatus(200);
        $response->assertSee('Auditoría y Trazabilidad Operacional');
        $response->assertSee('Cajero Carlos');
        $response->assertSee('Producto Creado');

        // Test filter by cashier
        $filteredResponse = $this->get(route('audit.index', ['user_id' => $cashier->id]));
        $filteredResponse->assertStatus(200);
        $filteredResponse->assertSee('Cajero Carlos');

        // Test filter by category
        $catResponse = $this->get(route('audit.index', ['category' => 'inventory']));
        $catResponse->assertStatus(200);
        $catResponse->assertSee('Producto Creado');
    }

    public function test_waiter_is_forbidden_from_audit_module(): void
    {
        [$tenant, $admin, $cashier, $waiter] = $this->setupTenantAndUsers();

        $this->actingAs($waiter);
        session(['active_tenant_id' => $tenant->id]);

        $response = $this->get(route('audit.index'));
        $response->assertStatus(403);
    }
}
