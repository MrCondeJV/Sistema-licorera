@extends('layouts.app', ['title' => 'Gastos Operativos'])

@section('content')
<div class="h-full flex flex-col space-y-4" x-data="{ showNewExpenseModal: false }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-black text-slate-900 dark:text-white">Gastos Operativos</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Control de egresos para cálculo real de utilidad operativa</p>
        </div>
        <button type="button" @click="showNewExpenseModal = true" class="btn-touch py-2 px-4 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-lg shadow-emerald-600/30 rounded-xl">
            + REGISTRAR GASTO
        </button>
    </div>

    <!-- Expenses Table -->
    <div class="card-night p-4 flex-1 overflow-hidden flex flex-col">
        <div class="flex-1 overflow-y-auto">
            <div class="overflow-x-auto min-w-full">
                <table class="w-full text-left text-xs min-w-[700px]">
                <thead class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-2">Fecha</th>
                        <th class="py-3 px-2">Descripción</th>
                        <th class="py-3 px-2">Medio de Pago</th>
                        <th class="py-3 px-2">Comprobante</th>
                        <th class="py-3 px-2">Registrado por</th>
                        <th class="py-3 px-2 text-right">Monto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($expenses as $exp)
                        <tr>
                            <td class="py-3 px-2 font-mono text-slate-600 dark:text-slate-300">{{ $exp->expense_date->format('d/m/Y') }}</td>
                            <td class="py-3 px-2 font-bold text-slate-900 dark:text-white max-w-xs truncate">{{ $exp->description }}</td>
                            <td class="py-3 px-2">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $exp->payment_method === 'cash' ? 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-700' : 'bg-cyan-100 dark:bg-cyan-950 text-cyan-700 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-700' }}">
                                    {{ $exp->payment_method === 'cash' ? 'Caja / Efectivo' : 'Banco / Transferencia' }}
                                </span>
                            </td>
                            <td class="py-3 px-2 font-mono text-slate-500 dark:text-slate-400">{{ $exp->receipt_number ?? 'S/N' }}</td>
                            <td class="py-3 px-2 text-slate-500 dark:text-slate-400">{{ $exp->user->name }}</td>
                            <td class="py-3 px-2 text-right font-mono font-bold text-rose-600 dark:text-rose-400">-${{ number_format($exp->amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 dark:text-slate-500">No hay gastos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
                </table>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-200 dark:border-slate-800">
            {{ $expenses->links() }}
        </div>
    </div>

    <!-- Modal: New Expense -->
    <div x-show="showNewExpenseModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" style="display: none;">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-black text-slate-900 dark:text-white mb-1">Registrar Gasto Operativo</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Si seleccionas pago en efectivo, se descontará de la caja abierta</p>

            <form action="{{ route('expenses.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Monto ($ COP) *</label>
                    <input type="number" name="amount" min="1" step="any" required autofocus
                           class="input-night w-full font-mono font-bold text-lg" placeholder="0">
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Medio de Pago *</label>
                    <select name="payment_method" required class="input-night w-full text-xs">
                        <option value="cash">Efectivo (Desde Caja Abierta)</option>
                        <option value="bank">Banco / Transferencia</option>
                        <option value="other">Otro</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Fecha *</label>
                    <input type="date" name="expense_date" value="{{ date('Y-m-d') }}" required
                           class="input-night w-full text-xs">
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Descripción / Detalle *</label>
                    <input type="text" name="description" required placeholder="Ej: Pago recibo de luz, aseo, flete, hielo..."
                           class="input-night w-full text-xs">
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="showNewExpenseModal = false" class="btn-touch flex-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl">Cancelar</button>
                    <button type="submit" class="btn-touch flex-1 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-lg shadow-emerald-600/30 rounded-xl">REGISTRAR</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
