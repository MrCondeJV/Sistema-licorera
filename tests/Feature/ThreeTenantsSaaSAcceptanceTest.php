<?php

namespace Tests\Feature;

use App\Models\CashRegister;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Table;
use App\Models\Tenant;
use App\Services\CashRegisterService;
use App\Services\SaleService;
use App\Services\TenantContext;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThreeTenantsSaaSAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_three_liquor_stores_registered_in_same_db_with_total_isolation(): void
    {
        $tenantService = new TenantService();

        // 1. Register 3 distinct liquor stores
        $store1 = $tenantService->registerBusiness([
            'business_name' => 'Licorera La 70 Medellín',
            'admin_name' => 'Administrador La 70',
            'email' => 'la70@lanoche.pos',
            'pin' => '1001',
        ]);

        $store2 = $tenantService->registerBusiness([
            'business_name' => 'Licorera Parque Lleras',
            'admin_name' => 'Administrador Lleras',
            'email' => 'lleras@lanoche.pos',
            'pin' => '2002',
        ]);

        $store3 = $tenantService->registerBusiness([
            'business_name' => 'Licorera Bogotá Zona Rosa',
            'admin_name' => 'Administrador Bogotá',
            'email' => 'bogota@lanoche.pos',
            'pin' => '3003',
        ]);

        // Verify all 3 exist in the tenants table in MySQL
        $this->assertEquals(3, Tenant::count());

        $context = app(TenantContext::class);

        // 2. Add distinct products in each store
        $context->setTenant($store1['tenant']);
        $cat1 = Category::where('tenant_id', $store1['tenant']->id)->first();
        $prod1 = Product::create([
            'tenant_id' => $store1['tenant']->id,
            'category_id' => $cat1->id,
            'sku' => 'SKU-LA70-01',
            'name' => 'Aguardiente Antioqueño La 70',
            'price_buy' => 35000,
            'price_sell' => 60000,
            'stock' => 100,
            'stock_min' => 10,
            'base_unit' => 'botella',
            'is_liquor' => true,
        ]);

        $context->setTenant($store2['tenant']);
        $cat2 = Category::where('tenant_id', $store2['tenant']->id)->first();
        $prod2 = Product::create([
            'tenant_id' => $store2['tenant']->id,
            'category_id' => $cat2->id,
            'sku' => 'SKU-LLERAS-01',
            'name' => 'Ron Medellín Lleras',
            'price_buy' => 40000,
            'price_sell' => 70000,
            'stock' => 50,
            'stock_min' => 5,
            'base_unit' => 'botella',
            'is_liquor' => true,
        ]);

        $context->setTenant($store3['tenant']);
        $cat3 = Category::where('tenant_id', $store3['tenant']->id)->first();
        $prod3 = Product::create([
            'tenant_id' => $store3['tenant']->id,
            'category_id' => $cat3->id,
            'sku' => 'SKU-BOGOTA-01',
            'name' => 'Whisky Buchanan Bogotá',
            'price_buy' => 120000,
            'price_sell' => 180000,
            'stock' => 30,
            'stock_min' => 2,
            'base_unit' => 'botella',
            'is_liquor' => true,
        ]);

        // 3. Verify Store 1 only sees Store 1 products
        $context->setTenant($store1['tenant']);
        $store1Products = Product::all();
        $this->assertTrue($store1Products->contains('id', $prod1->id));
        $this->assertFalse($store1Products->contains('id', $prod2->id));
        $this->assertFalse($store1Products->contains('id', $prod3->id));

        // 4. Verify Store 2 only sees Store 2 products
        $context->setTenant($store2['tenant']);
        $store2Products = Product::all();
        $this->assertFalse($store2Products->contains('id', $prod1->id));
        $this->assertTrue($store2Products->contains('id', $prod2->id));
        $this->assertFalse($store2Products->contains('id', $prod3->id));

        // 5. Verify Store 3 only sees Store 3 products
        $context->setTenant($store3['tenant']);
        $store3Products = Product::all();
        $this->assertFalse($store3Products->contains('id', $prod1->id));
        $this->assertFalse($store3Products->contains('id', $prod2->id));
        $this->assertTrue($store3Products->contains('id', $prod3->id));

        // 6. Complete a sale in Store 1 and verify Store 2 & 3 have 0 sales
        $context->setTenant($store1['tenant']);
        $register1 = CashRegister::firstOrFail();
        $cashSession1 = (new CashRegisterService())->openSession($register1, $store1['user'], 100000);
        $cashMethod1 = PaymentMethod::where('code', 'cash')->firstOrFail();

        $sale1 = (app(SaleService::class))->createSale(
            [
                ['product_id' => $prod1->id, 'quantity' => 1]
            ],
            [
                ['payment_method_id' => $cashMethod1->id, 'amount' => 60000]
            ],
            $cashSession1,
            $store1['user']
        );

        $context->setTenant($store1['tenant']);
        $this->assertEquals(1, \App\Models\Sale::count());

        $context->setTenant($store2['tenant']);
        $this->assertEquals(0, \App\Models\Sale::count(), 'Store 2 must have 0 sales');

        $context->setTenant($store3['tenant']);
        $this->assertEquals(0, \App\Models\Sale::count(), 'Store 3 must have 0 sales');
    }
}
