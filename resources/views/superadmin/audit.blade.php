@extends('layouts.app', ['title' => 'Auditoría Global SaaS (Superadmin)'])

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
                <span class="p-2 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 border border-purple-200 dark:border-purple-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                </span>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-xl font-black text-slate-900 dark:text-white">Auditoría Global de Plataforma SaaS</h2>
                        <span class="px-2 py-0.5 rounded-full bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-700 text-[10px] font-bold">SUPERADMIN</span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Visión maestra de creación de empresas, cambios de suscripción, accesos de superadmin y eventos de todas las licoreras</p>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('superadmin.index') }}" class="btn-touch py-2 px-3.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl border border-slate-300 dark:border-slate-700 shadow-sm transition-all">
                &larr; Volver al Panel Superadmin
            </a>
            <button type="button" onclick="window.print()" class="btn-touch py-2 px-3.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl flex items-center gap-1.5 border border-slate-300 dark:border-slate-700 shadow-sm transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                <span>Imprimir</span>
            </button>
        </div>
    </div>

    <!-- KPI Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="card-night p-4 border-l-4 border-l-purple-500">
            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Auditorías SaaS</span>
            <p class="text-2xl font-black font-mono text-slate-900 dark:text-white mt-1">{{ number_format($totalLogs) }}</p>
            <span class="text-[10px] text-slate-400">En toda la base de datos</span>
        </div>

        <div class="card-night p-4 border-l-4 border-l-fuchsia-500">
            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Acciones Superadmin</span>
            <p class="text-2xl font-black font-mono text-fuchsia-600 dark:text-fuchsia-400 mt-1">{{ number_format($superadminLogsCount) }}</p>
            <span class="text-[10px] text-slate-400">Creaciones, planes, accesos</span>
        </div>

        <div class="card-night p-4 border-l-4 border-l-emerald-500">
            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Empresas en Monitoreo</span>
            <p class="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400 mt-1">{{ $tenants->count() }}</p>
            <span class="text-[10px] text-slate-400">Tenants activos y suspendidos</span>
        </div>

        <div class="card-night p-4 border-l-4 border-l-cyan-500">
            <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Registros Filtrados</span>
            <p class="text-2xl font-black font-mono text-cyan-600 dark:text-cyan-400 mt-1">{{ number_format($logs->total()) }}</p>
            <span class="text-[10px] text-slate-400">Mostrados actualmente</span>
        </div>
    </div>

    <!-- Filters -->
    <div class="card-night p-4">
        <form method="GET" action="{{ route('superadmin.audit') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
            <!-- Search -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Buscar por palabra clave</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Empresa, Usuario, Motivo, IP..."
                           class="input-night w-full text-xs pl-8">
                    <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>

            <!-- Tenant Selector -->
            <div>
                <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Filtrar por Empresa</label>
                <select name="tenant_id" class="input-night w-full text-xs">
                    <option value="">Todas las empresas</option>
                    <option value="platform" {{ request('tenant_id') === 'platform' ? 'selected' : '' }}>⚙️ Solo Plataforma / Superadmin</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ request('tenant_id') == $t->id ? 'selected' : '' }}>
                            🏢 {{ $t->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Date From / To -->
            <div>
                <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Desde</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="input-night w-full text-xs">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">Hasta</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="input-night w-full text-xs">
            </div>

            <!-- Options & Submit -->
            <div class="sm:col-span-2 lg:col-span-5 flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-200 dark:border-slate-800">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="only_superadmin" value="1" {{ request('only_superadmin') ? 'checked' : '' }} class="w-4 h-4 rounded text-purple-600 focus:ring-0">
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Ver exclusivamente operaciones de Superadmin</span>
                </label>
                <div class="flex items-center gap-2">
                    @if(request()->anyFilled(['search', 'tenant_id', 'date_from', 'date_to', 'only_superadmin', 'action']))
                        <a href="{{ route('superadmin.audit') }}" class="btn-touch py-1.5 px-3 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 text-xs font-bold rounded-xl shadow-sm transition-all">
                            Limpiar Filtros
                        </a>
                    @endif
                    <button type="submit" class="btn-touch py-1.5 px-4 bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs rounded-xl shadow-md shadow-purple-600/20">
                        Aplicar Filtros
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="card-night p-4 flex-1 overflow-hidden flex flex-col">
        <div class="flex-1 overflow-y-auto">
            <div class="overflow-x-auto min-w-full">
                <table class="w-full text-left text-xs min-w-[850px]">
                    <thead class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider bg-slate-50/50 dark:bg-slate-850/50">
                        <tr>
                            <th class="py-3 px-3">Fecha y Hora</th>
                            <th class="py-3 px-3">Empresa / Tenant</th>
                            <th class="py-3 px-3">Usuario Responsable</th>
                            <th class="py-3 px-3">Acción Registrada</th>
                            <th class="py-3 px-3">Motivo / Detalle</th>
                            <th class="py-3 px-3 text-center">Valores</th>
                            <th class="py-3 px-3">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($logs as $log)
                            @php
                                $changes = $log->formatted_changes;
                                $isSuperAction = str_starts_with($log->action, 'superadmin_') || ($log->user && $log->user->is_superadmin);
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-850/80 transition-colors {{ $isSuperAction ? 'bg-purple-50/30 dark:bg-purple-950/20' : '' }}">
                                <!-- Date -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="font-mono text-slate-800 dark:text-slate-200 font-semibold">{{ $log->created_at->format('d/m/Y h:i:s A') }}</div>
                                    <div class="text-[10px] text-slate-400 mt-0.5">{{ $log->created_at->diffForHumans() }}</div>
                                </td>

                                <!-- Tenant -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    @if($log->tenant)
                                        <span class="px-2 py-0.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700">
                                            🏢 {{ $log->tenant->name }}
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-lg text-xs font-bold bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                            ⚙️ Plataforma SaaS
                                        </span>
                                    @endif
                                </td>

                                <!-- User -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    @if($log->user)
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-bold text-slate-900 dark:text-white">{{ $log->user->name }}</span>
                                            @if($log->user->is_superadmin)
                                                <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300">Superadmin</span>
                                            @endif
                                        </div>
                                        <div class="text-[10px] text-slate-400">{{ $log->user->email }}</div>
                                    @else
                                        <span class="text-slate-400 italic">Sistema</span>
                                    @endif
                                </td>

                                <!-- Action -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold border {{ $log->action_badge_class }}">
                                        {{ $log->action_label }}
                                    </span>
                                </td>

                                <!-- Reason -->
                                <td class="py-3 px-3 text-slate-700 dark:text-slate-200 max-w-xs">
                                    <p class="font-medium truncate" title="{{ $log->reason }}">{{ $log->reason ?? '-' }}</p>
                                </td>

                                <!-- Changes -->
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    @if(!empty($changes))
                                        <button type="button"
                                                @click="viewChanges({{ json_encode([
                                                    'action' => $log->action_label,
                                                    'category' => $log->action_category,
                                                    'user' => $log->user ? $log->user->name : 'Sistema',
                                                    'tenant' => $log->tenant ? $log->tenant->name : 'Plataforma Global',
                                                    'date' => $log->created_at->format('d/m/Y h:i:s A'),
                                                    'ip' => $log->ip_address ?? '127.0.0.1',
                                                    'reason' => $log->reason,
                                                    'changes' => $changes,
                                                ]) }})"
                                                class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 text-[11px] font-bold transition-all inline-flex items-center gap-1">
                                            <span>Ver Cambios ({{ count($changes) }})</span>
                                        </button>
                                    @else
                                        <span class="text-slate-400">-</span>
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
                                    No hay registros de auditoría que coincidan con los filtros.
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

    <!-- Modal: Detalle de Cambios -->
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
             class="card-night w-full max-w-2xl p-6 shadow-2xl border border-slate-200 dark:border-slate-800 flex flex-col max-h-[85vh]">

            <div class="flex items-start justify-between pb-4 border-b border-slate-200 dark:border-slate-800">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300"
                              x-text="selectedLog?.tenant"></span>
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

            <div class="py-4 space-y-4 overflow-y-auto flex-1">
                <div class="p-3 bg-slate-50 dark:bg-slate-850 rounded-xl border border-slate-200 dark:border-slate-800 text-xs">
                    <span class="font-bold text-slate-700 dark:text-slate-300">Motivo / Descripción:</span>
                    <p class="text-slate-600 dark:text-slate-400 mt-0.5" x-text="selectedLog?.reason || 'Sin motivo adicional especificado'"></p>
                </div>

                <div>
                    <h4 class="text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-2">Desglose de Valores y Campos</h4>
                    <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-100 dark:bg-slate-850 text-slate-600 dark:text-slate-400 font-bold uppercase text-[10px]">
                                <tr>
                                    <th class="py-2.5 px-3">Campo</th>
                                    <th class="py-2.5 px-3">Valor Anterior</th>
                                    <th class="py-2.5 px-3">Nuevo Valor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-mono">
                                <template x-for="(item, idx) in selectedLog?.changes" :key="idx">
                                    <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-850/50">
                                        <td class="py-2.5 px-3 font-sans font-bold text-slate-800 dark:text-slate-200" x-text="item.field"></td>
                                        <td class="py-2.5 px-3 text-rose-600 dark:text-rose-400" x-text="item.old || '-'"></td>
                                        <td class="py-2.5 px-3 text-emerald-600 dark:text-emerald-400 font-bold" x-text="item.new || '-'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                <button type="button" @click="modalOpen = false" class="btn-touch py-2 px-5 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl shadow-md">
                    Cerrar Detalle
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
