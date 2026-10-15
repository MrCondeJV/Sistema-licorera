<?php

namespace App\Http\Controllers;

use App\Models\TenantUser;
use App\Models\User;
use App\Services\AuditService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index()
    {
        $tenantId = app(\App\Services\TenantContext::class)->getTenantId();

        // Carlos Administrador or any tenant user can NEVER see or know of the platform superadmin
        $memberships = TenantUser::where('tenant_id', $tenantId)
            ->whereHas('user', function ($q) {
                $q->where('is_superadmin', false);
            })
            ->with(['user.branches'])
            ->get();

        $branches = \App\Models\Branch::where('tenant_id', $tenantId)->where('is_active', true)->get();

        return view('users.index', [
            'memberships' => $memberships,
            'branches' => $branches,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'role' => 'required|in:admin,cashier,waiter',
            'password' => 'required|string|min:6',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'exists:branches,id',
        ], [
            'email.required' => 'El correo electrónico es requerido para el inicio de sesión.',
            'email.unique' => 'Ya existe un usuario registrado con este correo.',
            'password.required' => 'La contraseña de acceso es requerida.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ]);

        $tenantId = app(\App\Services\TenantContext::class)->getTenantId();

        DB::transaction(function () use ($request, $tenantId) {
            $user = User::create([
                'name' => $request->input('name'),
                'email' => strtolower(trim($request->input('email'))),
                'phone' => $request->input('phone'),
                'password' => Hash::make($request->input('password')),
                'pin_hash' => Hash::make(Str::random(32)),
                'is_superadmin' => false,
                'is_active' => true,
            ]);

            TenantUser::create([
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'role' => $request->input('role'),
                'is_active' => true,
            ]);

            $branchIds = $request->input('branch_ids', []);
            if (empty($branchIds)) {
                // If not specified, attach to tenant's main branch
                $mainBranchId = \App\Models\Branch::where('tenant_id', $tenantId)->where('is_main', true)->value('id');
                if ($mainBranchId) {
                    $branchIds = [$mainBranchId];
                }
            }

            foreach ($branchIds as $bId) {
                $user->branches()->attach($bId, ['tenant_id' => $tenantId]);
            }

            AuditService::log('user_created', 'User', $user->id, null, ['name' => $user->name, 'role' => $request->input('role')], "Creación de empleado y credenciales");
        });

        return redirect()->route('users.index')->with('success', 'Empleado creado exitosamente con sus credenciales de acceso.');
    }

    public function resetPassword(Request $request, User $user)
    {
        // Tenant admin can NEVER modify or know about superadmin
        if ($user->is_superadmin) {
            abort(404);
        }

        $tenantId = app(\App\Services\TenantContext::class)->getTenantId();
        $membership = TenantUser::where('tenant_id', $tenantId)->where('user_id', $user->id)->first();
        if (!$membership) {
            abort(404);
        }

        $request->validate([
            'new_password' => 'required|string|min:6',
        ], [
            'new_password.required' => 'La nueva contraseña es requerida.',
            'new_password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ]);

        $user->password = Hash::make($request->input('new_password'));
        $user->save();

        AuditService::log('user_password_reset', 'User', $user->id, null, null, "Restablecimiento de contraseña");

        return back()->with('success', "Contraseña de {$user->name} actualizada correctamente.");
    }

    public function toggleStatus(User $user)
    {
        // Tenant admin can NEVER deactivate or know about superadmin
        if ($user->is_superadmin) {
            abort(404);
        }

        // A user cannot deactivate their own account
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'No puedes desactivar tu propia cuenta de usuario.']);
        }

        $tenantId = app(\App\Services\TenantContext::class)->getTenantId();
        $membership = TenantUser::where('tenant_id', $tenantId)->where('user_id', $user->id)->firstOrFail();

        $membership->is_active = !$membership->is_active;
        $membership->save();

        AuditService::log('user_status_toggled', 'TenantUser', $membership->id, null, ['is_active' => $membership->is_active], "Cambio de estado de empleado");

        $statusLabel = $membership->is_active ? 'activado' : 'desactivado';
        return back()->with('success', "Empleado {$user->name} {$statusLabel}.");
    }
}
