@extends('layouts.app', ['title' => 'Reportes y Rentabilidad'])

@section('content')
<div class="h-full flex flex-col space-y-4">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-black text-white">Reportes de Ventas y Rentabilidad</h2>
            <p class="text-xs text-slate-400 mt-0.5">Márgenes reales, costo de mercancía vendida (COGS) y desglose de pagos</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('reports.pdf', request()->query()) }}" target="_blank"
               class="btn-touch py-2 px-3 bg-night-800 hover:bg-night-750 text-rose-400 border border-night-750 text-xs font-bold flex items-center gap-1.5">
                <span>📄 Exportar PDF</span>
            </a>
            <a href="{{ route('reports.excel', request()->query()) }}"
               class="btn-touch py-2 px-3 bg-night-800 hover:bg-night-750 text-emerald-400 border border-night-750 text-xs font-bold flex items-center gap-1.5">
                <span>📊 Exportar Excel</span>
            </a>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="card-night p-3">
        <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-center gap-2">
            <div class="flex items-center gap-1 text-xs">
                <span class="text-slate-400">Desde:</span>
                <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}"
                       class="bg-night-850 border border-night-750 text-white rounded-xl px-2.5 py-1.5 text-xs focus:border-neon-green focus:outline-none">
            </div>

            <div class="flex items-center gap-1 text-xs">
                <span class="text-slate-400">Hasta:</span>
                <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}"
                       class="bg-night-850 border border-night-750 text-white rounded-xl px-2.5 py-1.5 text-xs focus:border-neon-green focus:outline-none">
            </div>

            <select name="user_id" class="bg-night-850 border border-night-750 text-white rounded-xl px-3 py-1.5 text-xs focus:outline-none focus:border-neon-green">
                <option value="">Todos los cajeros</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ $userId == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                @endforeach
            </select>

            <select name="payment_method_id" class="bg-night-850 border border-night-750 text-white rounded-xl px-3 py-1.5 text-xs focus:outline-none focus:border-neon-green">
                <option value="">Todos los medios de pago</option>
                @foreach($paymentMethods as $pm)
                    <option value="{{ $pm->id }}" {{ $paymentMethodId == $pm->id ? 'selected' : '' }}>{{ $pm->name }}</option>
                @endforeach
            </select>

            <button type="submit" class="px-4 py-2 bg-neon-green hover:bg-emerald-400 text-night-950 font-bold text-xs rounded-xl shadow-md">
                Filtrar Reporte
            </button>
        </form>
    </div>

    <!-- Financial KPI Summary Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="card-night p-3.5 border-night-750">
            <span class="text-[10px] uppercase font-bold text-slate-400">Ventas Totales</span>
            <p class="text-lg font-black font-mono text-white mt-1">${{ number_format($report['total_sales'], 0, ',', '.') }}</p>
        </div>
        <div class="card-night p-3.5 border-night-750">
            <span class="text-[10px] uppercase font-bold text-slate-400">Descuentos</span>
            <p class="text-lg font-black font-mono text-amber-400 mt-1">-${{ number_format($report['total_discounts'], 0, ',', '.') }}</p>
        </div>
        <div class="card-night p-3.5 border-night-750">
            <span class="text-[10px] uppercase font-bold text-slate-400">Costo Mercancía (COGS)</span>
            <p class="text-lg font-black font-mono text-rose-400 mt-1">-${{ number_format($report['cogs'], 0, ',', '.') }}</p>
        </div>
        <div class="card-night p-3.5 border-neon-green/30 bg-neon-green/5">
            <span class="text-[10px] uppercase font-black text-neon-green">Utilidad Bruta</span>
            <p class="text-lg font-black font-mono text-neon-green mt-1">${{ number_format($report['gross_profit'], 0, ',', '.') }}</p>
        </div>
        <div class="card-night p-3.5 border-neon-cyan/30 bg-neon-cyan/5">
            <span class="text-[10px] uppercase font-black text-neon-cyan">Margen Bruto (%)</span>
            <p class="text-lg font-black font-mono text-neon-cyan mt-1">{{ $report['profit_margin'] }}%</p>
        </div>
        <div class="card-night p-3.5 border-night-750">
            <span class="text-[10px] uppercase font-bold text-slate-400">Transacciones</span>
            <p class="text-lg font-black font-mono text-white mt-1">{{ $report['count'] }} ventas</p>
        </div>
    </div>

    <!-- Sales Details Table -->
    <div class="card-night p-4 flex-1 overflow-hidden flex flex-col">
        <h3 class="text-sm font-bold text-white mb-2">Detalle de Ventas Concretadas</h3>
        <div class="flex-1 overflow-y-auto">
            <div class="overflow-x-auto min-w-full">
                <table class="w-full text-left text-xs min-w-[850px]">
                <thead class="border-b border-night-800 text-slate-400 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-2">Venta #</th>
                        <th class="py-3 px-2">Fecha y Hora</th>
                        <th class="py-3 px-2">Cajero</th>
                        <th class="py-3 px-2">Medios de Pago</th>
                        <th class="py-3 px-2 text-right">Subtotal</th>
                        <th class="py-3 px-2 text-right">Total</th>
                        <th class="py-3 px-2 text-right">Costo Est.</th>
                        <th class="py-3 px-2 text-right">Margen</th>
                        <th class="py-3 px-2 text-right">Tirilla</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-night-800 font-mono">
                    @forelse($report['sales'] as $sale)
                        @php
                            $cost = $sale->calculateTotalCost();
                            $profit = $sale->total - $cost;
                        @endphp
                        <tr>
                            <td class="py-3 px-2 font-bold text-white">#{{ $sale->sale_number }}</td>
                            <td class="py-3 px-2 text-slate-400">{{ $sale->sold_at->format('d/m/Y h:i A') }}</td>
                            <td class="py-3 px-2 font-sans text-slate-300">{{ $sale->user->name }}</td>
                            <td class="py-3 px-2 font-sans">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($sale->payments as $p)
                                        <span class="badge-neon bg-night-850 text-slate-200 border border-night-750 text-[10px]">
                                            {{ $p->paymentMethod->name }}: ${{ number_format($p->amount, 0, ',', '.') }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="py-3 px-2 text-right text-slate-400">${{ number_format($sale->subtotal, 0, ',', '.') }}</td>
                            <td class="py-3 px-2 text-right font-bold text-white">${{ number_format($sale->total, 0, ',', '.') }}</td>
                            <td class="py-3 px-2 text-right text-rose-400">${{ number_format($cost, 0, ',', '.') }}</td>
                            <td class="py-3 px-2 text-right font-bold text-neon-green">+${{ number_format($profit, 0, ',', '.') }}</td>
                            <td class="py-3 px-2 text-right font-sans">
                                <a href="{{ route('pos.receipt', $sale->id) }}" target="_blank" class="text-neon-cyan hover:underline font-bold text-[11px]">
                                    Imprimir
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-500 font-sans">No hay transacciones registradas en este período.</td>
                        </tr>
                    @endforelse
                </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
