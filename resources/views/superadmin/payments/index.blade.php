@extends('layouts.app', ['title' => 'Histórico de Pagos SaaS'])

@section('content')
<div class="h-full flex flex-col space-y-4">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">Historial de Pagos SaaS</h2>
                <span class="px-2.5 py-0.5 rounded-full bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800 text-[10px] font-black uppercase tracking-wider">
                    Todas las Licoreras
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Auditoría completa de transacciones, cobros de membresías y renovaciones Wompi de todas las licoreras registradas
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('superadmin.index') }}" class="btn-touch py-2.5 px-4 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl border border-slate-300 dark:border-slate-700 flex items-center gap-2 shadow-sm transition-all">
                <span>🏢 Ver Licoreras</span>
            </a>
        </div>
    </div>

    <!-- Revenue KPIs -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="card-night p-4 border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-950/20 rounded-2xl">
            <span class="text-[11px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Total Recaudado (Aprobado)</span>
            <p class="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400 mt-1">${{ number_format($totalRevenue, 0, ',', '.') }}</p>
            <span class="text-[10px] text-slate-400">Pesos colombianos (COP)</span>
        </div>
        <div class="card-night p-4 border-slate-200 dark:border-slate-800 rounded-2xl">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Pagos Aprobados</span>
            <p class="text-2xl font-black font-mono text-slate-900 dark:text-white mt-1">{{ $approvedCount }}</p>
            <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">Membresías cobradas con éxito</span>
        </div>
        <div class="card-night p-4 border-amber-500/30 bg-amber-50/50 dark:bg-amber-950/20 rounded-2xl">
            <span class="text-[11px] font-bold uppercase tracking-wider text-amber-600 dark:text-amber-400">Pagos Pendientes</span>
            <p class="text-2xl font-black font-mono text-amber-600 dark:text-amber-400 mt-1">{{ $pendingCount }}</p>
            <span class="text-[10px] text-slate-400">En espera de validación bancaria</span>
        </div>
        <div class="card-night p-4 border-slate-200 dark:border-slate-800 rounded-2xl">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Rechazados / Fallidos</span>
            <p class="text-2xl font-black font-mono text-rose-600 dark:text-rose-400 mt-1">{{ $declinedCount }}</p>
            <span class="text-[10px] text-slate-400">Intentos cancelados o declinados</span>
        </div>
    </div>

    <!-- Filter Card -->
    <div class="card-night p-4 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <form method="GET" action="{{ route('superadmin.payments') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- Filter: Tenant -->
            <div>
                <label for="tenant_id" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Licorera / Tenant:</label>
                <select name="tenant_id" id="tenant_id" class="w-full text-xs font-bold py-2 px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">-- Todas las Licoreras --</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ request('tenant_id') == $t->id ? 'selected' : '' }}>
                            {{ $t->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter: Status -->
            <div>
                <label for="status" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Estado del Pago:</label>
                <select name="status" id="status" class="w-full text-xs font-bold py-2 px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">-- Todos los Estados --</option>
                    <option value="APPROVED" {{ request('status') === 'APPROVED' ? 'selected' : '' }}>✅ APROBADO</option>
                    <option value="PENDING" {{ request('status') === 'PENDING' ? 'selected' : '' }}>⏳ PENDIENTE</option>
                    <option value="DECLINED" {{ request('status') === 'DECLINED' ? 'selected' : '' }}>❌ DECLINADO</option>
                    <option value="ERROR" {{ request('status') === 'ERROR' ? 'selected' : '' }}>⚠️ ERROR</option>
                </select>
            </div>

            <!-- Filter: Plan -->
            <div>
                <label for="plan_id" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Plan SaaS:</label>
                <select name="plan_id" id="plan_id" class="w-full text-xs font-bold py-2 px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">-- Todos los Planes --</option>
                    @foreach($plans as $p)
                        <option value="{{ $p->id }}" {{ request('plan_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter: Search -->
            <div>
                <label for="search" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Buscar:</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}"
                       placeholder="Referencia, ID Wompi o empresa..."
                       class="w-full text-xs font-medium py-2 px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>

            <!-- Action Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-2 px-3 bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs rounded-xl shadow-md transition-all">
                    Filtrar
                </button>
                <a href="{{ route('superadmin.payments') }}" class="py-2 px-3 bg-slate-200 hover:bg-slate-300 dark:bg-slate-700 dark:hover:bg-slate-650 text-slate-700 dark:text-slate-200 font-bold text-xs rounded-xl transition-all" title="Restablecer">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    <!-- Payments Table -->
    <div class="card-night p-4 flex-1 flex flex-col rounded-2xl shadow-sm">
        <div class="overflow-x-auto flex-1">
            <table class="w-full text-left text-xs min-w-[850px]">
                <thead class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-3">Fecha y Hora</th>
                        <th class="py-3 px-3">Licorera / Negocio</th>
                        <th class="py-3 px-3">Plan SaaS</th>
                        <th class="py-3 px-3">Monto (COP)</th>
                        <th class="py-3 px-3">Referencia</th>
                        <th class="py-3 px-3">ID Transacción</th>
                        <th class="py-3 px-3">Método</th>
                        <th class="py-3 px-3 text-right">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($payments as $pay)
                        <tr>
                            <td class="py-3 px-3 font-mono text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                {{ $pay->created_at->format('d/m/Y h:i A') }}
                            </td>
                            <td class="py-3 px-3">
                                <span class="font-bold text-slate-900 dark:text-white block">{{ $pay->tenant->name ?? 'Licorera Desconocida' }}</span>
                                <span class="text-[10px] font-mono text-slate-400">{{ $pay->tenant->email ?? $pay->tenant->uuid }}</span>
                            </td>
                            <td class="py-3 px-3 font-bold text-slate-700 dark:text-slate-300">
                                {{ $pay->plan->name ?? 'Plan Personalizado' }}
                            </td>
                            <td class="py-3 px-3 font-mono font-black text-emerald-600 dark:text-emerald-400 text-sm whitespace-nowrap">
                                {{ $pay->formatted_amount }}
                            </td>
                            <td class="py-3 px-3 font-mono font-bold text-slate-900 dark:text-white">
                                {{ $pay->reference }}
                            </td>
                            <td class="py-3 px-3 font-mono text-slate-400 text-[11px]">
                                {{ $pay->wompi_transaction_id ?? 'N/A' }}
                            </td>
                            <td class="py-3 px-3 uppercase text-[10px] font-bold text-slate-500">
                                {{ $pay->payment_method_type ?? 'Wompi Checkout' }}
                            </td>
                            <td class="py-3 px-3 text-right">
                                @if($pay->status === 'APPROVED')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                                        <span>✓</span> APROBADO
                                    </span>
                                @elseif($pay->status === 'PENDING')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-300 dark:border-amber-700">
                                        <span>⏳</span> PENDIENTE
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border border-rose-300 dark:border-rose-700">
                                        <span>✕</span> {{ $pay->status }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                No se encontraron registros de pagos con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-3 border-t border-slate-200 dark:border-slate-800">
            {{ $payments->links() }}
        </div>
    </div>
</div>
@endsection
