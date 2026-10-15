@extends('layouts.app', ['title' => 'Usuarios y Permisos'])

@section('content')
<div class="h-full flex flex-col space-y-4" x-data="{ showNewUserModal: false, showPasswordModal: false, resetUserId: null, resetUserName: '' }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-black text-slate-900 dark:text-white">Usuarios y Permisos de Empleados</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Gestión de administradores, cajeros y meseros de la licorera</p>
        </div>
        <button type="button" @click="showNewUserModal = true" class="btn-touch py-2 px-4 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-lg shadow-emerald-600/30 rounded-xl">
            + NUEVO EMPLEADO
        </button>
    </div>

    <!-- Table -->
    <div class="card-night p-4 flex-1 overflow-hidden flex flex-col">
        <div class="flex-1 overflow-y-auto">
            <div class="overflow-x-auto min-w-full">
                <table class="w-full text-left text-xs min-w-[650px]">
                <thead class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-2">Nombre</th>
                        <th class="py-3 px-2">Correo / Teléfono</th>
                        <th class="py-3 px-2">Rol en el Negocio</th>
                        <th class="py-3 px-2">Estado</th>
                        <th class="py-3 px-2 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($memberships as $m)
                        <tr>
                            <td class="py-3 px-2 font-bold text-slate-900 dark:text-white text-sm">{{ $m->user->name }}</td>
                            <td class="py-3 px-2 text-slate-600 dark:text-slate-300">
                                <div>{{ $m->user->email ?? 'Sin correo' }}</div>
                                <div class="text-[11px] text-slate-400 dark:text-slate-500 font-mono">{{ $m->user->phone ?? '' }}</div>
                            </td>
                            <td class="py-3 px-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $m->role === 'admin' ? 'bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-700' : ($m->role === 'cashier' ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700' : 'bg-cyan-100 dark:bg-cyan-950 text-cyan-700 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-700') }}">
                                    {{ strtoupper($m->role) }}
                                </span>
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @if($m->role === 'admin')
                                        <span class="text-[10px] text-slate-400 font-semibold">Todas las sedes</span>
                                    @else
                                        @forelse($m->user->branches as $ub)
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                                                {{ $ub->name }}
                                            </span>
                                        @empty
                                            <span class="text-[10px] text-slate-400 italic">Sede Principal</span>
                                        @endforelse
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $m->is_active ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700' : 'bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-700' }}">
                                    {{ $m->is_active ? 'Activo' : 'Desactivado' }}
                                </span>
                            </td>
                            <td class="py-3 px-2 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" @click="resetUserId = {{ $m->user->id }}; resetUserName = '{{ $m->user->name }}'; showPasswordModal = true"
                                            class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-emerald-600 dark:text-emerald-400 border border-slate-200 dark:border-slate-750 rounded-lg text-xs font-bold transition-colors">
                                        Cambiar Clave
                                    </button>

                                    @if($m->user->id !== auth()->id())
                                        <form action="{{ route('users.toggle', $m->user->id) }}" method="POST"
                                              data-confirm="¿Deseas {{ $m->is_active ? 'desactivar el acceso' : 'reactivar el acceso' }} para {{ $m->user->name }}?"
                                              data-confirm-title="{{ $m->is_active ? 'Desactivar Empleado' : 'Activar Empleado' }}"
                                              data-confirm-btn="Sí, {{ $m->is_active ? 'desactivar' : 'activar' }}"
                                              data-confirm-danger="{{ $m->is_active ? 'true' : 'false' }}">
                                            @csrf
                                            <button type="submit" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white border border-slate-200 dark:border-slate-750 rounded-lg text-xs font-bold transition-colors">
                                                {{ $m->is_active ? 'Desactivar' : 'Activar' }}
                                            </button>
                                        </form>
                                    @else
                                        <span class="px-2 py-1 text-[11px] text-slate-400 italic">Sesión actual</span>
                                    @endif
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
    <div x-show="showNewUserModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" style="display: none;">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-black text-slate-900 dark:text-white mb-1">Registrar Empleado</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Acceso mediante correo electrónico y contraseña</p>

            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Nombre Completo *</label>
                    <input type="text" name="name" required placeholder="Ej: Diana Gómez" autofocus
                           class="input-night w-full text-xs">
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Correo Electrónico (Login) *</label>
                    <input type="email" name="email" required placeholder="diana@miempresa.com"
                           class="input-night w-full text-xs">
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Contraseña de Acceso *</label>
                    <input type="password" name="password" required minlength="6" placeholder="Mínimo 6 caracteres"
                           class="input-night w-full text-xs font-mono">
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Rol Operativo *</label>
                    <select name="role" required class="input-night w-full text-xs">
                        <option value="cashier">Cajero (Ventas, Cobros y Caja)</option>
                        <option value="waiter">Mesero (Mesas y Cuentas)</option>
                        <option value="admin">Administrador (Control Total)</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Sucursal(es) Asignadas</label>
                    <div class="space-y-1.5 p-2 rounded-xl bg-slate-50 dark:bg-slate-800/60 max-h-32 overflow-y-auto">
                        @foreach($branches as $br)
                            <label class="flex items-center gap-2 text-xs text-slate-700 dark:text-slate-300 cursor-pointer">
                                <input type="checkbox" name="branch_ids[]" value="{{ $br->id }}" {{ $br->is_main ? 'checked' : '' }} class="rounded text-emerald-600 focus:ring-emerald-500">
                                <span>{{ $br->name }} {{ $br->is_main ? '(Principal)' : '' }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="showNewUserModal = false" class="btn-touch flex-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl">Cancelar</button>
                    <button type="submit" class="btn-touch flex-1 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-lg shadow-emerald-600/30 rounded-xl">CREAR EMPLEADO</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Reset Password -->
    <div x-show="showPasswordModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" style="display: none;">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-black text-slate-900 dark:text-white mb-1">Cambiar Contraseña</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4" x-text="'Para: ' + resetUserName"></p>

            <form :action="'/users/' + resetUserId + '/reset-password'" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Nueva Contraseña *</label>
                    <input type="password" name="new_password" required minlength="6" placeholder="Mínimo 6 caracteres" autofocus
                           class="input-night w-full text-sm font-mono font-bold">
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="showPasswordModal = false" class="btn-touch flex-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl">Cancelar</button>
                    <button type="submit" class="btn-touch flex-1 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-lg shadow-emerald-600/30 rounded-xl">GUARDAR</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
