@extends('layouts.app', ['title' => 'Gastos Operativos'])

@section('content')
<div class="h-full flex flex-col space-y-4" x-data="{ showNewExpenseModal: false }">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-black text-white">Gastos Operativos</h2>
            <p class="text-xs text-slate-400 mt-0.5">Control de egresos para cálculo real de utilidad operativa</p>
        </div>
        <button type="button" @click="showNewExpenseModal = true" class="btn-touch py-2 px-4 bg-gradient-to-r from-neon-green to-emerald-400 text-night-950 text-xs font-black shadow-lg shadow-neon-green/20">
            + REGISTRAR GASTO
        </button>
    </div>

    <!-- Expenses Table -->
    <div class="card-night p-4 flex-1 overflow-hidden flex flex-col">
        <div class="flex-1 overflow-y-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-night-800 text-slate-400 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-2">Fecha</th>
                        <th class="py-3 px-2">Categoría</th>
                        <th class="py-3 px-2">Descripción</th>
                        <th class="py-3 px-2">Medio de Pago</th>
                        <th class="py-3 px-2">Comprobante</th>
                        <th class="py-3 px-2">Registrado por</th>
                        <th class="py-3 px-2 text-right">Monto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-night-800">
                    @forelse($expenses as $exp)
                        <tr>
                            <td class="py-3 px-2 font-mono text-slate-300">{{ $exp->expense_date->format('d/m/Y') }}</td>
                            <td class="py-3 px-2 font-bold text-white">{{ $exp->category->name }}</td>
                            <td class="py-3 px-2 text-slate-300 max-w-xs truncate">{{ $exp->description }}</td>
                            <td class="py-3 px-2">
                                <span class="badge-neon {{ $exp->payment_method === 'cash' ? 'bg-amber-950 text-amber-300 border-amber-700' : 'bg-cyan-950 text-cyan-300 border-cyan-700' }} text-[10px]">
                                    {{ $exp->payment_method === 'cash' ? 'Caja / Efectivo' : 'Banco / Transferencia' }}
                                </span>
                            </td>
                            <td class="py-3 px-2 font-mono text-slate-400">{{ $exp->receipt_number ?? 'S/N' }}</td>
                            <td class="py-3 px-2 text-slate-400">{{ $exp->user->name }}</td>
                            <td class="py-3 px-2 text-right font-mono font-bold text-rose-400">-${{ number_format($exp->amount, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500">No hay gastos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-3 border-t border-night-800">
            {{ $expenses->links() }}
        </div>
    </div>

    <!-- Modal: New Expense -->
    <div x-show="showNewExpenseModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" style="display: none;">
        <div class="bg-night-900 border border-night-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl">
            <h3 class="text-base font-black text-white mb-1">Registrar Gasto Operativo</h3>
            <p class="text-xs text-slate-400 mb-4">Si seleccionas pago en efectivo, se descontará de la caja abierta</p>

            <form action="{{ route('expenses.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Categoría del Gasto *</label>
                    <select name="expense_category_id" required class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3 py-2 text-xs focus:border-neon-green focus:outline-none">
                        @foreach($categories as $ec)
                            <option value="{{ $ec->id }}">{{ $ec->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Monto ($ COP) *</label>
                    <input type="number" name="amount" min="1" step="1000" required autofocus
                           class="w-full bg-night-850 border border-night-750 text-white font-mono font-bold text-lg rounded-xl px-4 py-2.5 focus:border-neon-green focus:outline-none">
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Medio de Pago *</label>
                    <select name="payment_method" required class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3 py-2 text-xs focus:border-neon-green focus:outline-none">
                        <option value="cash">Efectivo (Desde Caja Abierta)</option>
                        <option value="bank">Banco / Transferencia</option>
                        <option value="other">Otro</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Fecha *</label>
                    <input type="date" name="expense_date" value="{{ date('Y-m-d') }}" required
                           class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3 py-2 text-xs focus:border-neon-green focus:outline-none">
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Descripción / Proveedor *</label>
                    <input type="text" name="description" required placeholder="Ej: Pago de recibo de luz EPM..."
                           class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3 py-2 text-xs focus:border-neon-green focus:outline-none">
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="showNewExpenseModal = false" class="btn-touch flex-1 bg-night-800 text-slate-300 text-xs font-bold">Cancelar</button>
                    <button type="submit" class="btn-touch flex-1 bg-neon-green text-night-950 text-xs font-black">REGISTRAR</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
