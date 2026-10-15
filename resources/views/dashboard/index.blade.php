@extends('layouts.app', ['title' => 'Inicio Dashboard'])

@section('content')
<div class="space-y-4">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">Panel Operativo — {{ $currentTenant->name ?? 'Licorera' }}</h2>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    {{ $currentBranch->name ?? 'Sede Principal' }}
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Visión en tiempo real de ventas, inventario, mesas y caja de esta sucursal</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('pos.index') }}" class="btn-touch py-2.5 px-4 bg-slate-900 hover:bg-slate-800 dark:bg-slate-100 dark:hover:bg-white text-white dark:text-slate-900 font-semibold text-xs rounded-xl shadow-xs inline-flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                <span>NUEVA VENTA (POS)</span>
            </a>
        </div>
    </div>

    <!-- Top KPI Metrics -->
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
        <div class="card-night p-4 border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-950/20">
            <span class="text-[10px] font-bold uppercase text-emerald-600 dark:text-emerald-400">Ventas de Hoy</span>
            <p class="text-xl sm:text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400 mt-1">${{ number_format($metrics['today_sales'], 0, ',', '.') }}</p>
            <span class="text-[10px] text-slate-400">{{ $metrics['today_transactions'] }} ventas registradas</span>
        </div>

        <div class="card-night p-4 border-slate-200 dark:border-slate-800">
            <span class="text-[10px] font-bold uppercase text-slate-500 dark:text-slate-400">Ventas del Mes</span>
            <p class="text-xl sm:text-2xl font-black font-mono text-slate-900 dark:text-white mt-1">${{ number_format($metrics['month_sales'], 0, ',', '.') }}</p>
            <span class="text-[10px] text-slate-400">Acumulado mensual</span>
        </div>

        <div class="card-night p-4 border-slate-200 dark:border-slate-800">
            <span class="text-[10px] font-bold uppercase text-slate-500 dark:text-slate-400">Ticket Promedio</span>
            <p class="text-xl sm:text-2xl font-black font-mono text-slate-900 dark:text-white mt-1">${{ number_format($metrics['average_ticket'], 0, ',', '.') }}</p>
            <span class="text-[10px] text-slate-400">Por transacción</span>
        </div>

        <div class="card-night p-4 border-amber-300 dark:border-amber-500/30 bg-amber-50/50 dark:bg-amber-950/10">
            <span class="text-[10px] font-bold uppercase text-amber-600 dark:text-amber-400">Mesas Ocupadas</span>
            <p class="text-xl sm:text-2xl font-black font-mono text-amber-600 dark:text-amber-400 mt-1">{{ $metrics['occupied_tables'] }} / {{ $metrics['total_tables'] }}</p>
            <span class="text-[10px] text-slate-400">Consumo en sitio</span>
        </div>

        <div class="card-night p-4 border-slate-200 dark:border-slate-800">
            <span class="text-[10px] font-bold uppercase text-slate-500 dark:text-slate-400">Stock Crítico</span>
            <p class="text-xl sm:text-2xl font-black font-mono {{ $metrics['low_stock_count'] > 0 ? 'text-amber-500' : 'text-slate-700 dark:text-slate-300' }} mt-1">
                {{ $metrics['low_stock_count'] }}
            </p>
            <span class="text-[10px] text-slate-400">{{ $metrics['out_of_stock_count'] }} agotados</span>
        </div>

        <div class="card-night p-4 border-slate-200 dark:border-slate-800">
            <span class="text-[10px] font-bold uppercase text-slate-500 dark:text-slate-400">Utilidad Bruta Est.</span>
            <p class="text-xl sm:text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400 mt-1">${{ number_format($metrics['estimated_gross_profit'], 0, ',', '.') }}</p>
            <span class="text-[10px] text-slate-400">Ventas menos COGS</span>
        </div>
    </div>

    <!-- Middle 2-Column Section: Top Products & Quick Tables Glance -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Top Products Today -->
        <div class="card-night p-4 flex flex-col min-h-[300px]">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-2">Top Productos Más Vendidos Hoy</h3>
            <div class="flex-1 overflow-x-auto min-w-full">
                <table class="w-full text-left text-xs min-w-[320px]">
                    <thead class="border-b border-slate-200 dark:border-slate-800 text-slate-400 font-bold uppercase">
                        <tr>
                            <th class="py-2">Producto</th>
                            <th class="py-2 text-center">Unidades</th>
                            <th class="py-2 text-right">Total Ingresos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($metrics['top_products'] as $top)
                            <tr>
                                <td class="py-2.5 font-bold text-slate-800 dark:text-slate-200">{{ $top->product_name_snapshot }}</td>
                                <td class="py-2.5 text-center font-mono font-bold text-slate-700 dark:text-slate-300">{{ rtrim(rtrim($top->total_qty, '0'), '.') }}</td>
                                <td class="py-2.5 text-right font-mono font-bold text-slate-900 dark:text-white">${{ number_format($top->total_revenue, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-6 text-center text-slate-400">Aún no hay ventas registradas el día de hoy.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Tables Status -->
        <div class="card-night p-4 flex flex-col overflow-hidden">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Estado Rápido de Mesas</h3>
                <a href="{{ route('tables.index') }}" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline font-semibold">Ver todas &rarr;</a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 overflow-y-auto flex-1 p-1">
                @foreach($tables as $t)
                    <a href="{{ route('tables.index') }}"
                       class="p-2.5 rounded-2xl border flex flex-col justify-between transition-all {{ $t->status === 'occupied' ? 'bg-amber-50 dark:bg-amber-950/30 border-amber-300 dark:border-amber-500/40 text-amber-800 dark:text-amber-300' : 'bg-emerald-50 dark:bg-emerald-950/20 border-emerald-300 dark:border-emerald-500/30 text-emerald-800 dark:text-emerald-300' }}">
                        <div class="flex items-center justify-between">
                            <span class="font-black text-sm">{{ $t->name }}</span>
                            <span class="text-[9px] font-bold uppercase">{{ $t->status === 'occupied' ? 'OCUPADA' : 'LIBRE' }}</span>
                        </div>
                        @if($t->activeAccount)
                            <span class="font-mono font-bold text-xs mt-1">${{ number_format($t->activeAccount->total, 0, ',', '.') }}</span>
                        @else
                            <span class="text-[10px] text-slate-400 mt-1">Disponible</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
