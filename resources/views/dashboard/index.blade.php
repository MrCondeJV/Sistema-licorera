@extends('layouts.app', ['title' => 'Inicio Dashboard'])

@section('content')
<div class="h-full flex flex-col space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl sm:text-2xl font-black text-white">Panel Operativo — {{ $currentTenant->name ?? 'Licorera' }}</h2>
            <p class="text-xs text-slate-400 mt-0.5">Visión en tiempo real de ventas, inventario, mesas y caja</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('pos.index') }}" class="btn-touch py-2.5 px-5 bg-gradient-to-r from-neon-green to-emerald-400 text-night-950 font-black text-sm shadow-xl shadow-neon-green/20">
                ⚡ VENDER AHORA (POS)
            </a>
        </div>
    </div>

    <!-- Top KPI Metrics -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
        <div class="card-night p-4 border-neon-green/30 bg-neon-green/5">
            <span class="text-[10px] font-bold uppercase text-neon-green">Ventas de Hoy</span>
            <p class="text-xl sm:text-2xl font-black font-mono text-neon-green mt-1">${{ number_format($metrics['today_sales'], 0, ',', '.') }}</p>
            <span class="text-[10px] text-slate-400">{{ $metrics['today_transactions'] }} ventas registradas</span>
        </div>

        <div class="card-night p-4 border-night-750">
            <span class="text-[10px] font-bold uppercase text-slate-400">Ventas del Mes</span>
            <p class="text-xl sm:text-2xl font-black font-mono text-white mt-1">${{ number_format($metrics['month_sales'], 0, ',', '.') }}</p>
            <span class="text-[10px] text-slate-400">Acumulado mensual</span>
        </div>

        <div class="card-night p-4 border-night-750">
            <span class="text-[10px] font-bold uppercase text-slate-400">Ticket Promedio</span>
            <p class="text-xl sm:text-2xl font-black font-mono text-neon-cyan mt-1">${{ number_format($metrics['average_ticket'], 0, ',', '.') }}</p>
            <span class="text-[10px] text-slate-400">Por transacción</span>
        </div>

        <div class="card-night p-4 border-amber-500/30 bg-amber-950/10">
            <span class="text-[10px] font-bold uppercase text-amber-400">Mesas Ocupadas</span>
            <p class="text-xl sm:text-2xl font-black font-mono text-amber-400 mt-1">{{ $metrics['occupied_tables'] }} / {{ $metrics['total_tables'] }}</p>
            <span class="text-[10px] text-slate-400">Consumo en sitio</span>
        </div>

        <div class="card-night p-4 border-night-750">
            <span class="text-[10px] font-bold uppercase text-slate-400">Stock Crítico</span>
            <p class="text-xl sm:text-2xl font-black font-mono {{ $metrics['low_stock_count'] > 0 ? 'text-amber-400' : 'text-slate-300' }} mt-1">
                {{ $metrics['low_stock_count'] }}
            </p>
            <span class="text-[10px] text-slate-400">{{ $metrics['out_of_stock_count'] }} agotados</span>
        </div>

        <div class="card-night p-4 border-night-750">
            <span class="text-[10px] font-bold uppercase text-slate-400">Utilidad Bruta Est.</span>
            <p class="text-xl sm:text-2xl font-black font-mono text-emerald-400 mt-1">${{ number_format($metrics['estimated_gross_profit'], 0, ',', '.') }}</p>
            <span class="text-[10px] text-slate-400">Ventas menos COGS</span>
        </div>
    </div>

    <!-- Middle 2-Column Section: Top Products & Quick Tables Glance -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 flex-1 overflow-hidden">
        <!-- Top Products Today -->
        <div class="card-night p-4 flex flex-col overflow-hidden">
            <h3 class="text-sm font-bold text-white mb-2">Top Productos Más Vendidos Hoy</h3>
            <div class="flex-1 overflow-y-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-night-800 text-slate-400 font-bold uppercase">
                        <tr>
                            <th class="py-2">Producto</th>
                            <th class="py-2 text-center">Unidades</th>
                            <th class="py-2 text-right">Total Ingresos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-night-800">
                        @forelse($metrics['top_products'] as $top)
                            <tr>
                                <td class="py-2.5 font-bold text-slate-200">{{ $top->product_name_snapshot }}</td>
                                <td class="py-2.5 text-center font-mono font-bold text-neon-cyan">{{ rtrim(rtrim($top->total_qty, '0'), '.') }}</td>
                                <td class="py-2.5 text-right font-mono font-bold text-white">${{ number_format($top->total_revenue, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-6 text-center text-slate-500">Aún no hay ventas registradas el día de hoy.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Tables Status -->
        <div class="card-night p-4 flex flex-col overflow-hidden">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-bold text-white">Estado Rápido de Mesas</h3>
                <a href="{{ route('tables.index') }}" class="text-xs text-neon-cyan hover:underline font-semibold">Ver todas &rarr;</a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 overflow-y-auto flex-1 p-1">
                @foreach($tables as $t)
                    <a href="{{ route('tables.index') }}"
                       class="p-2.5 rounded-xl border flex flex-col justify-between transition-all {{ $t->status === 'occupied' ? 'bg-amber-950/30 border-amber-500/40 text-amber-300' : 'bg-emerald-950/15 border-emerald-500/30 text-emerald-300' }}">
                        <div class="flex items-center justify-between">
                            <span class="font-black text-sm">{{ $t->name }}</span>
                            <span class="text-[9px] font-bold uppercase">{{ $t->status === 'occupied' ? 'OCUPADA' : 'LIBRE' }}</span>
                        </div>
                        @if($t->activeAccount)
                            <span class="font-mono font-bold text-xs mt-1">${{ number_format($t->activeAccount->total, 0, ',', '.') }}</span>
                        @else
                            <span class="text-[10px] text-slate-500 mt-1">Disponible</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
