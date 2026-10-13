<?php

namespace App\Http\Controllers;

use App\Models\TenantUser;
use App\Models\User;
use App\Services\AuditService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $tenantId = app(\App\Services\TenantContext::class)->getTenantId();

        $memberships = TenantUser::where('tenant_id', $tenantId)
            ->with('user')
            ->get();

        return view('users.index', [
            'memberships' => $memberships,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:30',
            'role' => 'required|in:admin,cashier,waiter',
            'pin' => 'required|digits:4',
        ]);

        $tenantId = app(\App\Services\TenantContext::class)->getTenantId();

        DB::transaction(function () use ($request, $tenantId) {
            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'pin_hash' => Hash::make($request->input('pin')),
                'is_active' => true,
            ]);

            TenantUser::create([
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'role' => $request->input('role'),
                'is_active' => true,
            ]);

            AuditService::log('user_created', 'User', $user->id, null, ['name' => $user->name, 'role' => $request->input('role')], "Creación de empleado y asignación de PIN");
        });

        return redirect()->route('users.index')->with('success', 'Empleado creado exitosamente con su PIN de acceso.');
    }

    public function resetPin(Request $request, User $user)
    {
        $request->validate([
            'new_pin' => 'required|digits:4',
        ]);

        $user->pin_hash = Hash::make($request->input('new_pin'));
        $user->save();

        AuditService::log('user_pin_reset', 'User', $user->id, null, null, "Restablecimiento de PIN");

        return back()->with('success', "PIN de {$user->name} restablecido correctamente.");
    }

    public function toggleStatus(User $user)
    {
        $tenantId = app(\App\Services\TenantContext::class)->getTenantId();
        $membership = TenantUser::where('tenant_id', $tenantId)->where('user_id', $user->id)->firstOrFail();

        $membership->is_active = !$membership->is_active;
        $membership->save();

        AuditService::log('user_status_toggled', 'TenantUser', $membership->id, null, ['is_active' => $membership->is_active], "Cambio de estado de empleado");

        $statusLabel = $membership->is_active ? 'activado' : 'desactivado';
        return back()->with('success', "Empleado {$user->name} {$statusLabel}.");
    }
}
