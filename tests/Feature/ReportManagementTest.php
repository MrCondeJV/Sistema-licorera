<?php

namespace Tests\Feature;

use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SaleService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportManagementTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected User $waiter;

    protected function setUp(): void
    {
        parent::setUp();

        $plan = Plan::create([
            'name' => 'Plan Starter',
            'slug' => 'starter',
            'price_monthly' => 79000,
            'max_users' => 5,
            'max_branches' => 1,
            'max_products' => 500,
            'is_active' => true,
        ]);

        $this->tenant = Tenant::create([
            'name' => 'Licorera Reportes Test',
            'slug' => 'licorera-reportes',
            'plan_id' => $plan->id,
            'status' => 'active',
        ]);

        app(TenantContext::class)->setTenant($this->tenant);

        $this->admin = User::create([
            'name' => 'Admin Reportes',
            'email' => 'admin@reportestest.com',
            'password' => bcrypt('Admin2026*'),
            'pin_hash' => bcrypt('1234'),
            'is_superadmin' => false,
            'is_active' => true,
        ]);
        $this->tenant->users()->attach($this->admin->id, ['role' => 'admin', 'is_active' => true]);

        $this->waiter = User::create([
            'name' => 'Waiter Reportes',
            'email' => 'waiter@reportestest.com',
            'password' => bcrypt('Waiter2026*'),
            'pin_hash' => bcrypt('2345'),
            'is_superadmin' => false,
            'is_active' => true,
        ]);
        $this->tenant->users()->attach($this->waiter->id, ['role' => 'waiter', 'is_active' => true]);

        $category = Category::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Varios',
            'slug' => 'varios',
        ]);

        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'name' => 'Ron Medellín 750ml',
            'sku' => 'RON-MED-750',
            'stock' => 50,
            'price_buy' => 25000,
            'price_sell' => 45000,
            'is_liquor' => true,
            'is_active' => true,
        ]);

        $cashRegister = CashRegister::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Caja 1',
            'is_active' => true,
        ]);

        $cashSession = CashSession::create([
            'tenant_id' => $this->tenant->id,
            'cash_register_id' => $cashRegister->id,
            'user_id' => $this->admin->id,
            'opening_amount' => 50000,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $cashMethod = PaymentMethod::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Efectivo',
            'code' => 'cash',
            'is_cash' => true,
            'is_active' => true,
        ]);

        // Generate 1 completed sale
        app(SaleService::class)->createSale(
            [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            [
                ['payment_method_id' => $cashMethod->id, 'amount' => 45000],
            ],
            $cashSession,
            $this->admin
        );
    }

    public function test_admin_can_view_reports_and_filter(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['pin_authenticated' => true, 'active_tenant_id' => $this->tenant->id])
            ->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertSee('Reportes de Ventas y Rentabilidad');
        $response->assertSee('Exportar PDF');
        $response->assertSee('Exportar Excel');
    }

    public function test_admin_can_export_pdf_report(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['pin_authenticated' => true, 'active_tenant_id' => $this->tenant->id])
            ->get(route('reports.pdf'));

        $response->assertStatus(200);
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_admin_can_export_excel_csv_report(): void
    {
        $response = $this->actingAs($this->admin)
            ->withSession(['pin_authenticated' => true, 'active_tenant_id' => $this->tenant->id])
            ->get(route('reports.excel'));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_waiter_cannot_access_reports(): void
    {
        $response = $this->actingAs($this->waiter)
            ->withSession(['pin_authenticated' => true, 'active_tenant_id' => $this->tenant->id])
            ->get(route('reports.index'));

        // Waiter role is redirected or forbidden (role:admin,cashier)
        $response->assertStatus(403);
    }
}
