@extends('layouts.app', ['title' => 'Panel Superadmin SaaS'])

@section('content')
<div class="h-full flex flex-col space-y-4">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-black text-slate-900 dark:text-white flex flex-wrap items-center gap-2">
                <span>Plataforma SaaS — Panel Superadministrador</span>
                <span class="px-2.5 py-0.5 rounded-full bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-700 text-xs font-bold">SUPERADMIN</span>
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Control global de licoreras registradas, planes de suscripción y acceso maestro a empresas</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('superadmin.payments') }}" class="btn-touch bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 text-xs font-black shadow-lg shadow-emerald-600/30 rounded-2xl flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                <span>Histórico de Pagos SaaS</span>
            </a>
            <a href="{{ route('superadmin.audit') }}" class="btn-touch bg-purple-600 hover:bg-purple-500 text-white px-4 py-2 text-xs font-black shadow-lg shadow-purple-600/30 rounded-2xl flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                <span>Auditoría Global</span>
            </a>
            <a href="{{ route('superadmin.tenants.create') }}" class="btn-touch bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 text-xs font-black shadow-lg shadow-indigo-600/30 rounded-2xl flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Nueva Empresa / Licorera</span>
            </a>
        </div>
    </div>

    <!-- Platform KPI Metrics -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="card-night p-4 border-slate-200 dark:border-slate-800">
            <span class="text-xs font-bold uppercase text-slate-500 dark:text-slate-400">Licoreras Registradas</span>
            <p class="text-2xl font-black font-mono text-slate-900 dark:text-white mt-1">{{ $totalTenants }}</p>
            <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">{{ $activeTenants }} activas &bull; {{ $suspendedTenants }} suspendidas</span>
        </div>
        <div class="card-night p-4 border-slate-200 dark:border-slate-800">
            <span class="text-xs font-bold uppercase text-slate-500 dark:text-slate-400">Total Usuarios Plataforma</span>
            <p class="text-2xl font-black font-mono text-slate-900 dark:text-white mt-1">{{ $totalUsers }}</p>
            <span class="text-[10px] text-slate-400">Admins, cajeros y meseros</span>
        </div>
        <div class="card-night p-4 border-slate-200 dark:border-slate-800">
            <span class="text-xs font-bold uppercase text-slate-500 dark:text-slate-400">Ventas Procesadas</span>
            <p class="text-2xl font-black font-mono text-cyan-600 dark:text-cyan-400 mt-1">{{ $totalSalesCount }}</p>
            <span class="text-[10px] text-slate-400">Transacciones globales</span>
        </div>
        <div class="card-night p-4 border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-950/20">
            <span class="text-xs font-black uppercase text-emerald-600 dark:text-emerald-400">Volumen Transaccional</span>
            <p class="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400 mt-1">${{ number_format($totalVolume, 0, ',', '.') }}</p>
            <span class="text-[10px] text-slate-400">Pesos colombianos (COP)</span>
        </div>
    </div>

    <!-- Tenants Management Table -->
    <div class="card-night p-4 flex-1 overflow-hidden flex flex-col">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-2">Licoreras en la Plataforma</h3>
        <div class="flex-1 overflow-y-auto">
            <div class="overflow-x-auto min-w-full">
                <table class="w-full text-left text-xs min-w-[880px]">
                <thead class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-2">Negocio / Tenant</th>
                        <th class="py-3 px-2">Contacto</th>
                        <th class="py-3 px-2">Plan</th>
                        <th class="py-3 px-2">Membresía / Vencimiento</th>
                        <th class="py-3 px-2">Sucursales / Usuarios</th>
                        <th class="py-3 px-2">Estado</th>
                        <th class="py-3 px-2 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($tenants as $t)
                        @php
                            $expDate = $t->getExpirationDate();
                            $daysRemaining = $t->daysRemaining();
                            $isPastDue = $t->isPastDue();
                            $graceExceeded = $t->isGracePeriodExceeded();
                        @endphp
                        <tr>
                            <td class="py-3 px-2">
                                <span class="font-bold text-slate-900 dark:text-white text-sm block">{{ $t->name }}</span>
                                <span class="text-[10px] font-mono text-slate-400">{{ $t->uuid }}</span>
                            </td>
                            <td class="py-3 px-2 text-slate-600 dark:text-slate-300">
                                <div>{{ $t->email ?? 'Sin correo' }}</div>
                                <div class="text-[11px] text-slate-400">{{ $t->phone ?? 'Sin teléfono' }}</div>
                            </td>
                            <td class="py-3 px-2">
                                <span class="px-2 py-0.5 rounded-full bg-cyan-100 dark:bg-cyan-950 text-cyan-800 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-700 text-[10px] font-bold">
                                    {{ $t->plan->name ?? 'Plan Básico' }}
                                </span>
                            </td>
                            <td class="py-3 px-2">
                                <div class="space-y-0.5">
                                    @if($t->hasActiveSubscription())
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700 text-[10px] font-black">
                                                ✅ ACTIVA
                                            </span>
                                            <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
                                                Faltan {{ $daysRemaining }}d
                                            </span>
                                        </div>
                                    @elseif($t->isOnTrial())
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-2 py-0.5 rounded-full bg-cyan-100 dark:bg-cyan-950 text-cyan-800 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-700 text-[10px] font-black">
                                                🎁 PRUEBA 14D
                                            </span>
                                            <span class="text-[11px] font-bold text-cyan-600 dark:text-cyan-400">
                                                Quedan {{ $daysRemaining }}d
                                            </span>
                                        </div>
                                    @elseif($graceExceeded)
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-2 py-0.5 rounded-full bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-700 text-[10px] font-black">
                                                🚨 SUSPENDIDA
                                            </span>
                                            <span class="text-[11px] font-bold text-rose-600 dark:text-rose-400">
                                                Venció hace {{ $t->daysPastDue() }}d
                                            </span>
                                        </div>
                                    @elseif($isPastDue)
                                        <div class="flex items-center gap-1.5">
                                            <span class="px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-700 text-[10px] font-black">
                                                ⚠️ GRACIA ({{ $t->graceDaysRemaining() }}d)
                                            </span>
                                        </div>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[10px] font-bold">
                                            SIN PLAN
                                        </span>
                                    @endif
                                    <div class="text-[11px] font-mono text-slate-500 dark:text-slate-400">
                                        Vence: <strong>{{ $expDate ? $expDate->format('d/m/Y') : 'Sin fecha' }}</strong>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-2 text-slate-600 dark:text-slate-300 font-mono">
                                {{ $t->branches->count() }} sedes &bull; {{ $t->users->count() }} usuarios
                            </td>
                            <td class="py-3 px-2">
                                <span class="px-2 py-0.5 rounded-full {{ $t->status === 'active' ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700' : 'bg-rose-100 dark:bg-rose-950 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-700' }} text-[10px] font-bold">
                                    {{ strtoupper($t->status) }}
                                </span>
                            </td>
                            <td class="py-3 px-2 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- SaaS Payments History for this tenant -->
                                    <a href="{{ route('superadmin.payments', ['tenant_id' => $t->id]) }}"
                                       class="px-2.5 py-1.5 rounded-xl text-xs font-bold bg-purple-50 hover:bg-purple-100 dark:bg-purple-950/60 dark:hover:bg-purple-900 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-700 transition-all flex items-center gap-1"
                                       title="Ver historial de pagos de esta licorera">
                                        <span>💳</span>
                                        <span>Pagos ({{ $t->subscriptionPayments->count() }})</span>
                                    </a>

                                    <!-- Enter / Impersonate Button -->
                                    <form action="{{ route('superadmin.tenants.enter', $t->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1.5 rounded-xl text-xs font-bold bg-cyan-50 hover:bg-cyan-100 dark:bg-cyan-950/60 dark:hover:bg-cyan-900 text-cyan-700 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-700 transition-all flex items-center gap-1" title="Entrar y gestionar esta empresa">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                                            </svg>
                                            <span>Acceder</span>
                                        </button>
                                    </form>

                                    <!-- Edit Button -->
                                    <a href="{{ route('superadmin.tenants.edit', $t->id) }}" class="px-2.5 py-1.5 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 transition-all flex items-center gap-1" title="Editar empresa">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        <span>Editar</span>
                                    </a>

                                    <!-- Toggle Status Button -->
                                    <form action="{{ route('superadmin.tenants.toggle', $t->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1.5 rounded-xl text-xs font-bold border transition-all {{ $t->status === 'active' ? 'bg-rose-50 hover:bg-rose-100 text-rose-700 border-rose-200 dark:bg-rose-950/80 dark:hover:bg-rose-900 dark:border-rose-800 dark:text-rose-300' : 'bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border-emerald-200 dark:bg-emerald-950/80 dark:hover:bg-emerald-900 dark:border-emerald-800 dark:text-emerald-300' }}">
                                            {{ $t->status === 'active' ? 'Suspender' : 'Reactivar' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">No hay negocios registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
                </table>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-200 dark:border-slate-800">
            {{ $tenants->links() }}
        </div>
    </div>
</div>
@endsection
