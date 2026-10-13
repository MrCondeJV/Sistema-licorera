<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Table;
use App\Services\TableAccountService;
use App\Services\TenantContext;
use App\Services\TenantService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_open_account_and_double_open_prevention(): void
    {
        $tenantService = new TenantService();
        $res = $tenantService->registerBusiness([
            'business_name' => 'Licorera Mesas',
            'admin_name' => 'Admin Mesas',
            'email' => 'mesas@test.com',
            'pin' => '1234',
        ]);
        $tenant = $res['tenant'];
        $user = $res['user'];

        app(TenantContext::class)->setTenant($tenant);

        $table = Table::where('tenant_id', $tenant->id)->where('name', 'M1')->firstOrFail();
        $this->assertEquals('free', $table->status);

        $accountService = new TableAccountService();

        // 1. Open account on M1
        $account = $accountService->openAccount($table->id, 'Mesa Amigos', null, $user);
        $table->refresh();

        $this->assertEquals('occupied', $table->status);
        $this->assertEquals('open', $account->status);

        // 2. Attempting to open a second account on M1 must fail
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("ya se encuentra ocupada");

        $accountService->openAccount($table->id, 'Otro Grupo', null, $user);
    }

    public function test_table_items_freeze_price_snapshots(): void
    {
        $tenantService = new TenantService();
        $res = $tenantService->registerBusiness([
            'business_name' => 'Licorera Precios',
            'admin_name' => 'Admin Precios',
            'email' => 'precios@test.com',
            'pin' => '1234',
        ]);
        $tenant = $res['tenant'];
        $user = $res['user'];

        app(TenantContext::class)->setTenant($tenant);
        $cat = Category::where('tenant_id', $tenant->id)->first();

        $beer = Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $cat->id,
            'sku' => 'CER-PRICE-01',
            'name' => 'Cerveza Club',
            'price_buy' => 2500,
            'price_sell' => 5000,
            'stock' => 50,
            'stock_min' => 5,
            'base_unit' => 'unidad',
        ]);

        $table = Table::where('tenant_id', $tenant->id)->where('name', 'M2')->firstOrFail();
        $accountService = new TableAccountService();
        $account = $accountService->openAccount($table->id, 'Grupo 1', null, $user);

        // Add 2 beers at $5.000
        $item = $accountService->addItem($account, $beer->id, null, 2);
        $account->refresh();

        $this->assertEquals(5000, $item->unit_price);
        $this->assertEquals(10000, $account->total);

        // Later, administrator changes product catalog price to $7.000
        $beer->price_sell = 7000;
        $beer->save();

        // The open tab must preserve its original frozen price!
        $account->refresh();
        $item->refresh();

        $this->assertEquals(5000, $item->unit_price, 'Item price in open tab must remain frozen.');
        $this->assertEquals(10000, $account->total, 'Account total must remain frozen.');
    }
}
