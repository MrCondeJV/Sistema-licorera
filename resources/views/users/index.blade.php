@extends('layouts.app', ['title' => 'Usuarios y Permisos'])

@section('content')
<div class="h-full flex flex-col space-y-4" x-data="{ showNewUserModal: false, showResetModal: false, resetUserId: null, resetUserName: '' }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-black text-white">Usuarios y Permisos de Empleados</h2>
            <p class="text-xs text-slate-400 mt-0.5">Gestión de administradores, cajeros, meseros y PINs seguros</p>
        </div>
        <button type="button" @click="showNewUserModal = true" class="btn-touch py-2 px-4 bg-gradient-to-r from-neon-green to-emerald-400 text-night-950 text-xs font-black shadow-lg shadow-neon-green/20">
            + NUEVO EMPLEADO
        </button>
    </div>

    <!-- Table -->
    <div class="card-night p-4 flex-1 overflow-hidden flex flex-col">
        <div class="flex-1 overflow-y-auto">
            <div class="overflow-x-auto min-w-full">
                <table class="w-full text-left text-xs min-w-[650px]">
                <thead class="border-b border-night-800 text-slate-400 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-2">Nombre</th>
                        <th class="py-3 px-2">Correo / Teléfono</th>
                        <th class="py-3 px-2">Rol en el Negocio</th>
                        <th class="py-3 px-2">Estado</th>
                        <th class="py-3 px-2 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-night-800">
                    @foreach($memberships as $m)
                        <tr>
                            <td class="py-3 px-2 font-bold text-white text-sm">{{ $m->user->name }}</td>
                            <td class="py-3 px-2 text-slate-300">
                                <div>{{ $m->user->email ?? 'Sin correo' }}</div>
                                <div class="text-[11px] text-slate-500 font-mono">{{ $m->user->phone ?? '' }}</div>
                            </td>
                            <td class="py-3 px-2">
                                <span class="badge-neon {{ $m->role === 'admin' ? 'bg-purple-950 text-purple-300 border-purple-700' : ($m->role === 'cashier' ? 'bg-emerald-950 text-emerald-300 border-emerald-700' : 'bg-cyan-950 text-cyan-300 border-cyan-700') }} text-[10px]">
                                    {{ strtoupper($m->role) }}
                                </span>
                            </td>
                            <td class="py-3 px-2">
                                <span class="badge-neon {{ $m->is_active ? 'bg-emerald-950 text-emerald-300 border-emerald-700' : 'bg-rose-950 text-rose-300 border-rose-700' }} text-[10px]">
                                    {{ $m->is_active ? 'Activo' : 'Desactivado' }}
                                </span>
                            </td>
                            <td class="py-3 px-2 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" @click="resetUserId = {{ $m->user->id }}; resetUserName = '{{ $m->user->name }}'; showResetModal = true"
                                            class="px-2.5 py-1.5 bg-night-800 hover:bg-night-750 text-neon-cyan border border-night-750 rounded-lg text-xs font-bold">
                                        Restablecer PIN
                                    </button>

                                    <form action="{{ route('users.toggle', $m->user->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1.5 bg-night-800 hover:bg-night-750 text-slate-400 hover:text-white border border-night-750 rounded-lg text-xs font-bold">
                                            {{ $m->is_active ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal: New User -->
    <div x-show="showNewUserModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" style="display: none;">
        <div class="bg-night-900 border border-night-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-black text-white mb-1">Registrar Empleado</h3>
            <p class="text-xs text-slate-400 mb-4">Se creará un acceso mediante PIN táctil</p>

            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Nombre Completo *</label>
                    <input type="text" name="name" required placeholder="Ej: Diana Gómez" autofocus
                           class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3 py-2 text-xs focus:border-neon-green focus:outline-none">
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Correo Electrónico</label>
                    <input type="email" name="email" placeholder="diana@licorera.pos"
                           class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3 py-2 text-xs focus:border-neon-green focus:outline-none">
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Rol Operativo *</label>
                    <select name="role" required class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3 py-2 text-xs focus:border-neon-green focus:outline-none">
                        <option value="cashier">Cajero (Ventas, Cobros y Caja)</option>
                        <option value="waiter">Mesero (Mesas y Cuentas)</option>
                        <option value="admin">Administrador (Control Total)</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">PIN de Acceso (4 dígitos) *</label>
                    <input type="password" name="pin" maxlength="4" pattern="\d{4}" inputmode="numeric" required placeholder="••••"
                           class="w-full bg-night-850 border border-night-750 text-white font-mono text-center text-lg font-bold rounded-xl px-4 py-2 focus:border-neon-green focus:outline-none">
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="showNewUserModal = false" class="btn-touch flex-1 bg-night-800 text-slate-300 text-xs font-bold">Cancelar</button>
                    <button type="submit" class="btn-touch flex-1 bg-neon-green text-night-950 text-xs font-black">CREAR</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Reset PIN -->
    <div x-show="showResetModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" style="display: none;">
        <div class="bg-night-900 border border-night-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-black text-white mb-1">Restablecer PIN</h3>
            <p class="text-xs text-slate-400 mb-4" x-text="'Para: ' + resetUserName"></p>

            <form :action="'/users/' + resetUserId + '/reset-pin'" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Nuevo PIN de 4 dígitos *</label>
                    <input type="password" name="new_pin" maxlength="4" pattern="\d{4}" inputmode="numeric" required placeholder="••••" autofocus
                           class="w-full bg-night-850 border border-night-750 text-white font-mono text-center text-xl font-bold rounded-xl px-4 py-3 focus:border-neon-green focus:outline-none">
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="showResetModal = false" class="btn-touch flex-1 bg-night-800 text-slate-300 text-xs font-bold">Cancelar</button>
                    <button type="submit" class="btn-touch flex-1 bg-neon-cyan text-night-950 text-xs font-black">CAMBIAR PIN</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
