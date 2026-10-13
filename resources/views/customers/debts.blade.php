@extends('layouts.app', ['title' => 'Cartera: ' . $customer->name])

@section('content')
<div class="h-full flex flex-col space-y-4" x-data="{ showNewDebtModal: false, showPaymentModal: false }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-xl font-black text-white">{{ $customer->name }}</h2>
                <span class="badge-neon {{ $customer->current_debt > 0 ? 'bg-amber-950 text-amber-300 border-amber-600' : 'bg-emerald-950 text-emerald-300 border-emerald-600' }} text-xs">
                    {{ $customer->current_debt > 0 ? 'Con Saldo Pendiente' : 'Al Día' }}
                </span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">Tel: {{ $customer->phone ?? 'S/N' }} &bull; Cupo límite: ${{ number_format($customer->credit_limit, 0, ',', '.') }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('customers.index') }}" class="btn-touch py-2 px-3 bg-night-800 text-slate-300 text-xs font-bold">
                &larr; Volver
            </a>
            <button type="button" @click="showNewDebtModal = true" class="btn-touch py-2 px-3 bg-amber-600 hover:bg-amber-500 text-night-950 text-xs font-black shadow-md">
                + NUEVO FIADO
            </button>
            @if($customer->current_debt > 0)
                <button type="button" @click="showPaymentModal = true" class="btn-touch py-2 px-4 bg-neon-green hover:bg-emerald-400 text-night-950 text-xs font-black shadow-lg shadow-neon-green/20">
                    💵 REGISTRAR ABONO
                </button>
            @endif
        </div>
    </div>

    <!-- Summary Balance Banner -->
    <div class="card-night p-4 border-amber-500/30 bg-amber-950/15 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <span class="text-xs uppercase font-bold text-amber-300">Saldo Total Pendiente por Cobrar</span>
            <p class="text-2xl sm:text-3xl font-black font-mono text-amber-400 mt-0.5">${{ number_format($customer->current_debt, 0, ',', '.') }} COP</p>
        </div>
        <div class="text-left sm:text-right text-xs text-slate-400">
            <p>Cupo disponible: <strong class="text-white">${{ number_format(max(0, $customer->credit_limit - $customer->current_debt), 0, ',', '.') }}</strong></p>
        </div>
    </div>

    <!-- Debts & Abonos 2-Column Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 flex-1 overflow-hidden">
        <!-- Col 1: Debts / Fiados -->
        <div class="card-night p-4 flex flex-col overflow-hidden min-h-[250px]">
            <h3 class="text-sm font-bold text-white mb-2">Historial de Fiados / Deudas</h3>
            <div class="flex-1 overflow-y-auto pr-1">
                <div class="overflow-x-auto min-w-full">
                    <table class="w-full text-left text-xs min-w-[340px]">
                        <thead class="border-b border-night-800 text-slate-400 font-bold uppercase">
                            <tr>
                                <th class="py-2">Fecha</th>
                                <th class="py-2">Detalle</th>
                                <th class="py-2 text-right">Original</th>
                                <th class="py-2 text-right">Pendiente</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-night-800 font-mono">
                            @forelse($customer->debts as $d)
                                <tr>
                                    <td class="py-2 text-slate-400">{{ $d->created_at->format('d/m/Y') }}</td>
                                    <td class="py-2 font-sans text-slate-200">{{ $d->notes ?? 'Fiado manual' }}</td>
                                    <td class="py-2 text-right text-slate-400">${{ number_format($d->original_amount, 0, ',', '.') }}</td>
                                    <td class="py-2 text-right font-bold {{ $d->remaining_amount > 0 ? 'text-amber-400' : 'text-emerald-400' }}">
                                        ${{ number_format($d->remaining_amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-4 text-center text-slate-500 font-sans">No hay deudas registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Col 2: Abonos / Payments -->
        <div class="card-night p-4 flex flex-col overflow-hidden min-h-[250px]">
            <h3 class="text-sm font-bold text-white mb-2">Historial de Abonos Recibidos</h3>
            <div class="flex-1 overflow-y-auto pr-1">
                <div class="overflow-x-auto min-w-full">
                    <table class="w-full text-left text-xs min-w-[360px]">
                        <thead class="border-b border-night-800 text-slate-400 font-bold uppercase">
                            <tr>
                                <th class="py-2">Fecha</th>
                                <th class="py-2">Método</th>
                                <th class="py-2">Referencia</th>
                                <th class="py-2 text-right">Monto Abonado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-night-800 font-mono">
                            @forelse($customer->payments as $p)
                                <tr>
                                    <td class="py-2 text-slate-400">{{ $p->created_at->format('d/m/Y h:i A') }}</td>
                                    <td class="py-2 font-sans text-slate-200">{{ $p->paymentMethod->name }}</td>
                                    <td class="py-2 text-slate-400">{{ $p->reference ?? 'S/R' }}</td>
                                    <td class="py-2 text-right font-bold text-neon-green">+${{ number_format($p->amount, 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-4 text-center text-slate-500 font-sans">No hay abonos registrados todavía.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: New Fiado -->
    <div x-show="showNewDebtModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" style="display: none;">
        <div class="bg-night-900 border border-night-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-black text-white mb-1">Registrar Nuevo Fiado</h3>
            <p class="text-xs text-slate-400 mb-4">Aumenta el saldo pendiente del cliente</p>

            <form action="{{ route('customers.debts.store', $customer->id) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Valor del Fiado ($ COP) *</label>
                    <input type="number" name="amount" min="1" step="1000" required autofocus
                           class="w-full bg-night-850 border border-night-750 text-white font-mono font-bold text-lg rounded-xl px-4 py-2.5 focus:border-neon-green focus:outline-none">
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Concepto / Notas *</label>
                    <input type="text" name="notes" required placeholder="Ej: Consumo de 3 cervezas y snacks..."
                           class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3 py-2 text-xs focus:border-neon-green focus:outline-none">
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="showNewDebtModal = false" class="btn-touch flex-1 bg-night-800 text-slate-300 text-xs font-bold">Cancelar</button>
                    <button type="submit" class="btn-touch flex-1 bg-amber-500 text-night-950 text-xs font-black">CONFIRMAR FIADO</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Register Abono -->
    <div x-show="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" style="display: none;">
        <div class="bg-night-900 border border-night-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-black text-white mb-1">Registrar Abono a Deuda</h3>
            <p class="text-xs text-slate-400 mb-4">Se registra como movimiento financiero independiente</p>

            <form action="{{ route('customers.payments.store', $customer->id) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Valor del Abono ($ COP) *</label>
                    <input type="number" name="amount" max="{{ $customer->current_debt }}" min="1" step="1000" value="{{ $customer->current_debt }}" required autofocus
                           class="w-full bg-night-850 border border-night-750 text-white font-mono font-bold text-lg rounded-xl px-4 py-2.5 focus:border-neon-green focus:outline-none">
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Medio de Pago *</label>
                    <select name="payment_method_id" required class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3 py-2 text-xs focus:border-neon-green focus:outline-none">
                        @foreach($paymentMethods as $pm)
                            <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Referencia / Comprobante</label>
                    <input type="text" name="reference" placeholder="Ej: Transf. Nequi #87654"
                           class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3 py-2 text-xs focus:border-neon-green focus:outline-none">
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="showPaymentModal = false" class="btn-touch flex-1 bg-night-800 text-slate-300 text-xs font-bold">Cancelar</button>
                    <button type="submit" class="btn-touch flex-1 bg-neon-green text-night-950 text-xs font-black">APLICAR ABONO</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
