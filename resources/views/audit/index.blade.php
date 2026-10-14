@extends('layouts.app', ['title' => 'Auditoría y Trazabilidad'])

@section('content')
<div class="h-full flex flex-col space-y-4" x-data="{
    modalOpen: false,
    selectedLog: null,
    viewChanges(log) {
        this.selectedLog = log;
        this.modalOpen = true;
    }
}">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <span class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                </span>
                <div>
                    <h2 class="text-xl font-black text-slate-900 dark:text-white">Auditoría y Trazabilidad Operacional</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Control inmutable de movimientos, creaciones y ediciones de productos, caja y operaciones por cajeros y empleados</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="btn-touch py-2 px-3.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl flex items-center gap-1.5 border border-slate-200 dark:border-slate-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                <span>Imprimir / Exportar</span>
            </button>
        </div>
    </div>

    <!-- KPI Summary Metrics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="card-night p-4 flex items-center gap-3 border-l-4 border-l-emerald-500">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Movimientos Hoy</p>
                <p class="text-xl font-black text-slate-900 dark:text-white font-mono">{{ number_format($metrics['totalToday']) }}</p>
            </div>
        </div>

        <div class="card-night p-4 flex items-center gap-3 border-l-4 border-l-cyan-500">
            <div class="w-10 h-10 rounded-xl bg-cyan-50 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Por Cajeros Hoy</p>
                <p class="text-xl font-black text-cyan-600 dark:text-cyan-400 font-mono">{{ number_format($metrics['cashierActionsToday']) }}</p>
            </div>
        </div>

        <div class="card-night p-4 flex items-center gap-3 border-l-4 border-l-purple-500">
            <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Inventario y Precios</p>
                <p class="text-xl font-black text-purple-600 dark:text-purple-400 font-mono">{{ number_format($metrics['inventoryActions']) }}</p>
            </div>
        </div>

        <div class="card-night p-4 flex items-center gap-3 border-l-4 border-l-rose-500">
            <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Caja y Gastos</p>
                <p class="text-xl font-black text-rose-600 dark:text-rose-400 font-mono">{{ number_format($metrics['cashAndExpense']) }}</p>
            </div>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="card-night p-4">
        <form method="GET" action="{{ route('audit.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            <!-- Search -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Buscar por palabra clave</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Producto, SKU, Nº Venta, Motivo, IP..."
                           class="input-night w-full text-xs pl-8">
                    <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>

            <!-- User Selector -->
            <div>
                <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Empleado / Cajero</label>
                <select name="user_id" class="input-night w-full text-xs">
                    <option value="">Todos los empleados</option>
                    @foreach($tenantUsers as $u)
                        @php
                            $role = $u->getRoleInTenant($currentTenantId) ?? ($u->is_superadmin ? 'Superadmin' : 'Usuario');
                            $roleLabel = match($role) {
                                'admin' => 'Administrador',
                                'cashier' => 'Cajero',
                                'waiter' => 'Mesero',
                                default => ucfirst($role),
                            };
                        @endphp
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                            {{ $u->name }} ({{ $roleLabel }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Module / Category -->
            <div>
                <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Módulo / Operación</label>
                <select name="category" class="input-night w-full text-xs">
                    <option value="">Todos los módulos</option>
                    <option value="inventory" {{ request('category') === 'inventory' ? 'selected' : '' }}>📦 Inventario y Productos</option>
                    <option value="sales" {{ request('category') === 'sales' ? 'selected' : '' }}>💳 Ventas y Facturación</option>
                    <option value="cash" {{ request('category') === 'cash' ? 'selected' : '' }}>💵 Caja y Efectivo</option>
                    <option value="tables" {{ request('category') === 'tables' ? 'selected' : '' }}>🍽️ Mesas y Cuentas</option>
                    <option value="expenses" {{ request('category') === 'expenses' ? 'selected' : '' }}>📉 Gastos Operativos</option>
                    <option value="purchases" {{ request('category') === 'purchases' ? 'selected' : '' }}>🚚 Compras y Proveedores</option>
                    <option value="customers" {{ request('category') === 'customers' ? 'selected' : '' }}>👥 Clientes y Fiados</option>
                    <option value="security" {{ request('category') === 'security' ? 'selected' : '' }}>🔐 Seguridad y Accesos</option>
                </select>
            </div>

            <!-- Date Preset -->
            <div>
                <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Periodo de Tiempo</label>
                <select name="preset" class="input-night w-full text-xs">
                    <option value="">Todo el historial</option>
                    <option value="today" {{ request('preset') === 'today' ? 'selected' : '' }}>Hoy</option>
                    <option value="yesterday" {{ request('preset') === 'yesterday' ? 'selected' : '' }}>Ayer</option>
                    <option value="this_week" {{ request('preset') === 'this_week' ? 'selected' : '' }}>Esta semana</option>
                    <option value="this_month" {{ request('preset') === 'this_month' ? 'selected' : '' }}>Este mes</option>
                </select>
            </div>

            <!-- Actions -->
            <div class="sm:col-span-2 lg:col-span-5 flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-500 dark:text-slate-400">Rango personalizado:</span>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="input-night text-xs py-1 px-2">
                    <span class="text-xs text-slate-400">a</span>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="input-night text-xs py-1 px-2">
                </div>
                <div class="flex items-center gap-2">
                    @if(request()->anyFilled(['search', 'user_id', 'category', 'preset', 'date_from', 'date_to', 'action']))
                        <a href="{{ route('audit.index') }}" class="btn-touch py-1.5 px-3 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-600 dark:text-slate-400 text-xs font-bold rounded-xl">
                            Limpiar Filtros
                        </a>
                    @endif
                    <button type="submit" class="btn-touch py-1.5 px-4 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-600/20">
                        Aplicar Filtros
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Audit Log Table -->
    <div class="card-night p-4 flex-1 overflow-hidden flex flex-col">
        <div class="flex-1 overflow-y-auto">
            <div class="overflow-x-auto min-w-full">
                <table class="w-full text-left text-xs min-w-[850px]">
                    <thead class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider bg-slate-50/50 dark:bg-slate-850/50">
                        <tr>
                            <th class="py-3 px-3">Fecha y Hora</th>
                            <th class="py-3 px-3">Usuario Responsable</th>
                            <th class="py-3 px-3">Acción / Módulo</th>
                            <th class="py-3 px-3">Entidad / Ref.</th>
                            <th class="py-3 px-3">Motivo / Detalle Operacional</th>
                            <th class="py-3 px-3 text-center">Cambios / Valores</th>
                            <th class="py-3 px-3">IP / Terminal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($logs as $log)
                            @php
                                $role = $log->user ? ($log->user->getRoleInTenant($currentTenantId) ?? ($log->user->is_superadmin ? 'superadmin' : 'usuario')) : null;
                                $roleBadge = match($role) {
                                    'admin' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300',
                                    'cashier' => 'bg-cyan-100 text-cyan-800 dark:bg-cyan-950/80 dark:text-cyan-300',
                                    'waiter' => 'bg-purple-100 text-purple-800 dark:bg-purple-950/80 dark:text-purple-300',
                                    'superadmin' => 'bg-fuchsia-100 text-fuchsia-800 dark:bg-fuchsia-950/80 dark:text-fuchsia-300',
                                    default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                                };
                                $roleName = match($role) {
                                    'admin' => 'Admin',
                                    'cashier' => 'Cajero',
                                    'waiter' => 'Mesero',
                                    'superadmin' => 'Superadmin',
                                    default => 'Sistema',
                                };
                                $changes = $log->formatted_changes;
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-850/80 transition-colors">
                                <!-- Date -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="font-mono text-slate-800 dark:text-slate-200 font-semibold">{{ $log->created_at->format('d/m/Y h:i:s A') }}</div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">{{ $log->created_at->diffForHumans() }}</div>
                                </td>

                                <!-- User -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    @if($log->user)
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold flex items-center justify-center text-[11px] shrink-0">
                                                {{ strtoupper(substr($log->user->name, 0, 1)) }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                                    <span>{{ $log->user->name }}</span>
                                                    <span class="px-1.5 py-0.2 rounded-md text-[9px] font-bold uppercase {{ $roleBadge }}">
                                                        {{ $roleName }}
                                                    </span>
                                                </div>
                                                <div class="text-[10px] text-slate-400">{{ $log->user->email }}</div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-slate-400 italic">Sistema Automático</span>
                                    @endif
                                </td>

                                <!-- Action & Module -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold border shadow-xs {{ $log->action_badge_class }}">
                                        <span>{{ $log->action_label }}</span>
                                    </span>
                                    <div class="text-[10px] text-slate-400 mt-0.5">{{ $log->action_category }}</div>
                                </td>

                                <!-- Entity -->
                                <td class="py-3 px-3 whitespace-nowrap font-mono text-slate-600 dark:text-slate-300">
                                    @if($log->entity_type)
                                        <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[11px]">
                                            {{ class_basename($log->entity_type) }} #{{ $log->entity_id }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>

                                <!-- Reason -->
                                <td class="py-3 px-3 text-slate-700 dark:text-slate-200 max-w-xs">
                                    <p class="font-medium truncate" title="{{ $log->reason }}">{{ $log->reason ?? '-' }}</p>
                                </td>

                                <!-- Diff / Changes -->
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    @if(!empty($changes))
                                        <button type="button"
                                                @click="viewChanges({{ json_encode([
                                                    'action' => $log->action_label,
                                                    'category' => $log->action_category,
                                                    'user' => $log->user ? $log->user->name . ' (' . $roleName . ')' : 'Sistema',
                                                    'date' => $log->created_at->format('d/m/Y h:i:s A'),
                                                    'ip' => $log->ip_address ?? '127.0.0.1',
                                                    'reason' => $log->reason,
                                                    'changes' => $changes,
                                                ]) }})"
                                                class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 text-[11px] font-bold transition-all shadow-xs inline-flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            <span>Ver Cambios ({{ count($changes) }})</span>
                                        </button>
                                    @else
                                        <span class="text-slate-400 text-[11px]">-</span>
                                    @endif
                                </td>

                                <!-- IP -->
                                <td class="py-3 px-3 whitespace-nowrap font-mono text-[11px] text-slate-500 dark:text-slate-400">
                                    {{ $log->ip_address ?? '127.0.0.1' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-2 text-2xl">
                                        📋
                                    </div>
                                    <p class="font-bold text-sm text-slate-600 dark:text-slate-400">No se encontraron movimientos registrados</p>
                                    <p class="text-xs text-slate-400 mt-1">Prueba ajustando los filtros de búsqueda o fecha.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
            <span class="text-xs text-slate-500 dark:text-slate-400">
                Mostrando {{ $logs->firstItem() ?? 0 }} a {{ $logs->lastItem() ?? 0 }} de {{ $logs->total() }} registros
            </span>
            <div>
                {{ $logs->links() }}
            </div>
        </div>
    </div>

    <!-- Modal: Detalle de Cambios e Inspección de Valores -->
    <div x-show="modalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;"
         @keydown.escape.window="modalOpen = false">

        <div @click.away="modalOpen = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="card-night w-full max-w-2xl p-6 shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col max-h-[85vh]">

            <!-- Modal Header -->
            <div class="flex items-start justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                              x-text="selectedLog?.category"></span>
                        <h3 class="text-base font-black text-slate-900 dark:text-white" x-text="selectedLog?.action"></h3>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Ejecutado por <strong class="text-slate-800 dark:text-slate-200" x-text="selectedLog?.user"></strong> &bull;
                        <span x-text="selectedLog?.date"></span> &bull;
                        IP: <code class="font-mono text-slate-600 dark:text-slate-400" x-text="selectedLog?.ip"></code>
                    </p>
                </div>
                <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl font-bold p-1">&times;</button>
            </div>

            <!-- Modal Body: Reason and Changes Table -->
            <div class="py-4 space-y-4 overflow-y-auto flex-1">
                <!-- Reason Alert -->
                <div class="p-3 bg-slate-50 dark:bg-slate-850 rounded-xl border border-slate-200 dark:border-slate-800 text-xs">
                    <span class="font-bold text-slate-700 dark:text-slate-300">Motivo / Descripción:</span>
                    <p class="text-slate-600 dark:text-slate-400 mt-0.5" x-text="selectedLog?.reason || 'Sin motivo adicional especificado'"></p>
                </div>

                <!-- Changes Comparison Table -->
                <div>
                    <h4 class="text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-2">Desglose de Valores y Campos Afectados</h4>
                    <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-100 dark:bg-slate-850 text-slate-600 dark:text-slate-400 font-bold uppercase text-[10px]">
                                <tr>
                                    <th class="py-2.5 px-3">Campo Modificado</th>
                                    <th class="py-2.5 px-3">Valor Anterior</th>
                                    <th class="py-2.5 px-3">Nuevo Valor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-mono">
                                <template x-for="(item, idx) in selectedLog?.changes" :key="idx">
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-850/50">
                                        <td class="py-2.5 px-3 font-sans font-bold text-slate-800 dark:text-slate-200" x-text="item.field"></td>
                                        <td class="py-2.5 px-3 text-rose-600 dark:text-rose-400">
                                            <span x-text="item.old || '-'"></span>
                                        </td>
                                        <td class="py-2.5 px-3 text-emerald-600 dark:text-emerald-400 font-bold">
                                            <span x-text="item.new || '-'"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                <button type="button" @click="modalOpen = false" class="btn-touch py-2 px-5 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl shadow-md">
                    Cerrar Detalle
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

