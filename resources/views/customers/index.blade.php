@extends('layouts.app', ['title' => 'Clientes y Cartera'])

@section('content')
<div class="h-full flex flex-col space-y-4" x-data="{ showNewCustomerModal: false }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-black text-slate-900 dark:text-white">Clientes y Cartera (Fiados)</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Control de clientes registrados, límites de crédito y saldos pendientes</p>
        </div>
        <button type="button" @click="showNewCustomerModal = true" class="btn-touch py-2 px-4 bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:hover:bg-slate-100 dark:text-slate-900 border border-slate-800 dark:border-white text-xs font-semibold rounded-xl shadow-xs transition-colors">
            + NUEVO CLIENTE
        </button>
    </div>

    <!-- Table -->
    <div class="card-night p-4 flex-1 overflow-hidden flex flex-col">
        <div class="flex-1 overflow-y-auto">
            <div class="overflow-x-auto min-w-full">
                <table class="w-full text-left text-sm min-w-[700px]">
                <thead class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-3">Cliente</th>
                        <th class="py-3.5 px-3">Teléfono</th>
                        <th class="py-3.5 px-3">Identificación</th>
                        <th class="py-3.5 px-3 text-right">Cupo de Crédito</th>
                        <th class="py-3.5 px-3 text-right">Deuda Actual</th>
                        <th class="py-3.5 px-3 text-right sticky right-0 bg-white dark:bg-slate-900 z-10 border-l border-slate-100 dark:border-slate-800 shadow-[-6px_0_10px_-2px_rgba(0,0,0,0.06)]">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($customers as $c)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-3.5 px-3 font-bold text-slate-900 dark:text-white text-sm">{{ $c->name }}</td>
                            <td class="py-3.5 px-3 font-mono text-slate-600 dark:text-slate-300">{{ $c->phone ?? 'Sin teléfono' }}</td>
                            <td class="py-3.5 px-3 font-mono text-slate-500 dark:text-slate-400">{{ $c->tax_id ?? 'N/A' }}</td>
                            <td class="py-3.5 px-3 text-right font-mono text-slate-600 dark:text-slate-300">${{ number_format($c->credit_limit, 0, ',', '.') }}</td>
                            <td class="py-3.5 px-3 text-right font-mono font-black {{ $c->current_debt > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                ${{ number_format($c->current_debt, 0, ',', '.') }}
                            </td>
                            <td class="py-3.5 px-3 text-right sticky right-0 bg-white dark:bg-slate-900 z-10 border-l border-slate-100 dark:border-slate-800 shadow-[-6px_0_10px_-2px_rgba(0,0,0,0.06)]">
                                <a href="{{ route('customers.debts', $c->id) }}"
                                   class="px-3 py-1.5 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 font-medium text-xs rounded-lg shadow-xs transition-colors inline-block">
                                    Ver Fiados y Abonos &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 dark:text-slate-500">No hay clientes registrados aún.</td>
                        </tr>
                    @endforelse
                </tbody>
                </table>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-200 dark:border-slate-800">
            {{ $customers->links() }}
        </div>
    </div>

    <!-- Modal: New Customer -->
    <div x-show="showNewCustomerModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" style="display: none;">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-black text-slate-900 dark:text-white mb-1">Registrar Nuevo Cliente</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Información básica para control de cartera</p>

            <form action="{{ route('customers.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Nombre Completo *</label>
                    <input type="text" name="name" required placeholder="Ej: Camilo Torres" autofocus
                           class="input-night w-full text-xs">
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Teléfono / WhatsApp</label>
                    <input type="text" name="phone" placeholder="Ej: 300 123 4567"
                           class="input-night w-full text-xs">
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Cédula / NIT</label>
                    <input type="text" name="tax_id" placeholder="Ej: 10203040"
                           class="input-night w-full text-xs">
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Límite de Crédito ($ COP)</label>
                    <input type="number" name="credit_limit" min="0" step="10000" value="0"
                           class="input-night w-full font-mono text-xs">
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="showNewCustomerModal = false" class="btn-touch flex-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl">Cancelar</button>
                    <button type="submit" class="btn-touch flex-1 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-lg shadow-emerald-600/30 rounded-xl">GUARDAR</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
