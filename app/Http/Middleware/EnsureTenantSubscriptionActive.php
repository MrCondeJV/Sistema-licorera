<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantSubscriptionActive
{
    /**
     * Handle an incoming request.
     * Enforces the 5-day grace period for SaaS tenants.
     * If more than 5 days have passed since subscription/trial expiration,
     * block access to all operational routes and redirect to immediate payment.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Unauthenticated or Superadmins are exempt
        if (!$user || $user->is_superadmin) {
            return $next($request);
        }

        // Allowed routes during suspension (Billing, Unlock, Logout)
        if (
            $request->routeIs('billing.*') ||
            $request->routeIs('logout') ||
            $request->routeIs('unlock*') ||
            $request->routeIs('wompi.webhook') ||
            $request->routeIs('superadmin.*')
        ) {
            return $next($request);
        }

        $tenantId = app(TenantContext::class)->getTenantId()
            ?? session('active_tenant_id')
            ?? $user->tenants()->first()?->id;

        if (!$tenantId) {
            return $next($request);
        }

        /** @var Tenant|null $tenant */
        $tenant = Tenant::find($tenantId);

        if ($tenant && $tenant->isGracePeriodExceeded()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Acceso suspendido: Han transcurrido más de 5 días desde el vencimiento de tu membresía. Realiza tu pago inmediato con Wompi para reactivar el servicio.',
                    'subscription_locked' => true,
                    'redirect_url' => route('billing.index'),
                ], 403);
            }

            return redirect()->route('billing.index')->with('error', '🚨 Servicio Suspendido: Han transcurrido más de 5 días desde la fecha de corte de tu cuenta sin registrar el pago de renovación. Realiza el pago a continuación para reactivar el sistema de inmediato.');
        }

        return $next($request);
    }
}
