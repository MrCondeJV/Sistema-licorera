<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Sale;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;

class SuperadminController extends Controller
{
    public function index()
    {
        $tenants = Tenant::with(['plan', 'branches', 'users'])->latest()->paginate(20);
        $totalTenants = Tenant::count();
        $activeTenants = Tenant::where('status', 'active')->count();
        $suspendedTenants = Tenant::where('status', 'suspended')->count();
        $totalUsers = User::count();
        $totalSalesCount = Sale::withoutGlobalScopes()->count();
        $totalVolume = Sale::withoutGlobalScopes()->where('status', 'completed')->sum('total');

        $plans = Plan::all();

        return view('superadmin.index', [
            'tenants' => $tenants,
            'totalTenants' => $totalTenants,
            'activeTenants' => $activeTenants,
            'suspendedTenants' => $suspendedTenants,
            'totalUsers' => $totalUsers,
            'totalSalesCount' => $totalSalesCount,
            'totalVolume' => $totalVolume,
            'plans' => $plans,
        ]);
    }

    public function toggleTenantStatus(Tenant $tenant)
    {
        $newStatus = $tenant->status === 'active' ? 'suspended' : 'active';
        $tenant->status = $newStatus;
        $tenant->save();

        AuditService::log('superadmin_tenant_status', 'Tenant', $tenant->id, null, ['status' => $newStatus], "Cambio de estado por superadministrador");

        $msg = $newStatus === 'active' ? 'activado' : 'suspendido';
        return back()->with('success', "Negocio '{$tenant->name}' {$msg} correctamente.");
    }

    public function updatePlan(Request $request, Tenant $tenant)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        $tenant->plan_id = $request->input('plan_id');
        $tenant->save();

        return back()->with('success', "Plan del negocio '{$tenant->name}' actualizado.");
    }
}
