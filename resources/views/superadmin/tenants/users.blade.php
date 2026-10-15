@extends('layouts.app', ['title' => 'Usuarios — ' . $tenant->name . ' — Superadmin'])

@section('content')
<div class="h-full flex flex-col space-y-4 max-w-6xl mx-auto" x-data="{
    showEditModal: false,
    showCreateModal: false,
    selectedUser: {
        id: null,
        name: '',
        email: '',
        phone: '',
        role: 'cashier',
        is_active: true,
        branch_ids: []
    },
    openEdit(user, role, isActive, branchIds) {
        this.selectedUser = {
            id: user.id,
            name: user.name,
            email: user.email,
            phone: user.phone || '',
            role: role,
            is_active: Boolean(isActive),
            branch_ids: branchIds || []
        };
        this.showEditModal = true;
    }
}">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('superadmin.index') }}" class="p-2.5 rounded-xl bg-white dark:bg-slate-850 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-800 transition-colors flex items-center justify-center shadow-xs">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <div class="flex items-center gap-2 mb-0.5">
                    <a href="{{ route('superadmin.index') }}" class="text-xs text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 font-medium transition-colors">
                        Empresas
                    </a>
                    <span class="text-xs text-slate-400">/</span>
                    <a href="{{ route('superadmin.tenants.edit', $tenant->id) }}" class="text-xs text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 font-medium transition-colors">
                        {{ $tenant->name }}
                    </a>
                    <span class="text-xs text-slate-400">/</span>
                    <span class="text-xs font-semibold text-slate-900 dark:text-white">Usuarios y Credenciales</span>
                </div>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span>Empleados y Credenciales: {{ $tenant->name }}</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700/80 text-[11px] font-semibold">
                        {{ $users->count() }} {{ $users->count() === 1 ? 'usuario' : 'usuarios' }}
                    </span>
                </h2>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('superadmin.tenants.edit', $tenant->id) }}" class="px-3.5 py-2 rounded-xl text-xs font-medium bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 shadow-xs transition-colors">
                Editar Empresa
            </a>
            <button type="button" @click="showCreateModal = true" class="px-3.5 py-2 rounded-xl text-xs font-medium bg-slate-900 hover:bg-slate-800 text-white dark:bg-slate-100 dark:hover:bg-white dark:text-slate-900 border border-slate-800 dark:border-white shadow-xs transition-colors flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Nuevo Usuario</span>
            </button>
        </div>
    </div>

    <!-- Users Table Card -->
    <div class="card-night p-4 flex-1 overflow-hidden flex flex-col border border-slate-200/80 dark:border-slate-800 shadow-xs">
        <div class="flex-1 overflow-y-auto">
            <div class="overflow-x-auto min-w-full">
                <table class="w-full text-left text-xs min-w-[750px]">
                    <thead class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider text-[11px]">
                        <tr>
                            <th class="py-3 px-3">Usuario / Nombre</th>
                            <th class="py-3 px-3">Correo (Login) / Teléfono</th>
                            <th class="py-3 px-3">Rol</th>
                            <th class="py-3 px-3">Sucursal(es)</th>
                            <th class="py-3 px-3">Estado</th>
                            <th class="py-3 px-3 text-right sticky right-0 bg-white dark:bg-slate-900 z-10 border-l border-slate-100 dark:border-slate-800 shadow-[-6px_0_10px_-2px_rgba(0,0,0,0.06)] dark:shadow-[-6px_0_10px_-2px_rgba(0,0,0,0.5)]">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($users as $u)
                            @php
                                $role = $u->getRoleInTenant($tenant->id) ?? 'cashier';
                                $pivotActive = $u->tenantUsers->where('tenant_id', $tenant->id)->first()?->is_active ?? $u->is_active;
                                $assignedBranchIds = $u->branches->where('tenant_id', $tenant->id)->pluck('id')->values()->toArray();
                            @endphp
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/50 transition-colors">
                                <td class="py-3 px-3">
                                    <div class="font-bold text-slate-900 dark:text-white text-sm">{{ $u->name }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">ID: #{{ $u->id }}</div>
                                </td>
                                <td class="py-3 px-3 text-slate-700 dark:text-slate-300">
                                    <div class="font-medium font-mono text-xs">{{ $u->email }}</div>
                                    <div class="text-[11px] text-slate-400 font-mono">{{ $u->phone ?: 'Sin teléfono' }}</div>
                                </td>
                                <td class="py-3 px-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        {{ strtoupper($role) }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-slate-600 dark:text-slate-400">
                                    @if($role === 'admin')
                                        <span class="text-[11px] text-slate-500 dark:text-slate-400">Todas las sedes</span>
                                    @else
                                        @php
                                            $userBranches = $u->branches->where('tenant_id', $tenant->id);
                                        @endphp
                                        @forelse($userBranches as $ub)
                                            <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 mr-1 mb-1 border border-slate-200/60 dark:border-slate-700/60">
                                                {{ $ub->name }}
                                            </span>
                                        @empty
                                            <span class="text-[11px] text-slate-400 italic">Sede Principal</span>
                                        @endforelse
                                    @endif
                                </td>
                                <td class="py-3 px-3">
                                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-medium border {{ $pivotActive ? 'bg-slate-50 dark:bg-slate-800/60 text-slate-700 dark:text-slate-200 border-slate-200 dark:border-slate-700' : 'bg-slate-100 dark:bg-slate-850 text-slate-400 dark:text-slate-500 border-slate-200 dark:border-slate-800' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $pivotActive ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                        <span>{{ $pivotActive ? 'Activo' : 'Suspendido' }}</span>
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right sticky right-0 bg-white dark:bg-slate-900 z-10 border-l border-slate-100 dark:border-slate-800 shadow-[-6px_0_10px_-2px_rgba(0,0,0,0.06)] dark:shadow-[-6px_0_10px_-2px_rgba(0,0,0,0.5)]">
                                    <button type="button"
                                            @click="openEdit({{ json_encode($u) }}, '{{ $role }}', {{ $pivotActive ? 'true' : 'false' }}, {{ json_encode($assignedBranchIds) }})"
                                            class="px-2.5 py-1.5 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium shadow-xs transition-colors">
                                        Modificar Credenciales
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 text-center text-slate-400">No hay usuarios vinculados a esta empresa.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal: Edit Credentials -->
    <div x-show="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs" style="display: none;">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 max-w-md w-full shadow-xl max-h-[92vh] overflow-y-auto" @click.away="showEditModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Modificar Credenciales</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400" x-text="selectedUser.name"></p>
                </div>
                <button type="button" @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form :action="'{{ url('/superadmin/tenants/' . $tenant->id . '/users') }}/' + selectedUser.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nombre Completo *</label>
                    <input type="text" name="name" x-model="selectedUser.name" required class="input-night w-full text-xs py-2">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Correo (Login) *</label>
                        <input type="email" name="email" x-model="selectedUser.email" required class="input-night w-full text-xs py-2 font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Teléfono</label>
                        <input type="text" name="phone" x-model="selectedUser.phone" class="input-night w-full text-xs py-2 font-mono">
                    </div>
                </div>

                <div class="p-3 bg-slate-50 dark:bg-slate-850 rounded-xl border border-slate-200/80 dark:border-slate-800 space-y-3">
                    <div class="text-[11px] font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                        Actualizar Claves de Acceso
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">
                            Nueva Contraseña <span class="text-[10px] text-slate-400">(dejar vacío para conservar la actual)</span>
                        </label>
                        <input type="password" name="password" minlength="6" placeholder="Mínimo 6 caracteres" class="input-night w-full text-xs py-2 font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">
                            Nuevo PIN de Desbloqueo <span class="text-[10px] text-slate-400">(4 a 6 dígitos numéricos)</span>
                        </label>
                        <input type="password" name="pin" maxlength="6" placeholder="Ej: 1234" class="input-night w-full text-xs py-2 font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Rol Operativo *</label>
                        <select name="role" x-model="selectedUser.role" required class="input-night w-full text-xs py-2 font-medium">
                            <option value="admin">Administrador (Total)</option>
                            <option value="cashier">Cajero (Ventas y Caja)</option>
                            <option value="waiter">Mesero (Mesas)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Estado del Acceso *</label>
                        <select name="is_active" :value="selectedUser.is_active ? '1' : '0'" class="input-night w-full text-xs py-2 font-medium">
                            <option value="1">Activo</option>
                            <option value="0">Suspendido / Desactivado</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Sucursales Asignadas</label>
                    <div class="space-y-1.5 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-200/80 dark:border-slate-800 max-h-32 overflow-y-auto">
                        @foreach($branches as $br)
                            <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" name="branch_ids[]" value="{{ $br->id }}" :checked="selectedUser.branch_ids.includes({{ $br->id }})" class="rounded border-slate-300 dark:border-slate-700 text-slate-900 focus:ring-slate-500">
                                <span>{{ $br->name }} {{ $br->is_main ? '(Principal)' : '' }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white dark:bg-slate-100 dark:hover:bg-white dark:text-slate-900 border border-slate-800 dark:border-white rounded-xl text-xs font-semibold shadow-xs transition-colors">
                        Guardar Credenciales
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Create New User -->
    <div x-show="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs" style="display: none;">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 max-w-md w-full shadow-xl max-h-[92vh] overflow-y-auto" @click.away="showCreateModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Registrar Nuevo Empleado</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Para: {{ $tenant->name }}</p>
                </div>
                <button type="button" @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form action="{{ route('superadmin.tenants.users.store', $tenant->id) }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nombre Completo *</label>
                    <input type="text" name="name" required placeholder="Ej: Diana Gómez" class="input-night w-full text-xs py-2">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Correo Electrónico (Login) *</label>
                        <input type="email" name="email" required placeholder="diana@empresa.com" class="input-night w-full text-xs py-2 font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Teléfono</label>
                        <input type="text" name="phone" placeholder="Ej: 300 123 4567" class="input-night w-full text-xs py-2 font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Contraseña de Acceso *</label>
                        <input type="password" name="password" required minlength="6" placeholder="Mínimo 6 caracteres" class="input-night w-full text-xs py-2 font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">PIN Rápido (4 a 6 dígitos)</label>
                        <input type="password" name="pin" maxlength="6" placeholder="Por defecto: 1234" class="input-night w-full text-xs py-2 font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Rol Operativo *</label>
                        <select name="role" required class="input-night w-full text-xs py-2 font-medium">
                            <option value="cashier">Cajero (Ventas y Caja)</option>
                            <option value="waiter">Mesero (Mesas y Cuentas)</option>
                            <option value="admin">Administrador (Total)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Estado *</label>
                        <select name="is_active" class="input-night w-full text-xs py-2 font-medium">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Sucursales Asignadas</label>
                    <div class="space-y-1.5 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-200/80 dark:border-slate-800 max-h-32 overflow-y-auto">
                        @foreach($branches as $br)
                            <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" name="branch_ids[]" value="{{ $br->id }}" {{ $br->is_main ? 'checked' : '' }} class="rounded border-slate-300 dark:border-slate-700 text-slate-900 focus:ring-slate-500">
                                <span>{{ $br->name }} {{ $br->is_main ? '(Principal)' : '' }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white dark:bg-slate-100 dark:hover:bg-white dark:text-slate-900 border border-slate-800 dark:border-white rounded-xl text-xs font-semibold shadow-xs transition-colors">
                        Registrar Empleado
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
