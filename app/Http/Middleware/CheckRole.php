<?php

namespace App\Http\Middleware;

use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->is_superadmin) {
            return $next($request);
        }

        $context = app(TenantContext::class);
        $tenantId = $context->getTenantId() ?: session('active_tenant_id');

        if (!$tenantId || !$user->hasRoleInTenant($tenantId, $roles)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No tienes permisos suficientes para realizar esta acción.'], 403);
            }
            abort(403, 'Acceso denegado: tu rol actual no tiene autorización para esta sección.');
        }

        return $next($request);
    }
}
