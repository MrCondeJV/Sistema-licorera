<?php

namespace App\Http\Controllers;

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
     * Show PIN login screen.
     */
    public function showLogin(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $tenants = Tenant::where('status', 'active')->orderBy('name')->get();
        $selectedTenantId = $request->query('tenant') ?? session('active_tenant_id') ?? $tenants->first()?->id;
        $selectedTenant = $selectedTenantId ? Tenant::find($selectedTenantId) : null;

        return view('auth.login', [
            'tenants' => $tenants,
            'selectedTenant' => $selectedTenant,
        ]);
    }

    /**
     * Authenticate via 4-digit PIN.
     */
    public function loginWithPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|digits:4',
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        $pin = $request->input('pin');
        $tenantId = (int) $request->input('tenant_id');
        $ip = $request->ip();

        // Rate limiter key based on IP and tenant
        $throttleKey = "login_pin:{$ip}:{$tenantId}";

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            LoginAttempt::create([
                'tenant_id' => $tenantId,
                'identifier' => "PIN:{$pin}",
                'ip_address' => $ip,
                'successful' => false,
            ]);

            return back()->withErrors([
                'pin' => "Demasiados intentos fallidos. Sistema bloqueado temporalmente por {$seconds} segundos.",
            ]);
        }

        $tenant = Tenant::findOrFail($tenantId);

        if (!$tenant->isActive()) {
            return back()->withErrors(['pin' => 'Este negocio se encuentra suspendido.']);
        }

        // Find users with active membership in this tenant
        $activeMemberships = TenantUser::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->with('user')
            ->get();

        $authenticatedUser = null;
        foreach ($activeMemberships as $membership) {
            $candidate = $membership->user;
            if ($candidate && $candidate->is_active && Hash::check($pin, $candidate->pin_hash)) {
                $authenticatedUser = $candidate;
                break;
            }
        }

        // Check if superadmin is logging in with master PIN
        if (!$authenticatedUser) {
            $superAdmins = User::where('is_superadmin', true)->where('is_active', true)->get();
            foreach ($superAdmins as $sa) {
                if (Hash::check($pin, $sa->pin_hash)) {
                    $authenticatedUser = $sa;
                    break;
                }
            }
        }

        if (!$authenticatedUser) {
            RateLimiter::hit($throttleKey, 900); // 15 minute lockout on 5 strikes

            LoginAttempt::create([
                'tenant_id' => $tenant->id,
                'identifier' => "FAILED_PIN",
                'ip_address' => $ip,
                'successful' => false,
            ]);

            return back()->withErrors(['pin' => 'PIN incorrecto o usuario no asignado a este negocio.']);
        }

        RateLimiter::clear($throttleKey);

        Auth::login($authenticatedUser);

        session([
            'active_tenant_id' => $tenant->id,
            'screen_locked' => false,
            'last_activity_time' => time(),
        ]);

        app(TenantContext::class)->setTenant($tenant);

        LoginAttempt::create([
            'tenant_id' => $tenant->id,
            'user_id' => $authenticatedUser->id,
            'identifier' => "USER:{$authenticatedUser->id}",
            'ip_address' => $ip,
            'successful' => true,
        ]);

        return redirect()->intended(route('pos.index'));
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
     * Unlock screen with current user's PIN without resetting session state.
     */
    public function unlockWithPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|digits:4',
        ]);

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        if (Hash::check($request->input('pin'), $user->pin_hash)) {
            session([
                'screen_locked' => false,
                'last_activity_time' => time(),
            ]);

            return redirect()->intended(route('pos.index'));
        }

        return back()->withErrors(['pin' => 'PIN incorrecto.']);
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

        session([
            'active_tenant_id' => $tenant->id,
            'active_branch_id' => null,
        ]);

        return redirect()->route('dashboard')->with('success', "Cambiado a {$tenant->name}");
    }
}
