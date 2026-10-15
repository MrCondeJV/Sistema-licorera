<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePinAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'No autenticado'], 401);
            }
            return redirect()->route('login');
        }

        if (!$user->is_active) {
            auth()->logout();
            session()->flush();
            return redirect()->route('login')->withErrors(['pin' => 'Tu usuario se encuentra desactivado.']);
        }

        // Superadmin in platform area is not subjected to POS touch screen lock
        if ($user->is_superadmin && !session('superadmin_impersonating')) {
            session(['last_activity_time' => time(), 'screen_locked' => false]);
            return $next($request);
        }

        // Screen lock check
        if (session('screen_locked', false) && !$request->routeIs('unlock*') && !$request->routeIs('logout')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Pantalla bloqueada por inactividad', 'locked' => true], 423);
            }
            return redirect()->route('unlock');
        }

        // Inactivity timeout check (default 15 minutes, or configured in tenant settings)
        $lastActivity = session('last_activity_time');
        $lockMinutes = (int) (session('lock_timeout_minutes') ?: 15);

        if ($lastActivity && (time() - $lastActivity) > ($lockMinutes * 60) && !$request->routeIs('unlock*') && !$request->routeIs('logout')) {
            session(['screen_locked' => true]);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Pantalla bloqueada por inactividad', 'locked' => true], 423);
            }
            return redirect()->route('unlock');
        }

        session(['last_activity_time' => time()]);

        return $next($request);
    }
}
