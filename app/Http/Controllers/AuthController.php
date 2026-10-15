<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\LoginAttempt;
use App\Models\Tenant;
use App\Models\TenantUser;
use App\Models\User;
use App\Services\AuditService;
use App\Services\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    /**
     * Show login screen.
     */
    public function showLogin(Request $request)
    {
        return view('auth.login');
    }

    /**
     * Authenticate via standard Email and Password credentials.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email|string',
            'password' => 'required|string',
        ], [
            'email.required' => 'Por favor ingresa tu correo electrónico.',
            'email.email' => 'Por favor ingresa un correo electrónico válido.',
            'password.required' => 'Por favor ingresa tu contraseña.',
        ]);

        $ip = $request->ip();
        $email = trim(strtolower($request->input('email')));
        $throttleKey = 'login_email:' . $ip . ':' . $email;

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withInput($request->only('email', 'remember'))->withErrors([
                'email' => "Demasiados intentos fallidos. Por seguridad, espera {$seconds} segundos.",
            ]);
        }

        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            RateLimiter::hit($throttleKey, 900);

            LoginAttempt::create([
                'tenant_id' => null,
                'identifier' => "EMAIL:{$email}",
                'ip_address' => $ip,
                'successful' => false,
            ]);

            return back()->withInput($request->only('email', 'remember'))->withErrors([
                'email' => 'Las credenciales proporcionadas no son correctas.',
            ]);
        }

        if (!$user->is_active) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Tu cuenta de usuario se encuentra inactiva.',
            ]);
        }

        RateLimiter::clear($throttleKey);
        Auth::login($user, $request->boolean('remember'));

        session([
            'screen_locked' => false,
            'last_activity_time' => time(),
        ]);

        // Platform Superadministrator
        if ($user->is_superadmin) {
            LoginAttempt::create([
                'tenant_id' => null,
                'user_id' => $user->id,
                'identifier' => "SUPERADMIN:{$user->email}",
                'ip_address' => $ip,
                'successful' => true,
            ]);

            return redirect()->intended(route('superadmin.index'));
        }

        // Regular Tenant User (Admin, Cashier, Waiter)
        $membership = $user->tenantUsers()->where('is_active', true)->with('tenant')->first();

        if (!$membership || !$membership->tenant) {
            Auth::logout();
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'No tienes un negocio activo asignado a tu usuario.',
            ]);
        }

        $tenant = $membership->tenant;
        if (!$tenant->isActive()) {
            Auth::logout();
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'El negocio asignado se encuentra suspendido.',
            ]);
        }

        session(['active_tenant_id' => $tenant->id]);
        app(TenantContext::class)->setTenant($tenant);

        LoginAttempt::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'identifier' => "USER:{$user->id}",
            'ip_address' => $ip,
            'successful' => true,
        ]);

        if (in_array($membership->role, ['cashier', 'waiter'])) {
            return redirect()->intended(route('pos.index'));
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Authenticate via 4-digit PIN (Disabled in production for multi-tenant security).
     */
    public function loginWithPin(Request $request)
    {
        return redirect()->route('login')->withErrors([
            'email' => 'El inicio de sesión mediante PIN directo ha sido deshabilitado. Por favor ingrese con su correo electrónico y contraseña.',
        ]);
    }

    /**
     * Show touch screen lockscreen.
     */
    public function showUnlock()
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $tenantId = session('active_tenant_id');
        $tenant = $tenantId ? Tenant::find($tenantId) : null;

        return view('auth.unlock', [
            'user' => $user,
            'tenant' => $tenant,
        ]);
    }

    /**
     * Unlock screen with current user's password without resetting session state.
     */
    public function unlock(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ], [
            'password.required' => 'Por favor ingresa tu contraseña para desbloquear la sesión.',
        ]);

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        if (Hash::check($request->input('password'), $user->password)) {
            session([
                'screen_locked' => false,
                'last_activity_time' => time(),
            ]);

            if ($user->is_superadmin && !session('superadmin_impersonating')) {
                return redirect()->intended(route('superadmin.index'));
            }

            return redirect()->intended(route('pos.index'));
        }

        return back()->withErrors(['password' => 'Contraseña incorrecta.']);
    }

    /**
     * Logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Switch active business if user belongs to multiple tenants.
     */
    public function switchTenant(Request $request, Tenant $tenant)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $hasAccess = $user->is_superadmin || $user->tenantUsers()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->exists();

        if (!$hasAccess) {
            abort(403, 'No tienes acceso a este negocio.');
        }

        $sessionData = [
            'active_tenant_id' => $tenant->id,
            'active_branch_id' => null,
        ];

        if ($user->is_superadmin) {
            $sessionData['superadmin_impersonating'] = true;
        }

        session($sessionData);

        return back()->with('success', "Cambiado a {$tenant->name}");
    }

    /**
     * Switch active branch for authorized user within the current tenant.
     */
    public function switchBranch(Request $request, Branch $branch)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $tenantId = session('active_tenant_id') ?? app(TenantContext::class)->getTenantId();

        if ($branch->tenant_id !== $tenantId) {
            abort(403, 'No tienes acceso a una sucursal de otro negocio.');
        }

        if (!$branch->is_active) {
            abort(403, 'La sucursal seleccionada se encuentra inactiva.');
        }

        if (!$user->canAccessBranch($branch)) {
            abort(403, 'No tienes autorización para operar en esta sucursal.');
        }

        session(['active_branch_id' => $branch->id]);
        app(TenantContext::class)->setBranch($branch);

        return back()->with('success', "Cambiado a sucursal {$branch->name}");
    }
}
