<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\Sale;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\AuditService;
use App\Services\TenantService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SuperadminController extends Controller
{
    public function index()
    {
        $tenants = Tenant::with(['plan', 'branches', 'users', 'subscriptionPayments'])->latest()->paginate(20);
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

    public function create()
    {
        $plans = Plan::where('is_active', true)->get();
        return view('superadmin.tenants.create', compact('plans'));
    }

    public function store(Request $request, TenantService $tenantService)
    {
        $request->validate([
            'business_name' => 'required|string|max:120',
            'admin_name' => 'required|string|max:100',
            'email' => 'required|email|max:150',
            'phone' => 'nullable|string|max:20',
            'tax_id' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:190',
            'password' => 'required|string|min:6',
            'plan_id' => 'required|exists:plans,id',
        ], [
            'password.required' => 'La contraseña de acceso del administrador es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ]);

        $plan = Plan::findOrFail($request->input('plan_id'));

        $result = $tenantService->registerBusiness([
            'business_name' => $request->input('business_name'),
            'admin_name' => $request->input('admin_name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'tax_id' => $request->input('tax_id'),
            'address' => $request->input('address'),
            'pin' => $request->input('pin'),
            'password' => $request->input('password'),
            'plan_slug' => $plan->slug,
        ]);

        $tenant = $result['tenant'];
        $tenant->plan_id = $plan->id;
        $tenant->save();

        AuditService::log('superadmin_tenant_created', 'Tenant', $tenant->id, null, $tenant->toArray(), "Empresa creada por Superadministrador");

        return redirect()->route('superadmin.index')->with('success', "Empresa '{$tenant->name}' creada exitosamente.");
    }

    public function edit(Tenant $tenant)
    {
        $plans = Plan::all();
        $branches = $tenant->branches;
        $users = $tenant->users;

        return view('superadmin.tenants.edit', compact('tenant', 'plans', 'branches', 'users'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:20',
            'tax_id' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:190',
            'status' => 'required|in:active,suspended',
            'plan_id' => 'required|exists:plans,id',
        ]);

        $oldData = $tenant->toArray();

        $tenant->update($request->only([
            'name',
            'email',
            'phone',
            'tax_id',
            'address',
            'status',
            'plan_id',
        ]));

        AuditService::log('superadmin_tenant_updated', 'Tenant', $tenant->id, $oldData, $tenant->toArray(), "Empresa editada por Superadministrador");

        return redirect()->route('superadmin.index')->with('success', "Empresa '{$tenant->name}' actualizada correctamente.");
    }

    /**
     * List and manage users and credentials of a specific tenant.
     */
    public function tenantUsers(Tenant $tenant)
    {
        $tenant->load(['branches', 'users.branches']);
        $branches = $tenant->branches;
        $users = $tenant->users;

        return view('superadmin.tenants.users', compact('tenant', 'branches', 'users'));
    }

    /**
     * Register a new user for a specific tenant from Superadmin.
     */
    public function storeTenantUser(Request $request, Tenant $tenant)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:6',
            'pin' => 'nullable|string|min:4|max:6',
            'role' => 'required|in:admin,cashier,waiter',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'exists:branches,id',
            'is_active' => 'nullable',
        ], [
            'email.unique' => 'Ya existe un usuario con este correo electrónico.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'pin.min' => 'El PIN debe tener al menos 4 dígitos.',
        ]);

        $pin = $request->input('pin') ?: '1234';
        $isActive = $request->has('is_active') ? (bool) $request->input('is_active') : true;

        $user = User::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'password' => Hash::make($request->input('password')),
            'pin_hash' => Hash::make($pin),
            'is_superadmin' => false,
            'is_active' => $isActive,
        ]);

        // Attach tenant membership
        $user->tenants()->attach($tenant->id, [
            'role' => $request->input('role'),
            'is_active' => $isActive,
        ]);

        // Attach branches
        $branchIds = $request->input('branch_ids', []);
        if (empty($branchIds)) {
            $mainBranchId = $tenant->branches()->where('is_main', true)->value('id') ?: $tenant->branches()->value('id');
            if ($mainBranchId) {
                $branchIds = [$mainBranchId];
            }
        }
        foreach ($branchIds as $bId) {
            $user->branches()->attach($bId, ['tenant_id' => $tenant->id]);
        }

        AuditService::log(
            'superadmin_user_created',
            'User',
            $user->id,
            null,
            ['tenant' => $tenant->name, 'email' => $user->email, 'role' => $request->input('role')],
            "Superadministrador creó al usuario '{$user->name}' para la empresa '{$tenant->name}'"
        );

        return redirect()->route('superadmin.tenants.users', $tenant->id)
            ->with('success', "Usuario '{$user->name}' registrado exitosamente para {$tenant->name}.");
    }

    /**
     * Update user credentials (password, PIN, email, role, status) from Superadmin.
     */
    public function updateTenantUser(Request $request, Tenant $tenant, User $user)
    {
        // Ensure user belongs to this tenant
        if (!$tenant->users()->where('users.id', $user->id)->exists()) {
            abort(404, 'El usuario no pertenece a esta empresa.');
        }

        // Prevent modifying another superadmin through tenant users module
        if ($user->is_superadmin) {
            abort(403, 'No se puede modificar las credenciales de un superadministrador desde este módulo.');
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6',
            'pin' => 'nullable|string|min:4|max:6',
            'role' => 'required|in:admin,cashier,waiter',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'exists:branches,id',
            'is_active' => 'nullable',
        ], [
            'email.unique' => 'Ya existe un usuario con este correo electrónico.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'pin.min' => 'El PIN debe tener al menos 4 dígitos.',
        ]);

        $oldData = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->getRoleInTenant($tenant->id),
            'is_active' => $user->is_active,
        ];

        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->phone = $request->input('phone');

        if ($request->filled('password')) {
            $user->password = Hash::make($request->input('password'));
        }

        if ($request->filled('pin')) {
            $user->setPin($request->input('pin'));
        }

        $isActive = $request->has('is_active') ? (bool) $request->input('is_active') : true;
        $user->is_active = $isActive;
        $user->save();

        // Update tenant membership pivot
        $user->tenants()->updateExistingPivot($tenant->id, [
            'role' => $request->input('role'),
            'is_active' => $isActive,
        ]);

        // Sync branches for this tenant
        if ($request->has('branch_ids')) {
            $tenantBranchIds = $tenant->branches()->pluck('id')->toArray();
            $user->branches()->wherePivotIn('branch_id', $tenantBranchIds)->detach();

            $syncData = [];
            foreach ($request->input('branch_ids', []) as $bId) {
                if (in_array((int)$bId, $tenantBranchIds, true)) {
                    $syncData[$bId] = ['tenant_id' => $tenant->id];
                }
            }
            if (!empty($syncData)) {
                $user->branches()->attach($syncData);
            }
        }

        AuditService::log(
            'superadmin_user_credentials_updated',
            'User',
            $user->id,
            $oldData,
            [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $request->input('role'),
                'password_changed' => $request->filled('password'),
                'pin_changed' => $request->filled('pin'),
                'is_active' => $isActive,
            ],
            "Superadministrador modificó las credenciales y rol de '{$user->name}' en la empresa '{$tenant->name}'"
        );

        return redirect()->route('superadmin.tenants.users', $tenant->id)
            ->with('success', "Credenciales de '{$user->name}' actualizadas correctamente.");
    }

    /**
     * Enter / Impersonate a business as Superadmin
     */
    public function enter(Tenant $tenant)
    {
        session([
            'active_tenant_id' => $tenant->id,
            'superadmin_impersonating' => true,
        ]);

        $mainBranch = $tenant->branches()->where('is_main', true)->first() ?: $tenant->branches()->first();
        if ($mainBranch) {
            session(['active_branch_id' => $mainBranch->id]);
        }

        AuditService::log('superadmin_impersonation', 'Tenant', $tenant->id, null, null, "Superadministrador ingresó al panel de la empresa: {$tenant->name}");

        return redirect()->route('dashboard')->with('success', "Has ingresado a la gestión de '{$tenant->name}' como Superadministrador.");
    }

    /**
     * Return to Superadmin Panel from an active tenant
     */
    public function leave()
    {
        session()->forget(['active_tenant_id', 'active_branch_id', 'superadmin_impersonating']);

        return redirect()->route('superadmin.index')->with('success', 'Has regresado al Panel de Superadministrador.');
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

    public function audit(Request $request)
    {
        $tenants = Tenant::orderBy('name')->get();

        $query = AuditLog::withoutGlobalScopes()->with(['user', 'tenant'])->latest('created_at');

        // Filter: Specific Tenant
        if ($request->filled('tenant_id')) {
            if ($request->input('tenant_id') === 'platform') {
                $query->where(function ($q) {
                    $q->whereNull('tenant_id')->orWhere('action', 'like', 'superadmin_%');
                });
            } else {
                $query->where('tenant_id', $request->input('tenant_id'));
            }
        }

        // Filter: Only Superadmin Actions
        if ($request->boolean('only_superadmin')) {
            $query->where(function ($q) {
                $q->where('action', 'like', 'superadmin_%')
                  ->orWhereHas('user', fn($u) => $u->where('is_superadmin', true));
            });
        }

        // Filter: Action
        if ($request->filled('action')) {
            $query->where('action', $request->input('action'));
        }

        // Filter: Search
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('reason', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('tenant', function ($tq) use ($search) {
                      $tq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Filter: Date
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->input('date_from'))->startOfDay());
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->input('date_to'))->endOfDay());
        }

        $logs = $query->paginate(30)->withQueryString();

        return view('superadmin.audit', [
            'logs' => $logs,
            'tenants' => $tenants,
            'totalLogs' => AuditLog::withoutGlobalScopes()->count(),
            'superadminLogsCount' => AuditLog::withoutGlobalScopes()->where('action', 'like', 'superadmin_%')->count(),
        ]);
    }

    /**
     * Display all SaaS Subscription Plans.
     */
    public function plans()
    {
        $plans = Plan::withCount('tenants')->orderBy('price_monthly')->get();

        return view('superadmin.plans.index', [
            'plans' => $plans,
        ]);
    }

    /**
     * Store a new SaaS Plan.
     */
    public function storePlan(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'nullable|string|max:100|unique:plans,slug',
            'price_monthly' => 'required|numeric|min:0',
            'max_users' => 'required|integer|min:1',
            'max_branches' => 'required|integer|min:1',
            'max_products' => 'required|integer|min:10',
            'features' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
        }

        // Parse features from lines
        $features = [];
        if (!empty($validated['features'])) {
            $features = array_values(array_filter(array_map('trim', explode("\n", $validated['features']))));
        }

        $plan = Plan::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'price_monthly' => $validated['price_monthly'],
            'max_users' => $validated['max_users'],
            'max_branches' => $validated['max_branches'],
            'max_products' => $validated['max_products'],
            'features' => $features,
            'is_active' => $request->has('is_active') ? (bool) $request->input('is_active') : true,
        ]);

        AuditService::log(
            'superadmin_create_plan',
            'Plan',
            $plan->id,
            null,
            $plan->toArray(),
            "Superadmin creó el plan SaaS '{$plan->name}' con precio $" . number_format($plan->price_monthly) . " COP"
        );

        return redirect()->route('superadmin.plans.index')->with('success', "Plan '{$plan->name}' creado exitosamente.");
    }

    /**
     * Update an existing SaaS Plan.
     */
    public function updatePlanDetails(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'slug' => 'required|string|max:100|unique:plans,slug,' . $plan->id,
            'price_monthly' => 'required|numeric|min:0',
            'max_users' => 'required|integer|min:1',
            'max_branches' => 'required|integer|min:1',
            'max_products' => 'required|integer|min:10',
            'features' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $features = [];
        if (!empty($validated['features'])) {
            $features = array_values(array_filter(array_map('trim', explode("\n", $validated['features']))));
        }

        $oldValues = $plan->toArray();

        $plan->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'price_monthly' => $validated['price_monthly'],
            'max_users' => $validated['max_users'],
            'max_branches' => $validated['max_branches'],
            'max_products' => $validated['max_products'],
            'features' => $features,
            'is_active' => $request->has('is_active') ? (bool) $request->input('is_active') : true,
        ]);

        AuditService::log(
            'superadmin_update_plan',
            'Plan',
            $plan->id,
            $oldValues,
            $plan->toArray(),
            "Superadmin actualizó los parámetros del plan '{$plan->name}'"
        );

        return redirect()->route('superadmin.plans.index')->with('success', "Plan '{$plan->name}' actualizado exitosamente.");
    }

    /**
     * Toggle Plan active/inactive status.
     */
    public function togglePlan(Plan $plan)
    {
        $plan->is_active = !$plan->is_active;
        $plan->save();

        $statusStr = $plan->is_active ? 'activado' : 'desactivado';

        AuditService::log(
            'superadmin_toggle_plan',
            'Plan',
            $plan->id,
            null,
            ['is_active' => $plan->is_active],
            "Superadmin cambió el estado del plan '{$plan->name}' a {$statusStr}"
        );

        return redirect()->route('superadmin.plans.index')->with('success', "Plan '{$plan->name}' {$statusStr} exitosamente.");
    }

    /**
     * Display all SaaS Subscription Payments across all tenants with filters and KPIs.
     */
    public function payments(Request $request)
    {
        $tenants = Tenant::orderBy('name')->get();
        $plans = Plan::all();

        $query = SubscriptionPayment::withoutGlobalScopes()
            ->with(['tenant', 'plan'])
            ->latest('id');

        // Filter: Tenant
        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->input('tenant_id'));
        }

        // Filter: Status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter: Plan
        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->input('plan_id'));
        }

        // Filter: Date Range
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', Carbon::parse($request->input('date_from'))->startOfDay());
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', Carbon::parse($request->input('date_to'))->endOfDay());
        }

        // Filter: Search
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('wompi_transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('tenant', function ($tq) use ($search) {
                      $tq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // KPI metrics
        $totalRevenueCents = SubscriptionPayment::withoutGlobalScopes()
            ->where('status', 'APPROVED')
            ->sum('amount_in_cents');
        $totalRevenue = $totalRevenueCents / 100;

        $approvedCount = SubscriptionPayment::withoutGlobalScopes()
            ->where('status', 'APPROVED')
            ->count();

        $pendingCount = SubscriptionPayment::withoutGlobalScopes()
            ->where('status', 'PENDING')
            ->count();

        $declinedCount = SubscriptionPayment::withoutGlobalScopes()
            ->whereNotIn('status', ['APPROVED', 'PENDING'])
            ->count();

        $payments = $query->paginate(25)->withQueryString();

        return view('superadmin.payments.index', [
            'payments' => $payments,
            'tenants' => $tenants,
            'plans' => $plans,
            'totalRevenue' => $totalRevenue,
            'approvedCount' => $approvedCount,
            'pendingCount' => $pendingCount,
            'declinedCount' => $declinedCount,
        ]);
    }
}
