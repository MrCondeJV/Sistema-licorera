<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockTransfer;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\CashRegisterService;
use App\Services\InventoryService;
use App\Services\SaleService;
use App\Services\TenantContext;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MultiTenantMultiBranchArchitectureTest extends TestCase
{
    use RefreshDatabase;

    private function createTenantWithPlan(string $name, string $slug, int $maxBranches = 2): array
    {
        $plan = Plan::create([
            'name' => "Plan {$name}",
            'slug' => "plan-{$slug}",
            'price_monthly' => 99000,
            'max_users' => 10,
            'max_branches' => $maxBranches,
            'max_products' => 1000,
            'is_active' => true,
        ]);

        $tenant = Tenant::create([
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
            'plan_id' => $plan->id,
            'subscription_status' => 'active',
            'subscription_ends_at' => now()->addDays(30),
        ]);

        $mainBranch = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => "{$name} - Principal",
            'code' => 'MAIN',
            'address' => 'Calle 10 # 40-20',
            'is_main' => true,
            'is_active' => true,
        ]);

        $admin = User::create([
            'name' => "Admin {$name}",
            'email' => "admin@{$slug}.com",
            'password' => Hash::make('password123'),
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

        $admin->branches()->attach($mainBranch->id, ['tenant_id' => $tenant->id]);

        PaymentMethod::create([
            'tenant_id' => $tenant->id,
            'name' => 'Efectivo',
            'code' => 'cash',
            'is_cash' => true,
            'is_active' => true,
        ]);

        $register = CashRegister::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $mainBranch->id,
            'name' => "Caja 1 - {$mainBranch->name}",
            'is_active' => true,
        ]);

        return [
            'tenant' => $tenant,
            'plan' => $plan,
            'mainBranch' => $mainBranch,
            'admin' => $admin,
            'register' => $register,
        ];
    }

    // =========================================================================
    // 1. TENANT & BRANCH RELATIONSHIPS & ISOLATION
    // =========================================================================
    public function test_tenant_has_many_branches_and_cannot_access_other_tenant_branches(): void
    {
        $dataA = $this->createTenantWithPlan('Licorera Centro', 'centro', 3);
        $dataB = $this->createTenantWithPlan('Licorera Norte', 'norte', 2);

        $this->assertEquals(1, $dataA['tenant']->branches()->count());
        $this->assertEquals($dataA['tenant']->id, $dataA['mainBranch']->tenant_id);

        // Tenant A admin cannot switch to or access Tenant B branch
        $response = $this->actingAs($dataA['admin'])
            ->withSession([
                'active_tenant_id' => $dataA['tenant']->id,
                'active_branch_id' => $dataA['mainBranch']->id,
                'screen_locked' => false,
            ])
            ->post(route('branch.switch', $dataB['mainBranch']));

        $response->assertStatus(403);
        $this->assertEquals($dataA['mainBranch']->id, session('active_branch_id'));
    }

    // =========================================================================
    // 2. PLAN LIMITS ENFORCEMENT & CONCURRENCY RACE CONDITION PREVENTION
    // =========================================================================
    public function test_cannot_exceed_plan_max_branches_limit(): void
    {
        // Tenant with limit of 2 branches (already has 1 main branch)
        $data = $this->createTenantWithPlan('Licorera Bar', 'bar', 2);
        $tenant = $data['tenant'];
        $admin = $data['admin'];

        // Branch 2: should be allowed (1 -> 2)
        $response1 = $this->actingAs($admin)
            ->withSession([
                'active_tenant_id' => $tenant->id,
                'active_branch_id' => $data['mainBranch']->id,
                'screen_locked' => false,
            ])
            ->post(route('branches.store'), [
                'name' => 'Sede Poblado',
                'code' => 'POB',
                'address' => 'Cra 43A # 1-50',
            ]);

        $response1->assertRedirect(route('branches.index'));
        $response1->assertSessionHas('success');
        $this->assertEquals(2, $tenant->branches()->count());

        // Branch 3: must be strictly rejected (limit is 2)
        $response2 = $this->actingAs($admin)
            ->withSession([
                'active_tenant_id' => $tenant->id,
                'active_branch_id' => $data['mainBranch']->id,
                'screen_locked' => false,
            ])
            ->post(route('branches.store'), [
                'name' => 'Sede Laureles',
                'code' => 'LAUR',
                'address' => 'Circular 4 # 70-10',
            ]);

        $response2->assertSessionHas('error');
        $this->assertEquals(2, $tenant->branches()->count());

        // Upgrade plan to 5 branches
        $tenant->plan->update(['max_branches' => 5]);

        // Branch 3: now allowed
        $response3 = $this->actingAs($admin)
            ->withSession([
                'active_tenant_id' => $tenant->id,
                'active_branch_id' => $data['mainBranch']->id,
                'screen_locked' => false,
            ])
            ->post(route('branches.store'), [
                'name' => 'Sede Laureles',
                'code' => 'LAUR',
                'address' => 'Circular 4 # 70-10',
            ]);

        $response3->assertRedirect(route('branches.index'));
        $this->assertEquals(3, $tenant->branches()->count());
    }

    // =========================================================================
    // 3. USER AUTHORIZATION PER BRANCH
    // =========================================================================
    public function test_cashier_can_only_operate_in_assigned_branch(): void
    {
        $data = $this->createTenantWithPlan('Licorera Club', 'club', 5);
        $tenant = $data['tenant'];
        $branch1 = $data['mainBranch'];

        $branch2 = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Sede Sur',
            'code' => 'SUR',
            'is_main' => false,
            'is_active' => true,
        ]);

        // Create cashier assigned ONLY to Branch 1
        $cashier = User::create([
            'name' => 'Cajero Sede 1',
            'email' => 'cajero1@club.com',
            'password' => Hash::make('password123'),
            'pin_hash' => Hash::make('1111'),
            'is_superadmin' => false,
            'is_active' => true,
        ]);

        TenantUser::create([
            'tenant_id' => $tenant->id,
            'user_id' => $cashier->id,
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $cashier->branches()->attach($branch1->id, ['tenant_id' => $tenant->id]);

        $this->assertTrue($cashier->canAccessBranch($branch1));
        $this->assertFalse($cashier->canAccessBranch($branch2));

        // Cashier attempts to switch to unassigned branch 2 -> 403 Forbidden
        $response = $this->actingAs($cashier)
            ->withSession([
                'active_tenant_id' => $tenant->id,
                'active_branch_id' => $branch1->id,
                'screen_locked' => false,
            ])
            ->post(route('branch.switch', $branch2));

        $response->assertStatus(403);

        // Assign cashier to branch 2 as well
        $cashier->branches()->attach($branch2->id, ['tenant_id' => $tenant->id]);
        $this->assertTrue($cashier->fresh()->canAccessBranch($branch2));

        // Now cashier can switch to branch 2
        $responseSuccess = $this->actingAs($cashier)
            ->withSession([
                'active_tenant_id' => $tenant->id,
                'active_branch_id' => $branch1->id,
                'screen_locked' => false,
            ])
            ->post(route('branch.switch', $branch2));

        $responseSuccess->assertRedirect();
        $this->assertEquals($branch2->id, session('active_branch_id'));
    }

    // =========================================================================
    // 4. BRANCH INDEPENDENT INVENTORY & DEDUCTION ISOLATION
    // =========================================================================
    public function test_branches_have_independent_inventory_and_sale_deducts_only_from_current_branch(): void
    {
        $data = $this->createTenantWithPlan('Licorera Express', 'express', 3);
        $tenant = $data['tenant'];
        $branchA = $data['mainBranch'];

        $branchB = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Sucursal Norte',
            'code' => 'NTE',
            'is_main' => false,
            'is_active' => true,
        ]);

        $cat = Category::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cervezas',
            'slug' => 'cervezas',
            'is_active' => true,
        ]);

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $cat->id,
            'sku' => 'CER-POK-01',
            'name' => 'Cerveza Poker',
            'price_buy' => 2500,
            'price_sell' => 4500,
            'stock' => 0,
            'stock_min' => 5,
            'is_active' => true,
        ]);

        $inventoryService = new InventoryService();

        // Branch A receives 50 units
        $inventoryService->addStock($product, 50, 1, 'in', 'Compra sede A', $data['admin'], null, $branchA->id);

        // Branch B receives 12 units
        $inventoryService->addStock($product, 12, 1, 'in', 'Compra sede B', $data['admin'], null, $branchB->id);

        $this->assertEquals(50, $product->stockInBranch($branchA->id));
        $this->assertEquals(12, $product->stockInBranch($branchB->id));

        // Open cash session in Branch A
        $cashService = new CashRegisterService();
        $sessionA = $cashService->openSession($data['register'], $data['admin'], 100000);

        // Sell 5 units in Branch A
        $saleService = new SaleService($inventoryService);
        app(TenantContext::class)->setTenant($tenant);
        app(TenantContext::class)->setBranch($branchA);
        session(['active_tenant_id' => $tenant->id, 'active_branch_id' => $branchA->id]);

        $cashMethod = PaymentMethod::where('tenant_id', $tenant->id)->where('code', 'cash')->first();

        $sale = $saleService->createSale(
            [
                ['product_id' => $product->id, 'quantity' => 5]
            ],
            [
                ['payment_method_id' => $cashMethod->id, 'amount' => 22500]
            ],
            $sessionA,
            $data['admin']
        );

        $this->assertEquals($branchA->id, $sale->branch_id);
        $this->assertEquals(45, $product->fresh()->stockInBranch($branchA->id), 'Branch A stock reduced from 50 to 45');
        $this->assertEquals(12, $product->fresh()->stockInBranch($branchB->id), 'Branch B stock remained strictly unaffected at 12');
    }

    // =========================================================================
    // 5. ATOMIC INTER-BRANCH STOCK TRANSFER & ROLLBACK ON INSUFFICIENT STOCK
    // =========================================================================
    public function test_inter_branch_stock_transfer_is_atomic_and_rolls_back_on_error(): void
    {
        $data = $this->createTenantWithPlan('Licorera Transfer', 'transfer', 3);
        $tenant = $data['tenant'];
        $branchOrigin = $data['mainBranch'];

        $branchDest = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Sede Destino',
            'code' => 'DEST',
            'is_main' => false,
            'is_active' => true,
        ]);

        $cat = Category::create([
            'tenant_id' => $tenant->id,
            'name' => 'Licores',
            'slug' => 'licores',
            'is_active' => true,
        ]);

        $whisky = Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $cat->id,
            'sku' => 'LIC-BUC-01',
            'name' => "Buchanan's 12 Años",
            'price_buy' => 90000,
            'price_sell' => 160000,
            'stock' => 0,
            'stock_min' => 2,
            'is_liquor' => true,
            'is_active' => true,
        ]);

        $inventoryService = new InventoryService();

        // Seed 10 bottles in origin branch
        $inventoryService->addStock($whisky, 10, 1, 'in', 'Ingreso inicial', $data['admin'], null, $branchOrigin->id);
        $this->assertEquals(10, $whisky->stockInBranch($branchOrigin->id));
        $this->assertEquals(0, $whisky->stockInBranch($branchDest->id));

        // 1. Transfer 3 bottles Origin -> Destination
        $transfer = $inventoryService->transferStock(
            $branchOrigin,
            $branchDest,
            [
                ['product_id' => $whisky->id, 'quantity' => 3]
            ],
            $data['admin'],
            'Traslado de fin de semana'
        );

        $this->assertNotNull($transfer);
        $this->assertEquals('completed', $transfer->status);
        $this->assertEquals(7, $whisky->fresh()->stockInBranch($branchOrigin->id));
        $this->assertEquals(3, $whisky->fresh()->stockInBranch($branchDest->id));

        // Verify Kardex movements for both branches
        $this->assertDatabaseHas('stock_movements', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branchOrigin->id,
            'product_id' => $whisky->id,
            'movement_type' => 'out',
            'quantity' => -3,
            'stock_after' => 7,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'tenant_id' => $tenant->id,
            'branch_id' => $branchDest->id,
            'product_id' => $whisky->id,
            'movement_type' => 'in',
            'quantity' => 3,
            'stock_after' => 3,
        ]);

        // 2. Attempt transfer of 20 bottles when only 7 available -> Rollback expected
        $errorTriggered = false;
        try {
            $inventoryService->transferStock(
                $branchOrigin,
                $branchDest,
                [
                    ['product_id' => $whisky->id, 'quantity' => 20]
                ],
                $data['admin']
            );
        } catch (Exception $e) {
            $errorTriggered = true;
            $this->assertStringContainsString('Stock insuficiente', $e->getMessage());
        }

        $this->assertTrue($errorTriggered);
        $this->assertEquals(7, $whisky->fresh()->stockInBranch($branchOrigin->id), 'No partial deduction on failure');
        $this->assertEquals(3, $whisky->fresh()->stockInBranch($branchDest->id), 'No partial credit on failure');
    }

    // =========================================================================
    // 6. TABLES AND CASH SESSIONS ISOLATION PER BRANCH
    // =========================================================================
    public function test_tables_and_cash_registers_are_isolated_by_branch(): void
    {
        $data = $this->createTenantWithPlan('Licorera Mesas', 'mesas', 3);
        $tenant = $data['tenant'];
        $branch1 = $data['mainBranch'];

        $branch2 = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Sede 2',
            'code' => 'S2',
            'is_main' => false,
            'is_active' => true,
        ]);

        // Create Table 1 in Branch 1 and Table 1 in Branch 2 (same name allowed because different branch)
        $table1Branch1 = Table::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch1->id,
            'name' => 'Mesa 1',
            'status' => 'free',
            'capacity' => 4,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $table1Branch2 = Table::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch2->id,
            'name' => 'Mesa 1',
            'status' => 'free',
            'capacity' => 6,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // View tables while active in Branch 1
        $responseBranch1 = $this->actingAs($data['admin'])
            ->withSession([
                'active_tenant_id' => $tenant->id,
                'active_branch_id' => $branch1->id,
                'screen_locked' => false,
            ])
            ->get(route('tables.index'));

        $responseBranch1->assertStatus(200);
        $tablesLoaded1 = $responseBranch1->viewData('tables');
        $this->assertTrue($tablesLoaded1->contains('id', $table1Branch1->id));
        $this->assertFalse($tablesLoaded1->contains('id', $table1Branch2->id), 'Branch 2 tables must not show in Branch 1');

        // View tables while active in Branch 2
        $responseBranch2 = $this->actingAs($data['admin'])
            ->withSession([
                'active_tenant_id' => $tenant->id,
                'active_branch_id' => $branch2->id,
                'screen_locked' => false,
            ])
            ->get(route('tables.index'));

        $responseBranch2->assertStatus(200);
        $tablesLoaded2 = $responseBranch2->viewData('tables');
        $this->assertTrue($tablesLoaded2->contains('id', $table1Branch2->id));
        $this->assertFalse($tablesLoaded2->contains('id', $table1Branch1->id), 'Branch 1 tables must not show in Branch 2');
    }

    // =========================================================================
    // 7. DEACTIVATED BRANCH RESTRICTIONS & NO PHYSICAL DELETE
    // =========================================================================
    public function test_deactivated_branch_preserves_history_and_cannot_operate(): void
    {
        $data = $this->createTenantWithPlan('Licorera Segura', 'segura', 3);
        $tenant = $data['tenant'];
        $admin = $data['admin'];

        $subBranch = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Sede Temporal',
            'code' => 'TEMP',
            'is_main' => false,
            'is_active' => true,
        ]);

        // Toggle to inactive
        $responseToggle = $this->actingAs($admin)
            ->withSession([
                'active_tenant_id' => $tenant->id,
                'active_branch_id' => $data['mainBranch']->id,
                'screen_locked' => false,
            ])
            ->post(route('branches.toggle', $subBranch));

        $responseToggle->assertRedirect();
        $this->assertFalse($subBranch->fresh()->is_active);

        // Attempting to switch to deactivated branch produces 403
        $responseSwitch = $this->actingAs($admin)
            ->withSession([
                'active_tenant_id' => $tenant->id,
                'active_branch_id' => $data['mainBranch']->id,
                'screen_locked' => false,
            ])
            ->post(route('branch.switch', $subBranch));

        $responseSwitch->assertStatus(403);
    }
}
