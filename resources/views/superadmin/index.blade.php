@extends('layouts.app', ['title' => 'Panel Superadmin SaaS'])

@section('content')
<div class="h-full flex flex-col space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-black text-white flex items-center gap-2">
                <span>Plataforma SaaS — Panel Superadministrador</span>
                <span class="badge-neon bg-purple-950 text-purple-300 border border-purple-700 text-xs">SUPERADMIN</span>
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Control global de licoreras registradas, planes de suscripción y estado del servicio</p>
        </div>
    </div>

    <!-- Platform KPI Metrics -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="card-night p-4 border-night-750">
            <span class="text-xs font-bold uppercase text-slate-400">Licoreras Registradas</span>
            <p class="text-2xl font-black font-mono text-white mt-1">{{ $totalTenants }}</p>
            <span class="text-[10px] text-emerald-400 font-semibold">{{ $activeTenants }} activas &bull; {{ $suspendedTenants }} suspendidas</span>
        </div>
        <div class="card-night p-4 border-night-750">
            <span class="text-xs font-bold uppercase text-slate-400">Total Usuarios Plataforma</span>
            <p class="text-2xl font-black font-mono text-white mt-1">{{ $totalUsers }}</p>
            <span class="text-[10px] text-slate-400">Admins, cajeros y meseros</span>
        </div>
        <div class="card-night p-4 border-night-750">
            <span class="text-xs font-bold uppercase text-slate-400">Ventas Procesadas</span>
            <p class="text-2xl font-black font-mono text-neon-cyan mt-1">{{ $totalSalesCount }}</p>
            <span class="text-[10px] text-slate-400">Transacciones globales</span>
        </div>
        <div class="card-night p-4 border-neon-green/30 bg-neon-green/5">
            <span class="text-xs font-black uppercase text-neon-green">Volumen Transaccional</span>
            <p class="text-2xl font-black font-mono text-neon-green mt-1">${{ number_format($totalVolume, 0, ',', '.') }}</p>
            <span class="text-[10px] text-slate-400">Pesos colombianos (COP)</span>
        </div>
    </div>

    <!-- Tenants Management Table -->
    <div class="card-night p-4 flex-1 overflow-hidden flex flex-col">
        <h3 class="text-sm font-bold text-white mb-2">Licoreras en la Plataforma</h3>
        <div class="flex-1 overflow-y-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-night-800 text-slate-400 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-2">Negocio / Tenant</th>
                        <th class="py-3 px-2">Contacto</th>
                        <th class="py-3 px-2">Plan</th>
                        <th class="py-3 px-2">Sucursales / Usuarios</th>
                        <th class="py-3 px-2">Estado</th>
                        <th class="py-3 px-2">Fecha Registro</th>
                        <th class="py-3 px-2 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-night-800">
                    @forelse($tenants as $t)
                        <tr>
                            <td class="py-3 px-2">
                                <span class="font-bold text-white text-sm block">{{ $t->name }}</span>
                                <span class="text-[10px] font-mono text-slate-500">{{ $t->uuid }}</span>
                            </td>
                            <td class="py-3 px-2 text-slate-300">
                                <div>{{ $t->email ?? 'Sin correo' }}</div>
                                <div class="text-[11px] text-slate-500">{{ $t->phone ?? 'Sin teléfono' }}</div>
                            </td>
                            <td class="py-3 px-2">
                                <span class="badge-neon bg-cyan-950 text-cyan-300 border border-cyan-700 text-[10px]">
                                    {{ $t->plan->name ?? 'Plan Básico' }}
                                </span>
                            </td>
                            <td class="py-3 px-2 text-slate-300 font-mono">
                                {{ $t->branches->count() }} sedes &bull; {{ $t->users->count() }} usuarios
                            </td>
                            <td class="py-3 px-2">
                                <span class="badge-neon {{ $t->status === 'active' ? 'bg-emerald-950 text-emerald-300 border-emerald-700' : 'bg-rose-950 text-rose-300 border-rose-700' }} text-[10px]">
                                    {{ strtoupper($t->status) }}
                                </span>
                            </td>
                            <td class="py-3 px-2 font-mono text-slate-400">{{ $t->created_at->format('d/m/Y') }}</td>
                            <td class="py-3 px-2 text-right">
                                <form action="{{ route('superadmin.tenants.toggle', $t->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 rounded-xl text-xs font-bold border transition-all {{ $t->status === 'active' ? 'bg-rose-950/80 hover:bg-rose-900 border-rose-800 text-rose-300' : 'bg-emerald-950/80 hover:bg-emerald-900 border-emerald-800 text-emerald-300' }}">
                                        {{ $t->status === 'active' ? 'Suspender Negocio' : 'Reactivar Negocio' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500">No hay negocios registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-3 border-t border-night-800">
            {{ $tenants->links() }}
        </div>
    </div>
</div>
@endsection
