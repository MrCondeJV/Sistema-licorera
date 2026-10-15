<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerDebt;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SaleService;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerDebtItemDetailsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private Branch $branch;
    private User $admin;
    private Customer $customer;
    private Product $beer;
    private Product $rum;
    private CashSession $cashSession;

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

        $this->tenant = Tenant::create([
            'name' => 'Licorera Medellín',
            'slug' => 'licorera-medellin',
            'email' => 'medellin@licores.com',
            'status' => 'active',
            'plan_id' => $plan->id,
            'plan_expires_at' => now()->addMonth(),
        ]);

        $this->branch = Branch::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Sede Poblado',
            'address' => 'Cra 43A #10-20',
            'is_main' => true,
            'is_active' => true,
        ]);

        $this->admin = User::create([
            'name' => 'Admin Poblado',
            'email' => 'admin@poblado.com',
            'password' => Hash::make('Secret123!'),
            'pin_hash' => Hash::make('1234'),
            'is_superadmin' => false,
            'is_active' => true,
        ]);

        $this->tenant->users()->attach($this->admin->id, ['role' => 'admin', 'is_active' => true]);
        $this->branch->users()->attach($this->admin->id, ['tenant_id' => $this->tenant->id]);

        $category = Category::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Licores y Cervezas',
            'slug' => 'licores-y-cervezas',
        ]);

        $this->beer = Product::create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'name' => 'Cerveza Corona 330ml',
            'sku' => 'COR-330',
            'price_buy' => 4000,
            'price_sell' => 7000,
            'stock' => 50,
            'is_active' => true,
            'is_beer' => true,
            'has_iva' => false,
        ]);

        $this->rum = Product::create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'name' => 'Ron Medellín 750ml',
            'sku' => 'RON-750',
            'price_buy' => 35000,
            'price_sell' => 60000,
            'stock' => 20,
            'is_active' => true,
            'is_liquor' => true,
            'has_iva' => false,
        ]);

        $this->customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Mauricio Morales',
            'phone' => '3001234567',
            'credit_limit' => 500000,
            'current_debt' => 0,
            'is_active' => true,
        ]);

        $register = CashRegister::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'name' => 'Caja Principal 01',
            'is_active' => true,
        ]);

        // Open cash session
        $this->cashSession = CashSession::create([
            'tenant_id' => $this->tenant->id,
            'cash_register_id' => $register->id,
            'user_id' => $this->admin->id,
            'opening_amount' => 100000,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        app(TenantContext::class)->setTenant($this->tenant);
    }

    public function test_pos_sale_charged_to_debt_records_items_snapshot_and_itemized_notes(): void
    {
        $saleService = app(SaleService::class);

        $items = [
            [
                'product_id' => $this->beer->id,
                'quantity' => 4,
            ],
            [
                'product_id' => $this->rum->id,
                'quantity' => 1,
            ],
        ];

        // Total: 4 * 7000 + 1 * 60000 = 28000 + 60000 = 88000
        $sale = $saleService->createSale(
            $items,
            [], // No direct payment
            $this->cashSession,
            $this->admin,
            null,
            $this->customer->id,
            0,
            true // charge_to_debt
        );

        $debt = CustomerDebt::where('customer_id', $this->customer->id)
            ->where('sale_id', $sale->id)
            ->first();

        $this->assertNotNull($debt);
        $this->assertEquals(88000, (float)$debt->original_amount);
        $this->assertEquals(88000, (float)$debt->remaining_amount);

        // Verify items_snapshot
        $this->assertIsArray($debt->items_snapshot);
        $this->assertCount(2, $debt->items_snapshot);
        $this->assertEquals('Cerveza Corona 330ml', $debt->items_snapshot[0]['product_name']);
        $this->assertEquals(4, $debt->items_snapshot[0]['quantity']);
        $this->assertEquals(28000, $debt->items_snapshot[0]['subtotal']);

        $this->assertEquals('Ron Medellín 750ml', $debt->items_snapshot[1]['product_name']);
        $this->assertEquals(1, $debt->items_snapshot[1]['quantity']);
        $this->assertEquals(60000, $debt->items_snapshot[1]['subtotal']);

        // Verify human-readable notes summary
        $this->assertStringContainsString('4x Cerveza Corona 330ml', $debt->notes);
        $this->assertStringContainsString('1x Ron Medellín 750ml', $debt->notes);

        // Verify accessors
        $this->assertCount(2, $debt->items_breakdown);
        $this->assertStringContainsString('4x Cerveza Corona 330ml', $debt->items_summary_text);
        $this->assertStringContainsString('1x Ron Medellín 750ml', $debt->items_summary_text);

        // Verify customer balance
        $this->assertEquals(88000, (float)$this->customer->fresh()->current_debt);
    }

    public function test_customer_debts_view_displays_item_breakdown_and_sale_reference(): void
    {
        $saleService = app(SaleService::class);
        $sale = $saleService->createSale(
            [['product_id' => $this->beer->id, 'quantity' => 2]],
            [],
            $this->cashSession,
            $this->admin,
            null,
            $this->customer->id,
            0,
            true
        );

        $response = $this->actingAs($this->admin)
            ->withSession([
                'active_tenant_id' => $this->tenant->id,
                'active_branch_id' => $this->branch->id,
                'pin_authenticated' => true,
            ])
            ->get(route('customers.debts', $this->customer->id));

        $response->assertStatus(200);
        $response->assertSee('Historial de Fiados / Deudas');
        $response->assertSee('Cerveza Corona 330ml');
        $response->assertSee('Venta #' . $sale->sale_number);
        $response->assertSee('Ver Detalle');
    }

    public function test_manual_debt_creation_with_catalog_items_calculates_total_and_deducts_stock(): void
    {
        $stockBefore = (float)$this->beer->fresh()->stock;

        $payload = [
            'deduct_stock' => '1',
            'notes' => 'Entrega especial en la barra',
            'items' => [
                [
                    'product_id' => $this->beer->id,
                    'name' => $this->beer->name,
                    'quantity' => 3,
                    'unit_price' => 7000,
                    'subtotal' => 21000,
                ],
            ],
        ];

        $response = $this->actingAs($this->admin)
            ->withSession([
                'active_tenant_id' => $this->tenant->id,
                'active_branch_id' => $this->branch->id,
                'pin_authenticated' => true,
            ])
            ->post(route('customers.debts.store', $this->customer->id), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $debt = CustomerDebt::where('customer_id', $this->customer->id)->latest()->first();
        $this->assertNotNull($debt);
        $this->assertEquals(21000, (float)$debt->original_amount);
        $this->assertIsArray($debt->items_snapshot);
        $this->assertEquals(3, $debt->items_snapshot[0]['quantity']);
        $this->assertEquals('Cerveza Corona 330ml', $debt->items_snapshot[0]['product_name']);
        $this->assertStringContainsString('3x Cerveza Corona 330ml', $debt->notes);

        // Verify stock was deducted by 3
        $stockAfter = (float)$this->beer->fresh()->stock;
        $this->assertEquals($stockBefore - 3, $stockAfter);
    }

    public function test_backward_compatibility_existing_debt_with_sale_id_resolves_items_from_sale(): void
    {
        // Simulate a legacy debt where items_snapshot is null, but sale_id exists
        $sale = Sale::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'user_id' => $this->admin->id,
            'cash_session_id' => $this->cashSession->id,
            'customer_id' => $this->customer->id,
            'sale_number' => 'VTA-LEGACY-001',
            'status' => 'completed',
            'subtotal' => 60000,
            'discount_total' => 0,
            'tax_total' => 0,
            'total' => 60000,
            'sold_at' => now(),
        ]);

        SaleItem::create([
            'tenant_id' => $this->tenant->id,
            'sale_id' => $sale->id,
            'product_id' => $this->rum->id,
            'product_name_snapshot' => 'Ron Medellín 750ml',
            'presentation_name_snapshot' => 'Botella',
            'quantity' => 1,
            'conversion_factor_snapshot' => 1,
            'unit_cost_snapshot' => 35000,
            'unit_price' => 60000,
            'subtotal' => 60000,
            'tax_amount' => 0,
        ]);

        $legacyDebt = CustomerDebt::create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'sale_id' => $sale->id,
            'user_id' => $this->admin->id,
            'original_amount' => 60000,
            'remaining_amount' => 60000,
            'status' => 'pending',
            'notes' => 'Venta VTA-LEGACY-001 cargada a cuenta',
            'items_snapshot' => null, // Legacy: null
            'due_date' => now()->addDays(30),
        ]);

        // The accessor must resolve the items from $sale->items
        $this->assertCount(1, $legacyDebt->items_breakdown);
        $this->assertEquals('Ron Medellín 750ml', $legacyDebt->items_breakdown[0]['product_name']);
        $this->assertEquals('Botella', $legacyDebt->items_breakdown[0]['presentation_name']);
        $this->assertEquals(60000, $legacyDebt->items_breakdown[0]['subtotal']);
        $this->assertStringContainsString('1x Ron Medellín 750ml', $legacyDebt->items_summary_text);
    }
}
