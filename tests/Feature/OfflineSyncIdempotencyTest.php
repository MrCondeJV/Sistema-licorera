<?php

namespace Tests\Feature;

use App\Models\CashRegister;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Services\CashRegisterService;
use App\Services\OfflineSyncService;
use App\Services\SaleService;
use App\Services\TenantContext;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OfflineSyncIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_offline_sale_sync_is_strictly_idempotent(): void
    {
        $tenantService = new TenantService();
        $res = $tenantService->registerBusiness([
            'business_name' => 'Licorera Offline',
            'admin_name' => 'Admin Offline',
            'email' => 'offline@test.com',
            'pin' => '1234',
        ]);
        $tenant = $res['tenant'];
        $user = $res['user'];

        app(TenantContext::class)->setTenant($tenant);

        // Open cash session
        $register = CashRegister::where('tenant_id', $tenant->id)->firstOrFail();
        $session = (new CashRegisterService())->openSession($register, $user, 100000);

        // Create product with 50 stock
        $cat = Category::where('tenant_id', $tenant->id)->first();
        $beer = Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $cat->id,
            'sku' => 'CER-OFFLINE-01',
            'name' => 'Cerveza Corona Offline',
            'price_buy' => 4000,
            'price_sell' => 8000,
            'stock' => 50,
            'stock_min' => 5,
            'base_unit' => 'unidad',
        ]);

        $cashMethod = PaymentMethod::where('tenant_id', $tenant->id)->where('code', 'cash')->firstOrFail();

        $clientSyncToken = (string) Str::uuid();

        $payload = [
            'items' => [
                [
                    'product_id' => $beer->id,
                    'quantity' => 2,
                ]
            ],
            'payments' => [
                [
                    'payment_method_id' => $cashMethod->id,
                    'amount' => 16000,
                    'received_amount' => 20000,
                    'change_amount' => 4000,
                ]
            ]
        ];

        $syncService = app(OfflineSyncService::class);

        // 1. First synchronization attempt
        $res1 = $syncService->processOfflineSale($payload, $clientSyncToken, $user, $session);
        $beer->refresh();

        $this->assertEquals('synced', $res1['status']);
        $this->assertEquals(48, $beer->stock, 'Initial sync must deduct 2 units from 50 to 48');
        $this->assertEquals(1, Sale::where('tenant_id', $tenant->id)->count());

        // 2. Retry / Duplicate synchronization attempt with same UUID token
        $res2 = $syncService->processOfflineSale($payload, $clientSyncToken, $user, $session);
        $beer->refresh();

        $this->assertEquals('already_processed', $res2['status']);
        $this->assertEquals(48, $beer->stock, 'Duplicate sync must NOT deduct stock a second time');
        $this->assertEquals(1, Sale::where('tenant_id', $tenant->id)->count(), 'Must not duplicate sale records');
    }
}
