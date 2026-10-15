<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\TenantService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BusinessRegistrationController extends Controller
{
    protected TenantService $tenantService;

    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    public function showForm()
    {
        $plans = Plan::where('is_active', true)->get();
        return view('auth.register-business', [
            'plans' => $plans,
        ]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'business_name' => 'required|string|max:150',
            'admin_name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'tax_id' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:200',
            'password' => 'required|string|min:6',
            'pin' => 'nullable|digits:4',
            'plan_slug' => 'nullable|string|exists:plans,slug',
        ]);

        if (empty($validated['pin'])) {
            $validated['pin'] = substr(preg_replace('/\D/', '', $validated['password']) . '1234', 0, 4);
        }

        $validated['trial_days'] = 14;
        $validated['subscription_status'] = 'trial';

        $result = $this->tenantService->registerBusiness($validated);

        // Auto login the newly registered admin
        Auth::login($result['user']);
        session([
            'active_tenant_id' => $result['tenant']->id,
            'active_branch_id' => $result['branch']->id,
            'screen_locked' => false,
            'last_activity_time' => time(),
        ]);

        return redirect()->route('pos.index')->with('success', "¡Bienvenido a LICORERA POS! Tu licorera '{$result['tenant']->name}' ha sido configurada con éxito con 14 días de prueba gratuita.");
    }
}
