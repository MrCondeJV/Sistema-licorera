<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Customer;
use App\Models\CustomerDebt;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CashRegisterService;
use App\Services\InventoryService;
use App\Services\PurchaseService;
use App\Services\SaleService;
use App\Services\TableAccountService;
use App\Services\TenantContext;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FullSoftwareLifecycleSimulationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;
    protected Plan $proPlan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->proPlan = Plan::create([
            'name' => 'Plan Licorera Pro',
            'slug' => 'pro',
            'price_monthly' => 129000,
            'max_users' => 15,
            'max_branches' => 3,
            'max_products' => 2000,
            'is_active' => true,
        ]);

        $this->superadmin = User::create([
            'name' => 'Super Administrador SaaS',
            'email' => 'superadmin@poslicorera.com',
            'pin_hash' => bcrypt('9999'),
            'password' => bcrypt('Super2026*'),
            'is_superadmin' => true,
            'is_active' => true,
        ]);
    }

    public function test_complete_software_lifecycle_simulation(): void
    {
        $tenantService = app(TenantService::class);
        $purchaseService = app(PurchaseService::class);
        $cashRegisterService = app(CashRegisterService::class);
        $tableAccountService = app(TableAccountService::class);
        $saleService = app(SaleService::class);

        // =========================================================================
        // STEP 1: Superadmin Provisions a New Tenant (Licorera Los Sauces)
        // =========================================================================
        $provisionResult = $tenantService->registerBusiness([
            'business_name' => 'Licorera Los Sauces',
            'admin_name' => 'Carlos Administrador',
            'email' => 'carlos@lossauces.com',
            'phone' => '3001234567',
            'tax_id' => '900.888.777-1',
            'address' => 'Carrera 15 # 45-20, Bogotá',
            'password' => 'Admin2026*',
            'plan_slug' => 'pro',
        ]);

        /** @var Tenant $tenant */
        $tenant = $provisionResult['tenant'];
        $this->assertNotNull($tenant);
        $this->assertEquals('Licorera Los Sauces', $tenant->name);
        $this->assertCount(1, $tenant->branches);
        $this->assertCount(14, $tenant->categories);
        $this->assertCount(10, $tenant->tables);

        /** @var User $adminUser */
        $adminUser = $tenant->users()->first();
        $this->assertEquals('carlos@lossauces.com', $adminUser->email);
        $this->assertTrue(Hash::check('Admin2026*', $adminUser->password));

        // Set multi-tenant context for the simulated store
        app(TenantContext::class)->setTenant($tenant);
        $branch = $tenant->branches->first();
        app(TenantContext::class)->setBranch($branch);

        // Verify payment methods seeded for the tenant
        $cashMethod = PaymentMethod::where('tenant_id', $tenant->id)->where('code', 'cash')->first();
        $transferMethod = PaymentMethod::where('tenant_id', $tenant->id)->where('code', 'transfer')->first();
        $this->assertNotNull($cashMethod);
        $this->assertTrue($cashMethod->is_cash);
        $this->assertNotNull($transferMethod);
        $this->assertFalse($transferMethod->is_cash);

        // =========================================================================
        // STEP 2: Admin Creates Collaborators (Cashier and Waiter)
        // =========================================================================
        $cashierUser = User::create([
            'name' => 'Pedro Cajero',
            'email' => 'pedro@lossauces.com',
            'password' => bcrypt('Cajero2026*'),
            'pin_hash' => bcrypt('1111'),
            'is_superadmin' => false,
            'is_active' => true,
        ]);
        $tenant->users()->attach($cashierUser->id, ['role' => 'cashier', 'is_active' => true]);

        $waiterUser = User::create([
            'name' => 'Juan Mesero',
            'email' => 'juan@lossauces.com',
            'password' => bcrypt('Mesero2026*'),
            'pin_hash' => bcrypt('2222'),
            'is_superadmin' => false,
            'is_active' => true,
        ]);
        $tenant->users()->attach($waiterUser->id, ['role' => 'waiter', 'is_active' => true]);

        // Security check: Neither admin nor cashier can see the superadmin
        $visibleMembers = $tenant->users()->where('is_superadmin', false)->get();
        $this->assertCount(3, $visibleMembers); // admin, cashier, waiter
        $this->assertFalse($visibleMembers->contains('id', $this->superadmin->id));

        // =========================================================================
        // STEP 3: Supplier Creation & Stock Purchases (Stock Inflow)
        // =========================================================================
        $supplier = Supplier::create([
            'tenant_id' => $tenant->id,
            'name' => 'Distribuidora Central de Bebidas',
            'tax_id' => '800.555.444-9',
            'contact_person' => 'Marcos Distribuidor',
            'phone' => '3109998877',
            'is_active' => true,
        ]);

        $beerCategory = $tenant->categories()->where('slug', 'cervezas')->first();
        $liquorCategory = $tenant->categories()->where('slug', 'aguardientes')->first();

        // 1. Beer Product: Águila 330ml (Can be sold per unit or Six-pack of 6)
        $beer = Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $beerCategory->id,
            'name' => 'Cerveza Águila 330ml',
            'sku' => 'CERV-AGUILA-330',
            'barcode' => '7702001001',
            'stock' => 0,
            'stock_min' => 12,
            'price_buy' => 2200,
            'price_sell' => 3500,
            'base_unit' => 'Botella',
            'is_liquor' => false,
            'is_active' => true,
        ]);

        $sixPackPresentation = ProductPresentation::create([
            'tenant_id' => $tenant->id,
            'product_id' => $beer->id,
            'name' => 'Six-Pack (6 Unidades)',
            'barcode' => '7702001006',
            'conversion_factor' => 6,
            'sale_price' => 20000, // Saves $1,000 vs individual units
            'is_active' => true,
        ]);

        // 2. Liquor Product: Aguardiente Antioqueño 750ml (Full bottle only)
        $aguardiente = Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $liquorCategory->id,
            'name' => 'Aguardiente Antioqueño 750ml',
            'sku' => 'AGUARD-ANT-750',
            'barcode' => '7701002001',
            'stock' => 0,
            'stock_min' => 5,
            'price_buy' => 32000,
            'price_sell' => 48000,
            'base_unit' => 'Botella',
            'is_liquor' => true,
            'is_active' => true,
        ]);

        // Purchase Order: 10 Six-packs (60 units) + 20 bottles of Aguardiente
        $purchase = $purchaseService->createPurchase(
            $supplier->id,
            [
                [
                    'product_id' => $beer->id,
                    'product_presentation_id' => $sixPackPresentation->id,
                    'conversion_factor' => 6,
                    'quantity' => 10, // 10 six-packs = 60 units
                    'unit_cost' => 13200, // $2,200 per bottle * 6
                ],
                [
                    'product_id' => $aguardiente->id,
                    'product_presentation_id' => null,
                    'conversion_factor' => 1,
                    'quantity' => 20,
                    'unit_cost' => 32000,
                ],
            ],
            $adminUser,
            'FAC-10085',
            'Compra de abastecimiento fin de semana'
        );

        $this->assertEquals('draft', $purchase->status);
        $this->assertEquals(0, $beer->fresh()->stock);

        // Receive the purchase into inventory
        $purchaseService->receivePurchase($purchase, $adminUser);

        $this->assertEquals('received', $purchase->fresh()->status);
        $this->assertEquals(60, $beer->fresh()->stock); // 10 six-packs * 6 = 60 units
        $this->assertEquals(20, $aguardiente->fresh()->stock);

        // Verify Kardex stock movements
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $beer->id,
            'quantity' => 60,
            'stock_after' => 60,
            'movement_type' => 'purchase',
        ]);

        // =========================================================================
        // STEP 4: Cashier Opens Cash Register Shift
        // =========================================================================
        $cashRegister = CashRegister::where('tenant_id', $tenant->id)->first();
        $cashSession = $cashRegisterService->openSession($cashRegister, $cashierUser, 100000); // Base $100.000 COP

        $this->assertTrue($cashSession->isOpen());
        $this->assertEquals(100000, $cashSession->opening_amount);

        // =========================================================================
        // STEP 5: Direct POS Counter Sale (Paid in Cash with Change)
        // =========================================================================
        // Customer buys 1 Six-pack of Beer ($20,000) + 1 Aguardiente ($48,000). Total: $68,000 COP
        $sale1 = $saleService->createSale(
            [
                [
                    'product_id' => $beer->id,
                    'product_presentation_id' => $sixPackPresentation->id,
                    'quantity' => 1,
                ],
                [
                    'product_id' => $aguardiente->id,
                    'product_presentation_id' => null,
                    'quantity' => 1,
                ],
            ],
            [
                [
                    'payment_method_id' => $cashMethod->id,
                    'amount' => 68000,
                    'received_amount' => 100000,
                    'change_amount' => 32000,
                ],
            ],
            $cashSession,
            $cashierUser
        );

        $this->assertEquals('completed', $sale1->status);
        $this->assertEquals(68000, $sale1->total);
        $this->assertEquals(54, $beer->fresh()->stock); // 60 - 6 = 54
        $this->assertEquals(19, $aguardiente->fresh()->stock); // 20 - 1 = 19

        // =========================================================================
        // STEP 6: Table comanda order & Checkout via Bank Transfer
        // =========================================================================
        /** @var Table $table1 */
        $table1 = Table::where('tenant_id', $tenant->id)->where('name', 'M1')->first();
        $account = $tableAccountService->openAccount($table1->id, null, null, $waiterUser);

        $this->assertEquals('occupied', $table1->fresh()->status);
        $this->assertEquals('open', $account->status);

        // Waiter adds 2 Aguardiente bottles to table ($48,000 * 2 = $96,000)
        $tableAccountService->addItem($account, $aguardiente->id, null, 2);
        $this->assertEquals(96000, $account->fresh()->total);

        // Transfer table M1 to table M2
        /** @var Table $table2 */
        $table2 = Table::where('tenant_id', $tenant->id)->where('name', 'M2')->first();
        $tableAccountService->transferAccount($account, $table2);

        $this->assertEquals('free', $table1->fresh()->status);
        $this->assertEquals('occupied', $table2->fresh()->status);
        $this->assertEquals($table2->id, $account->fresh()->table_id);

        // Checkout Table M2 in POS with Bank Transfer (Nequi / Daviplata / Bancolombia)
        $sale2 = $saleService->createSale(
            [
                [
                    'product_id' => $aguardiente->id,
                    'product_presentation_id' => null,
                    'quantity' => 2,
                ],
            ],
            [
                [
                    'payment_method_id' => $transferMethod->id,
                    'amount' => 96000,
                    'reference' => 'NEQUI-VOUCHER-78945',
                ],
            ],
            $cashSession,
            $cashierUser,
            $account->id // Links to table account
        );

        $this->assertEquals('completed', $sale2->status);
        $this->assertEquals('closed', $account->fresh()->status);
        $this->assertEquals('free', $table2->fresh()->status); // Table released!
        $this->assertEquals(17, $aguardiente->fresh()->stock); // 19 - 2 = 17

        // =========================================================================
        // STEP 7: Customer Creation & Sale on Credit / Fiado (Charge to Debt)
        // =========================================================================
        $customer = Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Don Carlos Vecino',
            'phone' => '3157776655',
            'credit_limit' => 150000,
            'current_debt' => 0,
            'is_active' => true,
        ]);

        // Don Carlos buys 1 Six-pack ($20,000) charged to his account (Fiado)
        $sale3 = $saleService->createSale(
            [
                [
                    'product_id' => $beer->id,
                    'product_presentation_id' => $sixPackPresentation->id,
                    'quantity' => 1,
                ],
            ],
            [], // No upfront payment
            $cashSession,
            $cashierUser,
            null,
            $customer->id,
            0,
            true // chargeToDebt = true
        );

        $this->assertEquals('completed', $sale3->status);
        $this->assertEquals(20000, $customer->fresh()->current_debt);
        $this->assertEquals(48, $beer->fresh()->stock); // 54 - 6 = 48

        $debtRecord = CustomerDebt::where('customer_id', $customer->id)->where('sale_id', $sale3->id)->first();
        $this->assertNotNull($debtRecord);
        $this->assertEquals(20000, $debtRecord->remaining_amount);
        $this->assertEquals('pending', $debtRecord->status);

        // =========================================================================
        // STEP 8: Customer Pays Abono in Cash (Cash drawer inflow)
        // =========================================================================
        // Don Carlos makes a partial payment of $15,000 in Cash
        $this->actingAs($cashierUser)
            ->post(route('customers.payments.store', $customer->id), [
                'amount' => 15000,
                'payment_method_id' => $cashMethod->id,
                'notes' => 'Abono en efectivo en mostrador',
            ]);

        $this->assertEquals(5000, $customer->fresh()->current_debt);
        $this->assertEquals(5000, $debtRecord->fresh()->remaining_amount);

        // Verify that CashSession::calculateCashSummary includes this cash abono
        $summary = $cashSession->calculateCashSummary();
        $this->assertEquals(15000, $summary['cash_customer_payments']);

        // =========================================================================
        // STEP 9: Operational Expense in Cash
        // =========================================================================
        $expenseCategory = ExpenseCategory::create([
            'tenant_id' => $tenant->id,
            'name' => 'Insumos de Barra',
        ]);

        Expense::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'expense_category_id' => $expenseCategory->id,
            'cash_session_id' => $cashSession->id,
            'user_id' => $cashierUser->id,
            'amount' => 20000,
            'payment_method' => 'cash',
            'description' => 'Compra de bolsas de hielo y limones',
            'expense_date' => now()->toDateString(),
        ]);

        // =========================================================================
        // STEP 10: Cash Shift Close (Arqueo Ciego) Verification
        // =========================================================================
        // Theoretical cash calculation:
        // Base inicial:            $100.000
        // Ventas en efectivo:      + $68.000
        // Abono cliente en efect.: + $15.000
        // Gastos en efectivo:      - $20.000
        // Total Efectivo Esperado: = $163.000 COP
        //
        // Pagos electrónicos (Banco): $96.000 COP (no entran a la gaveta física)
        $finalSummary = $cashSession->calculateCashSummary();
        $this->assertEquals(100000, $finalSummary['opening_amount']);
        $this->assertEquals(68000, $finalSummary['cash_sales']);
        $this->assertEquals(15000, $finalSummary['cash_customer_payments']);
        $this->assertEquals(20000, $finalSummary['cash_expenses']);
        $this->assertEquals(163000, $finalSummary['expected_cash']);

        // Check electronic payments aggregation
        $this->assertCount(1, $finalSummary['electronic_payments']);
        $this->assertEquals('Transacción Bancaria', $finalSummary['electronic_payments'][0]['method']);
        $this->assertEquals(96000, $finalSummary['electronic_payments'][0]['total']);

        // Cashier performs blind physical count entering $163.000 COP
        $cashRegisterService->closeSession($cashSession, 163000, 'Arqueo perfecto sin descuadres', $cashierUser);

        $this->assertEquals('closed', $cashSession->fresh()->status);
        $this->assertEquals(163000, $cashSession->fresh()->counted_cash);
        $this->assertEquals(0, $cashSession->fresh()->difference); // Cuadre exacto!

        // =========================================================================
        // STEP 11: Final Kardex & Inventory Integrity
        // =========================================================================
        $this->assertEquals(48, $beer->fresh()->stock); // 60 initial - 6 (sale1) - 6 (sale3) = 48
        $this->assertEquals(17, $aguardiente->fresh()->stock); // 20 initial - 1 (sale1) - 2 (sale2) = 17

        $beerMovements = StockMovement::where('product_id', $beer->id)->get();
        $this->assertCount(3, $beerMovements); // 1 purchase (+60), 2 sales (-6, -6)
        $this->assertEquals(48, $beerMovements->last()->stock_after);

        $aguardienteMovements = StockMovement::where('product_id', $aguardiente->id)->get();
        $this->assertCount(3, $aguardienteMovements); // 1 purchase (+20), 2 sales (-1, -2)
        $this->assertEquals(17, $aguardienteMovements->last()->stock_after);
    }
}
