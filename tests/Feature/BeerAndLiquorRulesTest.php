<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Services\InventoryService;
use App\Services\TenantContext;
use App\Services\TenantService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeerAndLiquorRulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_beer_six_pack_and_crate_deduct_exact_base_units(): void
    {
        $tenantService = new TenantService();
        $res = $tenantService->registerBusiness([
            'business_name' => 'Licorera Bar',
            'admin_name' => 'Admin Bar',
            'email' => 'bar@test.com',
            'pin' => '1234',
        ]);
        $tenant = $res['tenant'];
        $user = $res['user'];

        $context = app(TenantContext::class);
        $context->setTenant($tenant);

        $cat = Category::where('tenant_id', $tenant->id)->first();

        // 1. Beer with 120 initial units
        $beer = Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $cat->id,
            'sku' => 'CER-TEST-120',
            'name' => 'Cerveza Águila Test',
            'price_buy' => 2000,
            'price_sell' => 4000,
            'stock' => 120,
            'stock_min' => 10,
            'base_unit' => 'unidad',
            'is_beer' => true,
        ]);

        $sixPack = ProductPresentation::create([
            'tenant_id' => $tenant->id,
            'product_id' => $beer->id,
            'name' => 'Six-Pack',
            'conversion_factor' => 6,
            'sale_price' => 22000,
        ]);

        $crate = ProductPresentation::create([
            'tenant_id' => $tenant->id,
            'product_id' => $beer->id,
            'name' => 'Caja 24',
            'conversion_factor' => 24,
            'sale_price' => 85000,
        ]);

        $inventoryService = new InventoryService();

        // Venta de 2 unidades individuales: 120 - 2 = 118
        $inventoryService->deductStock($beer, 2, 1, 'sale', 'Venta 2 uds', $user);
        $beer->refresh();
        $this->assertEquals(118, $beer->stock);

        // Venta de 1 six-pack: 118 - 6 = 112
        $inventoryService->deductStock($beer, 1, $sixPack->conversion_factor, 'sale', 'Venta six-pack', $user);
        $beer->refresh();
        $this->assertEquals(112, $beer->stock);

        // Venta de 1 caja de 24: 112 - 24 = 88
        $inventoryService->deductStock($beer, 1, $crate->conversion_factor, 'sale', 'Venta caja 24', $user);
        $beer->refresh();
        $this->assertEquals(88, $beer->stock);
    }

    public function test_liquors_enforce_strictly_full_bottle_only(): void
    {
        $tenantService = new TenantService();
        $res = $tenantService->registerBusiness([
            'business_name' => 'Licorera Premium',
            'admin_name' => 'Admin Premium',
            'email' => 'premium@test.com',
            'pin' => '1234',
        ]);
        $tenant = $res['tenant'];
        $user = $res['user'];

        app(TenantContext::class)->setTenant($tenant);
        $cat = Category::where('tenant_id', $tenant->id)->first();

        // Liquor product (is_liquor = true)
        $whisky = Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $cat->id,
            'sku' => 'WHI-TEST-01',
            'name' => 'Whisky Buchanans 750ml',
            'price_buy' => 120000,
            'price_sell' => 180000,
            'stock' => 5,
            'stock_min' => 1,
            'base_unit' => 'botella',
            'is_beer' => false,
            'is_liquor' => true,
        ]);

        $inventoryService = new InventoryService();

        // Full bottle sale: succeeds
        $inventoryService->deductStock($whisky, 1, 1, 'sale', 'Venta botella', $user);
        $whisky->refresh();
        $this->assertEquals(4, $whisky->stock);

        // Fractional / multi presentation attempt: must throw Exception
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Los licores no admiten fraccionamiento ni presentaciones múltiples. Venta exclusiva por botella completa.");

        $inventoryService->deductStock($whisky, 1, 2, 'sale', 'Fraccionamiento inválido', $user);
    }

    public function test_cannot_sell_more_than_available_stock(): void
    {
        $tenantService = new TenantService();
        $res = $tenantService->registerBusiness([
            'business_name' => 'Licorera Stock',
            'admin_name' => 'Admin Stock',
            'email' => 'stock@test.com',
            'pin' => '1234',
        ]);
        $tenant = $res['tenant'];
        $user = $res['user'];

        app(TenantContext::class)->setTenant($tenant);
        $cat = Category::where('tenant_id', $tenant->id)->first();

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $cat->id,
            'sku' => 'RON-LAST-01',
            'name' => 'Último Ron Disponible',
            'price_buy' => 40000,
            'price_sell' => 70000,
            'stock' => 1,
            'stock_min' => 1,
            'base_unit' => 'botella',
        ]);

        $inventoryService = new InventoryService();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Stock insuficiente");

        // Requesting 2 units when only 1 is available
        $inventoryService->deductStock($product, 2, 1, 'sale', 'Sobreventa', $user);
    }
}
