<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user || !$user->is_superadmin) {
            abort(403, 'Acceso restringido a superadministradores de la plataforma.');
        }

        return $next($request);
    }
}
