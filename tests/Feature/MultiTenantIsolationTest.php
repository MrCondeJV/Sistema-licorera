<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantContext;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_a_cannot_see_or_query_tenant_b_products(): void
    {
        $tenantService = new TenantService();

        // 1. Create Tenant A
        $resA = $tenantService->registerBusiness([
            'business_name' => 'Licorera Poblado',
            'admin_name' => 'Admin Poblado',
            'email' => 'poblado@test.com',
            'pin' => '1111',
        ]);
        $tenantA = $resA['tenant'];

        // 2. Create Tenant B
        $resB = $tenantService->registerBusiness([
            'business_name' => 'Licorera Laureles',
            'admin_name' => 'Admin Laureles',
            'email' => 'laureles@test.com',
            'pin' => '2222',
        ]);
        $tenantB = $resB['tenant'];

        // In Tenant A context: create a product
        $context = app(TenantContext::class);
        $context->setTenant($tenantA);

        $catA = Category::where('tenant_id', $tenantA->id)->first();
        $productA = Product::create([
            'tenant_id' => $tenantA->id,
            'category_id' => $catA->id,
            'sku' => 'CER-POBLADO-01',
            'name' => 'Cerveza Exclusiva Poblado',
            'price_buy' => 2000,
            'price_sell' => 5000,
            'stock' => 50,
            'stock_min' => 5,
            'base_unit' => 'unidad',
        ]);

        // In Tenant B context: create a product
        $context->setTenant($tenantB);
        $catB = Category::where('tenant_id', $tenantB->id)->first();
        $productB = Product::create([
            'tenant_id' => $tenantB->id,
            'category_id' => $catB->id,
            'sku' => 'CER-LAURELES-01',
            'name' => 'Cerveza Exclusiva Laureles',
            'price_buy' => 2200,
            'price_sell' => 5500,
            'stock' => 40,
            'stock_min' => 5,
            'base_unit' => 'unidad',
        ]);

        // 3. Verify Isolation from Tenant A perspective
        $context->setTenant($tenantA);
        $pobladoProducts = Product::all();

        $this->assertTrue($pobladoProducts->contains('id', $productA->id));
        $this->assertFalse($pobladoProducts->contains('id', $productB->id));
        $this->assertNull(Product::find($productB->id), 'Tenant A must not be able to find Tenant B product by ID');

        // 4. Verify Isolation from Tenant B perspective
        $context->setTenant($tenantB);
        $laurelesProducts = Product::all();

        $this->assertTrue($laurelesProducts->contains('id', $productB->id));
        $this->assertFalse($laurelesProducts->contains('id', $productA->id));
        $this->assertNull(Product::find($productA->id), 'Tenant B must not be able to find Tenant A product by ID');
    }
}
