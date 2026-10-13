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
     * Authenticate via standard Email and Password credentials.
     */
    public function login(Request $request)
    {
        // If request is from PIN keypad, delegate to loginWithPin
        if ($request->filled('pin') && !$request->filled('password')) {
            return $this->loginWithPin($request);
        }

        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ], [
            'email.required' => 'Por favor ingresa tu correo electrónico.',
            'password.required' => 'Por favor ingresa tu contraseña.',
        ]);

        $ip = $request->ip();
        $email = trim($request->input('email'));
        $throttleKey = 'login_email:' . $ip . ':' . strtolower($email);

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
     * Authenticate via 4-digit PIN.
     */
    public function loginWithPin(Request $request)
    {
        $request->validate([
            'pin' => 'required|digits:4',
            'tenant_id' => 'nullable|exists:tenants,id',
        ], [
            'pin.required' => 'Por favor ingresa tu PIN de 4 dígitos.',
            'pin.digits' => 'El PIN debe ser exactamente de 4 números.',
            'tenant_id.exists' => 'La licorera seleccionada no es válida.',
        ]);

        $pin = $request->input('pin');
        $tenantId = $request->input('tenant_id') ? (int) $request->input('tenant_id') : null;
        $ip = $request->ip();

        // Check first if PIN belongs to a platform Superadministrator
        $superAdmin = User::where('is_superadmin', true)->where('is_active', true)->get()
            ->first(fn($sa) => Hash::check($pin, $sa->pin_hash));

        if ($superAdmin) {
            Auth::login($superAdmin);

            session([
                'screen_locked' => false,
                'last_activity_time' => time(),
            ]);

            // If a specific tenant was chosen, associate context; otherwise go straight to SaaS dashboard
            if ($tenantId) {
                $tenant = Tenant::find($tenantId);
                if ($tenant) {
                    session(['active_tenant_id' => $tenant->id]);
                    app(TenantContext::class)->setTenant($tenant);
                }
            }

            return redirect()->intended(route('superadmin.index'));
        }

        // If not superadmin, a valid tenant is strictly required
        if (!$tenantId) {
            return back()->withErrors([
                'pin' => 'Por favor selecciona la licorera a la que perteneces para ingresar.',
            ]);
        }

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
            return back()->withErrors(['pin' => 'Este negocio se encuentra suspendido. Comuníquese con el administrador.']);
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
