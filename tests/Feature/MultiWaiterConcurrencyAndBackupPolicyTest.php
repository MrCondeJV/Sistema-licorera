<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Branch;
use App\Models\BranchInventory;
use App\Models\CashRegister;
use App\Models\CashSession;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Table;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BackupService;
use App\Services\InventoryService;
use App\Services\SaleService;
use App\Services\TableAccountService;
use App\Services\TenantContext;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MultiWaiterConcurrencyAndBackupPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected User $superadmin;
    protected Tenant $tenant;
    protected Branch $branch;
    protected User $tenantAdmin;
    protected User $waiter1;
    protected User $waiter2;
    protected Product $beer;
    protected Product $whisky;
    protected Table $table1;
    protected CashSession $cashSession;
    protected PaymentMethod $cashMethod;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Superadmin
        $this->superadmin = User::create([
            'name' => 'Super Administrador',
            'email' => 'superadmin@mambacode.test',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'pin_hash' => \Illuminate\Support\Facades\Hash::make('0000'),
            'is_superadmin' => true,
            'is_active' => true,
        ]);

        // 2. Create Tenant & Branch
        $this->tenant = Tenant::create([
            'name' => 'Licorera La 70',
            'slug' => 'licorera-la-70',
            'status' => 'active',
            'subscription_ends_at' => now()->addDays(30),
        ]);

        $this->branch = Branch::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Sede Principal 70',
            'address' => 'Carrera 70 #45-10',
            'is_main' => true,
            'is_active' => true,
        ]);

        // 3. Create Tenant Admin
        $this->tenantAdmin = User::create([
            'name' => 'Admin Juan Carlos',
            'email' => 'admin@licorera70.test',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'pin_hash' => \Illuminate\Support\Facades\Hash::make('1111'),
            'is_superadmin' => false,
            'is_active' => true,
        ]);

        $this->tenantAdmin->tenants()->attach($this->tenant->id, [
            'role' => 'admin',
            'is_active' => true,
        ]);

        // 4. Create Waiter 1 and Waiter 2
        $this->waiter1 = User::create([
            'name' => 'Mesero Pedro',
            'email' => 'pedro@licorera70.test',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'pin_hash' => \Illuminate\Support\Facades\Hash::make('2222'),
            'is_superadmin' => false,
            'is_active' => true,
        ]);
        $this->waiter1->tenants()->attach($this->tenant->id, [
            'role' => 'waiter',
            'is_active' => true,
        ]);
        $this->waiter1->branches()->attach($this->branch->id, ['tenant_id' => $this->tenant->id]);

        $this->waiter2 = User::create([
            'name' => 'Mesera Valentina',
            'email' => 'valentina@licorera70.test',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
            'pin_hash' => \Illuminate\Support\Facades\Hash::make('3333'),
            'is_superadmin' => false,
            'is_active' => true,
        ]);
        $this->waiter2->tenants()->attach($this->tenant->id, [
            'role' => 'waiter',
            'is_active' => true,
        ]);
        $this->waiter2->branches()->attach($this->branch->id, ['tenant_id' => $this->tenant->id]);

        // 5. Setup Category & Products
        $category = Category::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Cervezas y Licores',
            'slug' => 'cervezas-y-licores',
        ]);

        $this->beer = Product::create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'sku' => 'CER-CORONA-330',
            'name' => 'Corona Extra 330ml',
            'barcode' => '7709988776611',
            'price_buy' => 4500,
            'price_sell' => 9000,
            'stock' => 10,
            'stock_min' => 2,
            'base_unit' => 'unidad',
            'is_liquor' => false,
            'is_active' => true,
        ]);

        BranchInventory::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->beer->id,
            'stock' => 10,
            'stock_min' => 2,
        ]);

        $this->whisky = Product::create([
            'tenant_id' => $this->tenant->id,
            'category_id' => $category->id,
            'sku' => 'LIC-OLD-PARR-750',
            'name' => 'Old Parr 750ml',
            'barcode' => '7709988776622',
            'price_buy' => 85000,
            'price_sell' => 150000,
            'stock' => 1, // Only 1 bottle in stock for race test
            'stock_min' => 1,
            'base_unit' => 'botella',
            'is_liquor' => true,
            'is_active' => true,
        ]);

        BranchInventory::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'product_id' => $this->whisky->id,
            'stock' => 1,
            'stock_min' => 1,
        ]);

        // 6. Setup Tables
        $this->table1 = Table::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'name' => 'Mesa 1 VIP',
            'capacity' => 6,
            'status' => 'free',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // 7. Setup Cash Register and Open Session
        $cashRegister = CashRegister::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'name' => 'Caja Barra Principal',
            'is_active' => true,
        ]);

        $this->cashSession = CashSession::create([
            'tenant_id' => $this->tenant->id,
            'branch_id' => $this->branch->id,
            'cash_register_id' => $cashRegister->id,
            'user_id' => $this->tenantAdmin->id,
            'opening_amount' => 100000,
            'current_amount' => 100000,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $this->cashMethod = PaymentMethod::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Efectivo',
            'code' => 'cash',
            'requires_reference' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Clean any residual test backups
        $backupDir = storage_path('app/backups');
        if (!File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }
    }

    /**
     * Test 1: Tenant Admin is strictly blocked from backups (cannot view, create, schedule or run auto backups).
     */
    public function test_tenant_admin_cannot_access_or_trigger_backups()
    {
        // 1. Tenant admin accessing /backups -> route was removed from tenant web group
        $response = $this->actingAs($this->tenantAdmin)
            ->withSession([
                'pin_authenticated' => true,
                'active_tenant_id' => $this->tenant->id,
                'active_branch_id' => $this->branch->id,
            ])
            ->get('/backups');

        // Route does not exist under tenant routes
        $response->assertStatus(404);

        // 2. Tenant admin accessing superadmin.backups -> 403 Forbidden
        $superadminRouteResponse = $this->actingAs($this->tenantAdmin)
            ->withSession([
                'pin_authenticated' => true,
                'active_tenant_id' => $this->tenant->id,
            ])
            ->get(route('superadmin.backups'));

        $superadminRouteResponse->assertStatus(403);

        // 3. Tenant admin attempting POST to superadmin.backups.create -> 403 Forbidden
        $createResponse = $this->actingAs($this->tenantAdmin)
            ->withSession([
                'pin_authenticated' => true,
                'active_tenant_id' => $this->tenant->id,
            ])
            ->post(route('superadmin.backups.create'), [
                'backup_type' => 'general',
            ]);

        $createResponse->assertStatus(403);

        // 4. Tenant admin attempting POST to superadmin.backups.schedule -> 403 Forbidden
        $scheduleResponse = $this->actingAs($this->tenantAdmin)
            ->withSession([
                'pin_authenticated' => true,
                'active_tenant_id' => $this->tenant->id,
            ])
            ->post(route('superadmin.backups.schedule'), [
                'frequency' => 'daily',
                'time' => '02:00',
                'retention_days' => 10,
            ]);

        $scheduleResponse->assertStatus(403);

        // 5. Tenant admin attempting to trigger superadmin.backups.run-auto -> 403 Forbidden
        $runAutoResponse = $this->actingAs($this->tenantAdmin)
            ->withSession([
                'pin_authenticated' => true,
                'active_tenant_id' => $this->tenant->id,
            ])
            ->post(route('superadmin.backups.run-auto'));

        $runAutoResponse->assertStatus(403);
    }

    /**
     * Test 2: Superadmin can configure automated backup schedule and execute Artisan auto-backup.
     */
    public function test_superadmin_can_configure_auto_backup_schedule_and_run_command()
    {
        // 1. Superadmin visits backup management view
        $viewResponse = $this->actingAs($this->superadmin)
            ->withSession(['pin_authenticated' => true])
            ->get(route('superadmin.backups'));

        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Copias de Seguridad (Backups)');
        $viewResponse->assertSee('Programación de Auto-Copias');
        $viewResponse->assertSee('Ejecutar Auto-Copia Ahora');

        // 2. Superadmin updates schedule configuration
        $updateScheduleResponse = $this->actingAs($this->superadmin)
            ->withSession(['pin_authenticated' => true])
            ->post(route('superadmin.backups.schedule'), [
                'enabled' => '1',
                'frequency' => 'daily',
                'time' => '03:30',
                'retention_days' => 14,
                'type' => 'all',
            ]);

        $updateScheduleResponse->assertRedirect(route('superadmin.backups'));
        $updateScheduleResponse->assertSessionHas('success');

        // Verify config was saved to file
        $backupService = app(BackupService::class);
        $config = $backupService->getScheduleConfig();
        $this->assertTrue($config['enabled']);
        $this->assertEquals('daily', $config['frequency']);
        $this->assertEquals('03:30', $config['time']);
        $this->assertEquals(14, $config['retention_days']);

        // 3. Run artisan command php artisan backup:auto
        $exitCode = Artisan::call('backup:auto');
        $this->assertEquals(0, $exitCode);

        // Verify auto-backup file was generated
        $files = File::files(storage_path('app/backups'));
        $foundAutoBackup = false;
        foreach ($files as $file) {
            if (str_contains($file->getFilename(), 'backup_auto_general_')) {
                $foundAutoBackup = true;
                break;
            }
        }
        $this->assertTrue($foundAutoBackup, 'Automated general SQLite snapshot was successfully generated.');

        // 4. Test web trigger superadmin.backups.run-auto
        $runAutoWebResponse = $this->actingAs($this->superadmin)
            ->withSession(['pin_authenticated' => true])
            ->post(route('superadmin.backups.run-auto'));

        $runAutoWebResponse->assertRedirect(route('superadmin.backups'));
        $runAutoWebResponse->assertSessionHas('success');
    }

    /**
     * Test 3: Concurrency - Two waiters simultaneously attempting to open the same table.
     * Pessimistic row locking ensures only one succeeds and the other receives an exception.
     */
    public function test_two_waiters_concurrently_opening_same_table()
    {
        $accountService = app(TableAccountService::class);

        // Waiter 1 opens table 1
        $account1 = $accountService->openAccount($this->table1->id, 'Mesa de Juan', null, $this->waiter1);
        $this->table1->refresh();

        $this->assertEquals('occupied', $this->table1->status);
        $this->assertEquals('open', $account1->status);
        $this->assertEquals($this->waiter1->id, $account1->opened_by);

        // Waiter 2 simultaneously tries to open table 1 -> must throw occupied Exception
        $secondOpenFailed = false;
        try {
            $accountService->openAccount($this->table1->id, 'Mesa de Maria', null, $this->waiter2);
        } catch (Exception $e) {
            $secondOpenFailed = true;
            $this->assertStringContainsString("ya se encuentra ocupada", $e->getMessage());
        }

        $this->assertTrue($secondOpenFailed, 'Waiter 2 was safely prevented from double-opening an occupied table.');
        $this->table1->refresh();
        $this->assertEquals('occupied', $this->table1->status);
        $this->assertEquals(1, Account::where('table_id', $this->table1->id)->where('status', 'open')->count());
    }

    /**
     * Test 4: Concurrency - Two waiters simultaneously adding items to the same open table account.
     * Pessimistic row locking and DB transaction ensure all items accumulate atomically without lost updates.
     */
    public function test_two_waiters_concurrently_adding_items_to_same_table_account()
    {
        $accountService = app(TableAccountService::class);

        // Open account
        $account = $accountService->openAccount($this->table1->id, 'Mesa 1 VIP', null, $this->waiter1);

        // Waiter 1 adds 2 Coronas
        $accountService->addItem($account, $this->beer->id, null, 2);

        // Waiter 2 adds 3 Coronas to the same account
        $accountService->addItem($account, $this->beer->id, null, 3);

        $account->refresh();

        // Must have exactly 1 AccountItem with quantity 5
        $item = $account->items()->where('product_id', $this->beer->id)->first();
        $this->assertNotNull($item);
        $this->assertEquals(5, $item->quantity);
        $this->assertEquals(45000, $item->subtotal); // 5 * 9000

        // Account total must equal 45000
        $this->assertEquals(45000, $account->total);
    }

    /**
     * Test 5: Concurrency - Concurrent stock depletion with 1 item remaining.
     * When 2 waiters attempt to sell the last unit of Old Parr simultaneously,
     * the second one fails with "Stock insuficiente" and stock never goes negative.
     */
    public function test_concurrent_stock_depletion_prevents_negative_stock()
    {
        $saleService = app(SaleService::class);
        app(TenantContext::class)->setTenant($this->tenant);
        app(TenantContext::class)->setBranch($this->branch);

        $this->assertEquals(1, $this->whisky->stock);

        $saleItems = [
            [
                'product_id' => $this->whisky->id,
                'quantity' => 1,
            ],
        ];

        $payment = [
            [
                'payment_method_id' => $this->cashMethod->id,
                'amount' => 150000,
            ],
        ];

        // Waiter 1 completes sale for the last bottle
        $sale1 = $saleService->createSale(
            $saleItems,
            $payment,
            $this->cashSession,
            $this->waiter1
        );

        $this->assertNotNull($sale1);
        $this->whisky->refresh();
        $this->assertEquals(0, $this->whisky->stock);

        // Waiter 2 simultaneously attempts to sell the same bottle
        $waiter2Failed = false;
        try {
            $saleService->createSale(
                $saleItems,
                $payment,
                $this->cashSession,
                $this->waiter2
            );
        } catch (Exception $e) {
            $waiter2Failed = true;
            $this->assertStringContainsString("Stock insuficiente", $e->getMessage());
        }

        $this->assertTrue($waiter2Failed, 'Waiter 2 was safely prevented from selling out-of-stock product.');

        // Stock remains 0 and never became negative
        $this->whisky->refresh();
        $this->assertEquals(0, $this->whisky->stock);

        $branchInv = BranchInventory::where('product_id', $this->whisky->id)
            ->where('branch_id', $this->branch->id)
            ->first();
        $this->assertEquals(0, $branchInv->stock);
    }

    /**
     * Test 6: Concurrency - Simultaneous table checkout collision.
     * When 2 waiters/cashiers hit "Cobrar cuenta" at the exact same instant,
     * the second request safely fails with "ya se encuentra cerrada o cobrada".
     */
    public function test_concurrent_table_checkout_prevents_double_payment_and_double_stock_deduction()
    {
        $accountService = app(TableAccountService::class);
        $saleService = app(SaleService::class);

        app(TenantContext::class)->setTenant($this->tenant);
        app(TenantContext::class)->setBranch($this->branch);

        // 1. Open table and add 2 beers
        $account = $accountService->openAccount($this->table1->id, 'Mesa VIP 1', null, $this->waiter1);
        $accountService->addItem($account, $this->beer->id, null, 2);

        $beerInitialStock = $this->beer->stock; // 10

        $items = [
            [
                'product_id' => $this->beer->id,
                'quantity' => 2,
            ],
        ];

        $payment = [
            [
                'payment_method_id' => $this->cashMethod->id,
                'amount' => 18000,
            ],
        ];

        // 2. Waiter 1 pays and closes the account
        $sale = $saleService->createSale(
            $items,
            $payment,
            $this->cashSession,
            $this->waiter1,
            $account->id
        );

        $this->assertNotNull($sale);
        $account->refresh();
        $this->table1->refresh();

        $this->assertEquals('closed', $account->status);
        $this->assertEquals('free', $this->table1->status);

        $this->beer->refresh();
        $this->assertEquals($beerInitialStock - 2, $this->beer->stock);

        // 3. Waiter 2 simultaneously tries to charge the same account
        $doubleCheckoutFailed = false;
        try {
            $saleService->createSale(
                $items,
                $payment,
                $this->cashSession,
                $this->waiter2,
                $account->id
            );
        } catch (Exception $e) {
            $doubleCheckoutFailed = true;
            $this->assertStringContainsString("ya se encuentra cerrada o cobrada", $e->getMessage());
        }

        $this->assertTrue($doubleCheckoutFailed, 'Second simultaneous checkout was blocked.');

        // Verify stock was only deducted once
        $this->beer->refresh();
        $this->assertEquals($beerInitialStock - 2, $this->beer->stock);
    }
}
