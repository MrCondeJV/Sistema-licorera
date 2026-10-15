<?php

namespace Tests\Feature;

use App\Models\CashRegister;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Services\CashRegisterService;
use App\Services\TenantContext;
use App\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosSaleReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_checkout_returns_receipt_url_and_receipt_view_renders_correctly(): void
    {
        $tenantService = new TenantService();
        $res = $tenantService->registerBusiness([
            'business_name' => 'Licorera Ticket Test',
            'admin_name' => 'Admin Ticket',
            'email' => 'ticket@test.com',
            'pin' => '1234',
        ]);
        $tenant = $res['tenant'];
        $user = $res['user'];

        app(TenantContext::class)->setTenant($tenant);

        // Open cash session
        $register = CashRegister::where('tenant_id', $tenant->id)->firstOrFail();
        $session = (new CashRegisterService())->openSession($register, $user, 50000);

        // Create product
        $cat = Category::where('tenant_id', $tenant->id)->first();
        $beer = Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $cat->id,
            'sku' => 'CER-TICKET-01',
            'name' => 'Cerveza Club Colombia',
            'price_buy' => 3000,
            'price_sell' => 6000,
            'stock' => 20,
            'stock_min' => 2,
            'base_unit' => 'unidad',
        ]);

        $cashMethod = PaymentMethod::where('tenant_id', $tenant->id)->where('code', 'cash')->firstOrFail();

        // Perform POS checkout via POST /pos/checkout
        $response = $this->actingAs($user)->postJson(route('pos.checkout'), [
            'items' => [
                [
                    'product_id' => $beer->id,
                    'quantity' => 2,
                    'product_presentation_id' => null,
                ]
            ],
            'charge_to_debt' => false,
            'payments' => [
                [
                    'payment_method_id' => $cashMethod->id,
                    'amount' => 12000,
                    'received_amount' => 20000,
                    'change_amount' => 8000,
                ]
            ],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);
        $data = $response->json();
        $this->assertArrayHasKey('receipt_url', $data);
        $this->assertNotEmpty($data['receipt_url']);
        $this->assertArrayHasKey('whatsapp_url', $data);
        $this->assertStringContainsString('whatsapp', $data['whatsapp_url']);
        $this->assertArrayHasKey('whatsapp_message', $data);
        $this->assertStringContainsString('Cerveza Club Colombia', $data['whatsapp_message']);

        // Check that receipt page loads correctly with WhatsApp button
        $receiptResponse = $this->actingAs($user)->get($data['receipt_url']);
        $receiptResponse->assertStatus(200);
        $receiptResponse->assertSee('COMPROBANTE');
        $receiptResponse->assertSee('Cerveza Club Colombia');
        $receiptResponse->assertSee('autoprint');
        $receiptResponse->assertSee('ENVIAR POR WHATSAPP');
    }
}
