<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\Branch;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = app(TenantContext::class);

        // If route is superadmin, bypass tenant requirement
        if ($request->is('superadmin*')) {
            $context->setBypassScope(true);
            return $next($request);
        }

        $user = auth()->user();

        // If not authenticated, let it proceed to login routes
        if (!$user) {
            return $next($request);
        }

        $tenantId = session('active_tenant_id');

        if (!$tenantId) {
            // Find user's first active tenant
            $membership = $user->tenantUsers()->where('is_active', true)->with('tenant')->first();

            if ($membership && $membership->tenant) {
                $tenantId = $membership->tenant_id;
                session(['active_tenant_id' => $tenantId]);
            } elseif ($user->is_superadmin) {
                $firstTenant = Tenant::first();
                if ($firstTenant) {
                    $tenantId = $firstTenant->id;
                    session(['active_tenant_id' => $tenantId]);
                }
            }
        }

        if ($tenantId) {
            // Validate user actually has an active membership in this tenant
            $hasAccess = $user->is_superadmin || $user->tenantUsers()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->exists();

            if (!$hasAccess) {
                session()->forget(['active_tenant_id', 'active_branch_id']);
                abort(403, 'No tienes acceso a este negocio o tu membresía ha sido desactivada.');
            }

            $tenant = Tenant::find($tenantId);

            if (!$tenant) {
                session()->forget(['active_tenant_id', 'active_branch_id']);
                abort(404, 'Negocio no encontrado.');
            }

            if (!$tenant->isActive() && !$user->is_superadmin) {
                abort(403, 'El acceso a este negocio se encuentra suspendido. Comuníquese con soporte.');
            }

            $context->setTenant($tenant);

            // Resolve branch with user authorization
            $branchId = session('active_branch_id');
            $branch = null;
            if ($branchId) {
                $branchCandidate = Branch::where('tenant_id', $tenant->id)->where('is_active', true)->find($branchId);
                if ($branchCandidate && $user->canAccessBranch($branchCandidate)) {
                    $branch = $branchCandidate;
                }
            }

            if (!$branch) {
                // Find first branch user is authorized to access
                $userRole = $user->getRoleInTenant($tenant->id);
                if ($user->is_superadmin || $userRole === 'admin') {
                    $branch = Branch::where('tenant_id', $tenant->id)->where('is_main', true)->where('is_active', true)->first()
                           ?? Branch::where('tenant_id', $tenant->id)->where('is_active', true)->first();
                } else {
                    $branch = $user->branches()->where('branches.tenant_id', $tenant->id)->where('branches.is_active', true)->first()
                           ?? Branch::where('tenant_id', $tenant->id)->where('is_main', true)->where('is_active', true)->first()
                           ?? Branch::where('tenant_id', $tenant->id)->where('is_active', true)->first();
                }

                if ($branch) {
                    session(['active_branch_id' => $branch->id]);
                }
            }
            $context->setBranch($branch);

            $accessibleTenants = $user->is_superadmin
                ? Tenant::where('status', 'active')->orderBy('name')->get()
                : $user->tenants()->where('status', 'active')->orderBy('name')->get();

            $userRole = $user->getRoleInTenant($tenant->id);
            $accessibleBranches = ($user->is_superadmin || $userRole === 'admin')
                ? Branch::where('tenant_id', $tenant->id)->where('is_active', true)->orderByDesc('is_main')->orderBy('name')->get()
                : $user->branches()->where('branches.tenant_id', $tenant->id)->where('branches.is_active', true)->orderByDesc('is_main')->orderBy('name')->get();

            // Share current tenant & user role with all Blade views
            view()->share('currentTenant', $tenant);
            view()->share('currentBranch', $branch);
            view()->share('currentUserRole', $userRole);
            view()->share('accessibleTenants', $accessibleTenants);
            view()->share('accessibleBranches', $accessibleBranches);
        }

        $response = $next($request);

        // Prevent browsers and proxies from caching authenticated pages across tenants/refreshes
        $response->headers->set('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sun, 02 Jan 1990 00:00:00 GMT');

        return $response;
    }
}
