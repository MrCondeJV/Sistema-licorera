<?php

namespace Tests\Feature;

use App\Models\CashRegister;
use App\Models\CashSession;
use App\Services\CashRegisterService;
use App\Services\TenantContext;
use App\Services\TenantService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_shift_open_and_close_with_blind_count(): void
    {
        $tenantService = new TenantService();
        $res = $tenantService->registerBusiness([
            'business_name' => 'Licorera Arqueo',
            'admin_name' => 'Admin Arqueo',
            'email' => 'arqueo@test.com',
            'pin' => '1234',
        ]);
        $tenant = $res['tenant'];
        $user = $res['user'];

        app(TenantContext::class)->setTenant($tenant);

        $register = CashRegister::where('tenant_id', $tenant->id)->firstOrFail();
        $cashService = new CashRegisterService();

        // 1. Open shift with $100.000 COP base
        $session = $cashService->openSession($register, $user, 100000);
        $this->assertEquals('open', $session->status);
        $this->assertEquals(100000, $session->opening_amount);

        // 2. Add cash movement in: +$20.000
        $cashService->recordMovement($session, 'in', 20000, 'Base adicional', $user);

        // 3. Add cash movement out: -$10.000
        $cashService->recordMovement($session, 'out', 10000, 'Retiro para cambio', $user);

        // Expected physical cash: 100.000 + 20.000 - 10.000 = 110.000
        $summary = $session->calculateCashSummary();
        $this->assertEquals(110000, $summary['expected_cash']);

        // 4. Close shift with physical counted cash: $105.000 (Faltante de $5.000)
        // With difference and explanation note:
        $session = $cashService->closeSession($session, 105000, 'Faltante de $5.000 justificado por billete deteriorado', $user);

        $this->assertEquals('closed', $session->status);
        $this->assertEquals(110000, $session->expected_cash);
        $this->assertEquals(105000, $session->counted_cash);
        $this->assertEquals(-5000, $session->difference);
    }
}
