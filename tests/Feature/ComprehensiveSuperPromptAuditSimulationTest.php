<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountItem;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerDebt;
use App\Models\CustomerPayment;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Refund;
use App\Models\RefundItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CashRegisterService;
use App\Services\InventoryService;
use App\Services\PurchaseService;
use App\Services\ReportService;
use App\Services\SaleService;
use App\Services\TableAccountService;
use App\Services\TenantContext;
use App\Services\TenantService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ComprehensiveSuperPromptAuditSimulationTest extends TestCase
{
    use RefreshDatabase;

    protected Plan $proPlan;
    protected User $superadmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->proPlan = Plan::create([
            'name' => 'Plan Pro (Licorera Estándar)',
            'slug' => 'pro',
            'price_monthly' => 129000,
            'max_users' => 15,
            'max_branches' => 3,
            'max_products' => 2000,
            'is_active' => true,
        ]);

        $this->superadmin = User::create([
            'name' => 'Super Administrador Mambacode',
            'email' => 'superadmin@mambacode.test',
            'phone' => '3001112233',
            'pin_hash' => Hash::make('9999'),
            'password' => Hash::make('Admin1234*'),
            'is_superadmin' => true,
            'is_active' => true,
        ]);
    }

    public function test_super_prompt_full_business_lifecycle_and_multitenant_audit(): void
    {
        $tenantService = app(TenantService::class);
        $purchaseService = app(PurchaseService::class);
        $cashRegisterService = app(CashRegisterService::class);
        $tableAccountService = app(TableAccountService::class);
        $saleService = app(SaleService::class);
        $inventoryService = app(InventoryService::class);
        $reportService = app(ReportService::class);

        // =========================================================================
        // 1. CREACIÓN DE DOS TENANTS INDEPENDIENTES:
        //    TENANT A: "La Noche Centro"
        //    TENANT B: "La Noche Norte"
        // =========================================================================
        $provA = $tenantService->registerBusiness([
            'business_name' => 'La Noche Centro',
            'admin_name' => 'Carlos Administrador',
            'email' => 'admin@lanochecentro.com',
            'phone' => '3001002001',
            'tax_id' => '900.111.222-1',
            'address' => 'Calle 50 # 40-10 Centro',
            'password' => 'Centro2026*',
            'plan_slug' => 'pro',
        ]);
        /** @var Tenant $tenantA */
        $tenantA = $provA['tenant'];
        /** @var User $adminA */
        $adminA = $provA['user'];
        /** @var Branch $branchA */
        $branchA = $provA['branch'];

        $provB = $tenantService->registerBusiness([
            'business_name' => 'La Noche Norte',
            'admin_name' => 'Felipe Administrador',
            'email' => 'admin@lanochenorte.com',
            'phone' => '3001002002',
            'tax_id' => '900.333.444-2',
            'address' => 'Avenida 19 # 100-20 Norte',
            'password' => 'Norte2026*',
            'plan_slug' => 'pro',
        ]);
        /** @var Tenant $tenantB */
        $tenantB = $provB['tenant'];
        /** @var User $adminB */
        $adminB = $provB['user'];
        /** @var Branch $branchB */
        $branchB = $provB['branch'];

        $this->assertNotEquals($tenantA->id, $tenantB->id);

        // =========================================================================
        // 2. CREACIÓN DE USUARIOS PARA TENANT A:
        //    - Administrador (ya creado en provisión)
        //    - Cajero
        //    - Supervisor
        // =========================================================================
        $cajeroA = User::create([
            'name' => 'Cajero Turno Día',
            'email' => 'cajero@lanochecentro.com',
            'phone' => '3002223344',
            'pin_hash' => Hash::make('1234'),
            'password' => Hash::make('Cajero2026*'),
            'is_superadmin' => false,
            'is_active' => true,
        ]);
        TenantUser::create([
            'tenant_id' => $tenantA->id,
            'user_id' => $cajeroA->id,
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $supervisorA = User::create([
            'name' => 'Supervisor Nocturno',
            'email' => 'supervisor@lanochecentro.com',
            'phone' => '3005556677',
            'pin_hash' => Hash::make('5678'),
            'password' => Hash::make('Supervisor2026*'),
            'is_superadmin' => false,
            'is_active' => true,
        ]);
        TenantUser::create([
            'tenant_id' => $tenantA->id,
            'user_id' => $supervisorA->id,
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Establecer contexto para Tenant A
        session(['active_tenant_id' => $tenantA->id, 'active_branch_id' => $branchA->id]);
        app(TenantContext::class)->setTenant($tenantA);
        app(TenantContext::class)->setBranch($branchA);

        // =========================================================================
        // 3. CATEGORÍAS Y PRODUCTOS REALES (CERVEZAS, LICORES, MEZCLADORES, SNACKS)
        // =========================================================================
        $catCervezas = Category::where('tenant_id', $tenantA->id)->where('slug', 'cervezas')->firstOrFail();
        $catLicores = Category::where('tenant_id', $tenantA->id)->where('slug', 'otros-licores')->firstOrFail();
        $catMezcladores = Category::where('tenant_id', $tenantA->id)->where('slug', 'mezcladores')->firstOrFail();
        $catSnacks = Category::where('tenant_id', $tenantA->id)->where('slug', 'snacks')->firstOrFail();

        // 3.1 Cervezas: Águila, Poker, Club Colombia, Corona (unidad = 1, six = 6, caja = 30)
        $cervezaAguila = Product::create([
            'tenant_id' => $tenantA->id,
            'branch_id' => $branchA->id,
            'category_id' => $catCervezas->id,
            'name' => 'Cerveza Águila 330ml',
            'sku' => 'CER-AGU-01',
            'barcode' => '770111100001',
            'unit_type' => 'unit',
            'is_liquor' => false,
            'price_buy' => 3000,
            'price_sell' => 4500,
            'stock' => 0,
            'stock_min' => 10,
            'is_active' => true,
        ]);
        $aguilaSix = ProductPresentation::create([
            'tenant_id' => $tenantA->id,
            'product_id' => $cervezaAguila->id,
            'name' => 'Six Pack Águila',
            'conversion_factor' => 6,
            'cost_price' => 18000,
            'sale_price' => 26000,
            'is_active' => true,
        ]);
        $aguilaCaja = ProductPresentation::create([
            'tenant_id' => $tenantA->id,
            'product_id' => $cervezaAguila->id,
            'name' => 'Caja Águila (30 und)',
            'conversion_factor' => 30,
            'cost_price' => 90000,
            'sale_price' => 125000,
            'is_active' => true,
        ]);

        $cervezaPoker = Product::create([
            'tenant_id' => $tenantA->id,
            'branch_id' => $branchA->id,
            'category_id' => $catCervezas->id,
            'name' => 'Cerveza Poker 330ml',
            'sku' => 'CER-POK-01',
            'barcode' => '770111100002',
            'unit_type' => 'unit',
            'is_liquor' => false,
            'price_buy' => 2833.33,
            'price_sell' => 4500,
            'stock' => 0,
            'stock_min' => 10,
            'is_active' => true,
        ]);
        $pokerCaja = ProductPresentation::create([
            'tenant_id' => $tenantA->id,
            'product_id' => $cervezaPoker->id,
            'name' => 'Caja Poker (30 und)',
            'conversion_factor' => 30,
            'cost_price' => 85000,
            'sale_price' => 125000,
            'is_active' => true,
        ]);

        // 3.2 Licores: Aguardiente Antioqueño y Whisky Buchanan's (is_liquor = true, exclusivamente botella completa)
        $aguardiente = Product::create([
            'tenant_id' => $tenantA->id,
            'branch_id' => $branchA->id,
            'category_id' => $catLicores->id,
            'name' => 'Aguardiente Antioqueño Tapa Azul 750ml',
            'sku' => 'LIC-AGU-01',
            'barcode' => '770222200001',
            'unit_type' => 'bottle',
            'is_liquor' => true,
            'price_buy' => 85000,
            'price_sell' => 110000,
            'stock' => 0,
            'stock_min' => 3,
            'is_active' => true,
        ]);

        $whisky = Product::create([
            'tenant_id' => $tenantA->id,
            'branch_id' => $branchA->id,
            'category_id' => $catLicores->id,
            'name' => "Whisky Buchanan's Deluxe 750ml",
            'sku' => 'LIC-WHI-01',
            'barcode' => '770333300001',
            'unit_type' => 'bottle',
            'is_liquor' => true,
            'price_buy' => 150000,
            'price_sell' => 210000,
            'stock' => 0,
            'stock_min' => 2,
            'is_active' => true,
        ]);

        // 3.3 Mezclador: Coca-Cola
        $cocaCola = Product::create([
            'tenant_id' => $tenantA->id,
            'branch_id' => $branchA->id,
            'category_id' => $catMezcladores->id,
            'name' => 'Coca-Cola 400ml Botella',
            'sku' => 'MEZ-COC-01',
            'barcode' => '770444400001',
            'unit_type' => 'unit',
            'is_liquor' => false,
            'price_buy' => 3000,
            'price_sell' => 5000,
            'stock' => 0,
            'stock_min' => 10,
            'is_active' => true,
        ]);

        // 3.4 Snack: Papas Fritas
        $papas = Product::create([
            'tenant_id' => $tenantA->id,
            'branch_id' => $branchA->id,
            'category_id' => $catSnacks->id,
            'name' => 'Papas Margarita Natural',
            'sku' => 'SNK-PAP-01',
            'barcode' => '770555500001',
            'unit_type' => 'unit',
            'is_liquor' => false,
            'price_buy' => 2500,
            'price_sell' => 4000,
            'stock' => 50,
            'stock_min' => 5,
            'is_active' => true,
        ]);

        // =========================================================================
        // 4. AISLAMIENTO MULTITENANT ESTRICTO (TENANT A vs TENANT B)
        // =========================================================================
        // Crear un producto confidencial en Tenant B
        $catTenantB = Category::withoutGlobalScopes()->where('tenant_id', $tenantB->id)->firstOrFail();
        $productoTenantB = Product::create([
            'tenant_id' => $tenantB->id,
            'branch_id' => $branchB->id,
            'category_id' => $catTenantB->id,
            'name' => 'Ron Exclusivo Norte 750ml',
            'sku' => 'LIC-RON-NORTE',
            'price_buy' => 60000,
            'price_sell' => 95000,
            'stock' => 10,
            'is_active' => true,
        ]);

        // Consulta de Tenant A: No debe ver el producto de Tenant B
        $queryTenantA = Product::where('sku', 'LIC-RON-NORTE')->get();
        $this->assertCount(0, $queryTenantA, 'Tenant A no puede consultar productos de Tenant B debido a TenantScope.');

        // Intentar acceder a producto de Tenant B mediante request HTTP como Cajero A
        $this->actingAs($cajeroA);
        $respTampering = $this->get('/inventory/' . $productoTenantB->id . '/edit');
        $this->assertTrue(in_array($respTampering->status(), [403, 404]), 'Intento de manipular ID de otro tenant retorna 403 o 404.');

        // =========================================================================
        // 5. INICIO DE LA JORNADA OPERATIVA REAL (08:00):
        //    APERTURA DE CAJA PRINCIPAL CON $200.000 COP
        // =========================================================================
        /** @var CashRegister $cashRegister */
        $cashRegister = CashRegister::where('tenant_id', $tenantA->id)->firstOrFail();
        $cashSession = $cashRegisterService->openSession($cashRegister, $cajeroA, 200000);

        $this->assertNotNull($cashSession);
        $this->assertEquals('open', $cashSession->status);
        $this->assertEquals(200000, $cashSession->opening_amount);

        // =========================================================================
        // 6. COMPRA DE INVENTARIO CON RECEPCIÓN INMEDIATA (08:15)
        // =========================================================================
        $proveedor = Supplier::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Distribuidora Mayorista de Licores y Bebidas SAS',
            'tax_id' => '900.555.666-8',
            'phone' => '3109998877',
            'contact_name' => 'Alfonso Proveedor',
            'is_active' => true,
        ]);

        // Compra 1: 20 cajas Águila ($90.000/caja -> 20 * 30 = 600 botellas)
        // Compra 2: 10 cajas Poker ($85.000/caja -> 10 * 30 = 300 botellas)
        // Compra 3: 12 botellas Aguardiente ($85.000/botella = $1.020.000)
        // Compra 4: 6 botellas Whisky ($150.000/botella = $900.000)
        // Compra 5: 50 Coca-Colas ($3.000/botella = $150.000)
        $purchase = $purchaseService->createPurchase(
            $proveedor->id,
            [
                [
                    'product_id' => $cervezaAguila->id,
                    'product_presentation_id' => $aguilaCaja->id,
                    'conversion_factor' => 30,
                    'quantity' => 20,
                    'unit_cost' => 90000,
                ],
                [
                    'product_id' => $cervezaPoker->id,
                    'product_presentation_id' => $pokerCaja->id,
                    'conversion_factor' => 30,
                    'quantity' => 10,
                    'unit_cost' => 85000,
                ],
                [
                    'product_id' => $aguardiente->id,
                    'product_presentation_id' => null,
                    'conversion_factor' => 1,
                    'quantity' => 12,
                    'unit_cost' => 85000,
                ],
                [
                    'product_id' => $whisky->id,
                    'product_presentation_id' => null,
                    'conversion_factor' => 1,
                    'quantity' => 6,
                    'unit_cost' => 150000,
                ],
                [
                    'product_id' => $cocaCola->id,
                    'product_presentation_id' => null,
                    'conversion_factor' => 1,
                    'quantity' => 50,
                    'unit_cost' => 3000,
                ],
            ],
            $adminA,
            'FAC-PROV-9988',
            'Compra de abastecimiento general para jornada',
            true // Recibir inmediatamente en inventario
        );

        $this->assertEquals('received', $purchase->status);
        $cervezaAguila->refresh();
        $cervezaPoker->refresh();
        $aguardiente->refresh();
        $whisky->refresh();
        $cocaCola->refresh();

        $this->assertEquals(600, $cervezaAguila->stock, '20 cajas de 30 unidades equivalen exactamente a 600 botellas de Águila');
        $this->assertEquals(300, $cervezaPoker->stock, '10 cajas de 30 unidades equivalen exactamente a 300 botellas de Poker');
        $this->assertEquals(12, $aguardiente->stock, '12 botellas de Aguardiente ingresadas');
        $this->assertEquals(6, $whisky->stock, '6 botellas de Whisky ingresadas');
        $this->assertEquals(50, $cocaCola->stock, '50 Coca-Colas ingresadas');

        // =========================================================================
        // 7. VENTAS DE MOSTRADOR INDIVIDUALES CON DIFERENTES MEDIOS DE PAGO
        // =========================================================================
        $pmCash = PaymentMethod::where('tenant_id', $tenantA->id)->where('code', 'cash')->firstOrFail();
        $pmNequi = PaymentMethod::where('tenant_id', $tenantA->id)->where('code', 'nequi')->firstOrFail();
        $pmDaviplata = PaymentMethod::where('tenant_id', $tenantA->id)->where('code', 'daviplata')->firstOrFail();
        $pmCard = PaymentMethod::where('tenant_id', $tenantA->id)->where('code', 'card')->firstOrFail();

        // Venta 1: 1 cerveza Águila (Efectivo: $4.500)
        $sale1 = $saleService->createSale(
            [['product_id' => $cervezaAguila->id, 'quantity' => 1]],
            [['payment_method_id' => $pmCash->id, 'amount' => 4500, 'received_amount' => 5000, 'change_amount' => 500]],
            $cashSession,
            $cajeroA
        );
        $this->assertEquals(4500, $sale1->total);

        // Venta 2: 1 six de Águila (Nequi: $26.000)
        $sale2 = $saleService->createSale(
            [['product_id' => $cervezaAguila->id, 'product_presentation_id' => $aguilaSix->id, 'quantity' => 1]],
            [['payment_method_id' => $pmNequi->id, 'amount' => 26000]],
            $cashSession,
            $cajeroA
        );
        $this->assertEquals(26000, $sale2->total);

        // Venta 3: 1 caja de Poker (Daviplata: $125.000)
        $sale3 = $saleService->createSale(
            [['product_id' => $cervezaPoker->id, 'product_presentation_id' => $pokerCaja->id, 'quantity' => 1]],
            [['payment_method_id' => $pmDaviplata->id, 'amount' => 125000]],
            $cashSession,
            $cajeroA
        );
        $this->assertEquals(125000, $sale3->total);

        // Venta 4: 1 botella de Aguardiente (Tarjeta: $110.000)
        $sale4 = $saleService->createSale(
            [['product_id' => $aguardiente->id, 'quantity' => 1]],
            [['payment_method_id' => $pmCard->id, 'amount' => 110000]],
            $cashSession,
            $cajeroA
        );
        $this->assertEquals(110000, $sale4->total);

        // Venta 5: 2 Coca-Colas (Efectivo: $10.000)
        $sale5 = $saleService->createSale(
            [['product_id' => $cocaCola->id, 'quantity' => 2]],
            [['payment_method_id' => $pmCash->id, 'amount' => 10000]],
            $cashSession,
            $cajeroA
        );
        $this->assertEquals(10000, $sale5->total);

        // Venta 6: 1 botella de Whisky (Nequi: $210.000)
        $sale6 = $saleService->createSale(
            [['product_id' => $whisky->id, 'quantity' => 1]],
            [['payment_method_id' => $pmNequi->id, 'amount' => 210000]],
            $cashSession,
            $cajeroA
        );
        $this->assertEquals(210000, $sale6->total);

        // =========================================================================
        // 8. VALIDACIÓN MATEMÁTICA Y TRAZABILIDAD EXACTA DE INVENTARIO
        //    STOCK INICIAL + COMPRAS - VENTAS = STOCK FINAL
        // =========================================================================
        $cervezaAguila->refresh();
        $cervezaPoker->refresh();
        $aguardiente->refresh();
        $whisky->refresh();
        $cocaCola->refresh();

        // Águila: 0 inicial + 600 compradas - 1 (unidad) - 6 (six pack) = 593 botellas
        $this->assertEquals(593, $cervezaAguila->stock);

        // Poker: 0 inicial + 300 compradas - 30 (1 caja) = 270 botellas
        $this->assertEquals(270, $cervezaPoker->stock);

        // Aguardiente: 0 inicial + 12 compradas - 1 (botella) = 11 botellas
        $this->assertEquals(11, $aguardiente->stock);

        // Whisky: 0 inicial + 6 compradas - 1 (botella) = 5 botellas
        $this->assertEquals(5, $whisky->stock);

        // Coca-Cola: 0 inicial + 50 compradas - 2 (unidades) = 48 botellas
        $this->assertEquals(48, $cocaCola->stock);

        // =========================================================================
        // 9. PRUEBA DE MESAS Y CUENTAS ABIERTAS CON CONGELAMIENTO DE PRECIO
        // =========================================================================
        /** @var Table $mesa1 */
        $mesa1 = Table::where('tenant_id', $tenantA->id)->where('name', 'M1')->firstOrFail();
        $this->assertEquals('free', $mesa1->status);

        // Abrir cuenta Mesa 1 con 4 Águila, 2 Coca-Colas, 1 Aguardiente
        $cuentaMesa1 = $tableAccountService->openAccount($mesa1->id, 'Mesa 1 Amigos', null, $cajeroA);
        $mesa1->refresh();
        $this->assertEquals('occupied', $mesa1->status);
        $this->assertEquals('open', $cuentaMesa1->status);

        $itemAguila = $tableAccountService->addItem($cuentaMesa1, $cervezaAguila->id, null, 4); // 4 * 4500 = 18000
        $itemCola = $tableAccountService->addItem($cuentaMesa1, $cocaCola->id, null, 2); // 2 * 5000 = 10000
        $itemGuaro = $tableAccountService->addItem($cuentaMesa1, $aguardiente->id, null, 1); // 1 * 110000 = 110000

        $cuentaMesa1->refresh();
        $this->assertEquals(138000, $cuentaMesa1->total, '18.000 + 10.000 + 110.000 = 138.000 COP');

        // Modificar cuenta: Agregar 2 cervezas Poker (2 * 4500 = 9000), 1 Snack (Papas: 4000)
        $tableAccountService->addItem($cuentaMesa1, $cervezaPoker->id, null, 2);
        $tableAccountService->addItem($cuentaMesa1, $papas->id, null, 1);

        // Eliminar 1 Coca-Cola de la mesa
        $tableAccountService->removeItem($cuentaMesa1, $itemCola->id, 1); // Queda 1 Coca-Cola (-5000)
        $cuentaMesa1->refresh();
        // 138.000 + 9.000 + 4.000 - 5.000 = 146.000 COP
        $this->assertEquals(146000, $cuentaMesa1->total);

        // Liquidar y cerrar cuenta Mesa 1 mediante POS con pago mixto
        // Total: 146.000 COP -> Efectivo $46.000 + Nequi $100.000
        $itemsToCheckout = [];
        foreach ($cuentaMesa1->items as $acItem) {
            $itemsToCheckout[] = [
                'product_id' => $acItem->product_id,
                'product_presentation_id' => $acItem->product_presentation_id,
                'quantity' => $acItem->quantity,
            ];
        }

        $saleMesa1 = $saleService->createSale(
            $itemsToCheckout,
            [
                ['payment_method_id' => $pmCash->id, 'amount' => 46000],
                ['payment_method_id' => $pmNequi->id, 'amount' => 100000],
            ],
            $cashSession,
            $cajeroA,
            $cuentaMesa1->id
        );

        $this->assertEquals(146000, $saleMesa1->total);
        $cuentaMesa1->refresh();
        $mesa1->refresh();
        $this->assertEquals('closed', $cuentaMesa1->status, 'Cuenta pasa a estado cerrada');
        $this->assertEquals('free', $mesa1->status, 'Mesa queda libre automáticamente');

        // =========================================================================
        // 10. PRUEBAS DE REGLAS DE NEGOCIO: LICORES Y CONTROL DE STOCK NEGATIVO
        // =========================================================================
        // Regla: Licores no permiten venta fraccionada ni presentaciones que no sean botella completa
        $mediaAguardiente = ProductPresentation::create([
            'tenant_id' => $tenantA->id,
            'product_id' => $aguardiente->id,
            'name' => 'Media Botella Aguardiente',
            'conversion_factor' => 2,
            'cost_price' => 45000,
            'sale_price' => 65000,
            'is_active' => true,
        ]);

        $liquorRuleTriggered = false;
        try {
            $saleService->createSale(
                [['product_id' => $aguardiente->id, 'product_presentation_id' => $mediaAguardiente->id, 'quantity' => 1]],
                [['payment_method_id' => $pmCash->id, 'amount' => 65000]],
                $cashSession,
                $cajeroA
            );
        } catch (Exception $e) {
            $liquorRuleTriggered = true;
            $this->assertStringContainsString('Los licores solo pueden venderse por botella completa', $e->getMessage());
        }
        $this->assertTrue($liquorRuleTriggered, 'El sistema rechaza de manera estricta venta fraccionada en licores');

        // Regla: Rechazo por stock insuficiente (1000 cajas cuando solo hay stock limitado)
        $stockInsuficienteTriggered = false;
        try {
            $saleService->createSale(
                [['product_id' => $cervezaAguila->id, 'product_presentation_id' => $aguilaCaja->id, 'quantity' => 1000]],
                [['payment_method_id' => $pmCash->id, 'amount' => 125000000]],
                $cashSession,
                $cajeroA
            );
        } catch (Exception $e) {
            $stockInsuficienteTriggered = true;
            $this->assertStringContainsString('Stock insuficiente', $e->getMessage());
        }
        $this->assertTrue($stockInsuficienteTriggered, 'Rechazo garantizado con mensaje claro de Stock insuficiente');

        // =========================================================================
        // 11. INVENTARIO FÍSICO Y AJUSTE AUDITADO
        //     Sistema: 25 unidades -> Físico: 23 unidades -> Ajuste: -2 unidades
        // =========================================================================
        $productoAjuste = Product::create([
            'tenant_id' => $tenantA->id,
            'branch_id' => $branchA->id,
            'category_id' => $catSnacks->id,
            'name' => 'Maní Especial Salado 50g',
            'sku' => 'SNK-MAN-01',
            'price_buy' => 1500,
            'price_sell' => 3000,
            'stock' => 25,
            'stock_min' => 5,
            'is_active' => true,
        ]);

        $movAjuste = $inventoryService->adjustStock(
            $productoAjuste,
            23,
            'Conteo físico quincenal: faltante de 2 unidades por vencimiento o merma',
            $adminA
        );

        $this->assertEquals(-2, $movAjuste->quantity);
        $this->assertEquals(23, $movAjuste->stock_after);
        $this->assertEquals(23, $productoAjuste->fresh()->stock);

        // =========================================================================
        // 12. INMUTABILIDAD DE PRECIOS HISTÓRICOS TRAS CAMBIO DE PRECIO
        // =========================================================================
        // Cambiar precio de la Cerveza Águila de 4.500 a 5.000 COP
        $cervezaAguila->price_sell = 5000;
        $cervezaAguila->save();

        // La venta 1 efectuada al inicio debe permanecer inalterada en $4.500 COP
        $sale1Item = SaleItem::where('sale_id', $sale1->id)->where('product_id', $cervezaAguila->id)->first();
        $this->assertEquals(4500, $sale1Item->unit_price, 'El precio histórico snapshot no se altera ante cambios del catálogo');

        // =========================================================================
        // 13. GASTOS OPERATIVOS (ACTUALIZACIÓN SOLICITADA POR EL USUARIO: SIN CATEGORÍA)
        //     SOLO: MONTO, MEDIO DE PAGO, FECHA, DESCRIPCIÓN
        // =========================================================================
        $this->actingAs($cajeroA);
        $respGasto = $this->post(route('expenses.store'), [
            'amount' => 30000,
            'payment_method' => 'cash',
            'expense_date' => now()->format('Y-m-d'),
            'description' => 'Compra de bolsas plásticas, hielo y papel térmico POS',
        ]);
        $respGasto->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'tenant_id' => $tenantA->id,
            'amount' => 30000,
            'payment_method' => 'cash',
            'description' => 'Compra de bolsas plásticas, hielo y papel térmico POS',
            'expense_category_id' => null, // Confirmación estricta de ausencia de categoría
        ]);

        // =========================================================================
        // 14. CIERRE DE CAJA, ARQUEO FÍSICO Y AUDITORÍA DE DIFERENCIA (SOBRANTE / FALTANTE)
        // =========================================================================
        // Cálculo del efectivo esperado en caja:
        // Base Inicial: $200.000
        // + Ventas Efectivo:
        //   Sale 1: $4.500
        //   Sale 5: $10.000
        //   Sale Mesa 1: $46.000
        // Total Ventas Efectivo = $60.500
        // - Gastos en Efectivo: -$30.000
        // Efectivo Esperado = 200.000 + 60.500 - 30.000 = $230.500 COP
        $cashSummary = $cashSession->calculateCashSummary();
        $this->assertEquals(230500, $cashSummary['expected_cash'], 'Efectivo esperado matemáticamente exacto: $230.500 COP');

        // Arqueo físico con faltante de $5.000 COP (Contado = $225.500 COP)
        $closedSession = $cashRegisterService->closeSession(
            $cashSession,
            225500,
            'Faltante de $5.000 COP por error de cambio en mostrador',
            $cajeroA
        );

        $this->assertEquals('closed', $closedSession->status);
        $this->assertEquals(-5000, $closedSession->difference, 'Diferencia registrada con precisión: -$5.000 COP');

        // =========================================================================
        // 15. AUDITORÍA INTEGRAL Y TRAZABILIDAD
        // =========================================================================
        $auditLogsCount = AuditLog::where('tenant_id', $tenantA->id)->count();
        $this->assertGreaterThan(5, $auditLogsCount, 'Se generaron registros de auditoría para todas las operaciones críticas');

        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenantA->id,
            'action' => 'cash_session_opened',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenantA->id,
            'action' => 'sale_created',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenantA->id,
            'action' => 'expense_created',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $tenantA->id,
            'action' => 'cash_session_closed',
        ]);
    }
}
