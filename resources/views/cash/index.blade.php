@extends('layouts.app', ['title' => 'Turno de Caja'])

@section('content')
<div class="h-full flex flex-col space-y-4" x-data="{ showOpenModal: false, showCloseModal: false, showMovementModal: false, movementType: 'in' }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-black text-white flex flex-wrap items-center gap-2">
                <span>Gestión y Arqueo de Caja</span>
                @if($activeSession)
                    <span class="badge-neon bg-emerald-950 text-emerald-400 border border-emerald-700 text-xs">Caja Abierta</span>
                @else
                    <span class="badge-neon bg-rose-950 text-rose-400 border border-rose-700 text-xs">Caja Cerrada</span>
                @endif
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Control estricto de efectivo físico y segregación de medios electrónicos</p>
        </div>

        <div class="flex flex-wrap gap-2">
            @if($activeSession)
                <button type="button" @click="showMovementModal = true; movementType = 'in'" class="btn-touch py-2.5 px-3 bg-night-800 hover:bg-night-750 text-emerald-400 border border-night-750 text-xs font-bold">
                    + Entrada
                </button>
                <button type="button" @click="showMovementModal = true; movementType = 'out'" class="btn-touch py-2.5 px-3 bg-night-800 hover:bg-night-750 text-rose-400 border border-night-750 text-xs font-bold">
                    - Retiro
                </button>
                <button type="button" @click="showCloseModal = true" class="btn-touch py-2.5 px-4 bg-rose-950 hover:bg-rose-900 border border-rose-600/50 text-rose-300 text-xs font-black">
                    🔒 ARQUEO Y CIERRE
                </button>
            @else
                <button type="button" @click="showOpenModal = true" class="btn-touch py-2.5 px-5 bg-gradient-to-r from-neon-green to-emerald-400 text-night-950 text-sm font-black shadow-lg shadow-neon-green/20">
                    🔓 ABRIR TURNO DE CAJA
                </button>
            @endif
        </div>
    </div>

    @if($activeSession)
        <!-- Cash Summary Metrics -->
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="card-night p-3.5 border-night-750">
                <span class="text-[10px] uppercase font-bold text-slate-400">Base Inicial</span>
                <p class="text-lg font-black font-mono text-white mt-1">${{ number_format($summary['opening_amount'], 0, ',', '.') }}</p>
            </div>
            <div class="card-night p-3.5 border-emerald-500/20 bg-emerald-950/10">
                <span class="text-[10px] uppercase font-bold text-emerald-400">Ventas en Efectivo</span>
                <p class="text-lg font-black font-mono text-emerald-400 mt-1">+${{ number_format($summary['cash_sales'], 0, ',', '.') }}</p>
            </div>
            <div class="card-night p-3.5 border-night-750">
                <span class="text-[10px] uppercase font-bold text-slate-400">Entradas Manuales</span>
                <p class="text-lg font-black font-mono text-white mt-1">+${{ number_format($summary['cash_in'], 0, ',', '.') }}</p>
            </div>
            <div class="card-night p-3.5 border-night-750">
                <span class="text-[10px] uppercase font-bold text-slate-400">Retiros de Caja</span>
                <p class="text-lg font-black font-mono text-white mt-1">-${{ number_format($summary['cash_out'], 0, ',', '.') }}</p>
            </div>
            <div class="card-night p-3.5 border-rose-500/20 bg-rose-950/10">
                <span class="text-[10px] uppercase font-bold text-rose-400">Gastos en Efectivo</span>
                <p class="text-lg font-black font-mono text-rose-400 mt-1">-${{ number_format($summary['cash_expenses'], 0, ',', '.') }}</p>
            </div>
            <div class="card-night p-3.5 border-neon-green/40 bg-neon-green/5 shadow-lg shadow-neon-green/10">
                <span class="text-[10px] uppercase font-black text-neon-green">Efectivo Esperado</span>
                <p class="text-xl font-black font-mono text-neon-green mt-1">${{ number_format($summary['expected_cash'], 0, ',', '.') }}</p>
            </div>
        </div>

        <!-- Segregated Electronic / Digital Payments Card -->
        <div class="card-night p-4 border-neon-cyan/30 bg-neon-cyan/5">
            <h3 class="text-xs font-black uppercase text-neon-cyan tracking-wider mb-2 flex items-center gap-2">
                <span>💳 Pagos Electrónicos del Turno (No cuentan como efectivo físico)</span>
            </h3>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                @forelse($summary['electronic_payments'] as $ep)
                    <div class="p-3 bg-night-900 border border-night-750 rounded-xl">
                        <span class="text-xs text-slate-400 font-semibold">{{ $ep['method'] }}</span>
                        <p class="text-base font-bold font-mono text-neon-cyan mt-0.5">${{ number_format($ep['total'], 0, ',', '.') }}</p>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 col-span-4">No se han registrado pagos electrónicos en este turno aún.</p>
                @endforelse
            </div>
        </div>

        <!-- Movements & Shift History -->
        <div class="card-night p-4 flex-1 overflow-hidden flex flex-col">
            <h3 class="text-sm font-bold text-white mb-2">Movimientos del Turno Actual</h3>
            <div class="flex-1 overflow-y-auto pr-1">
                <div class="overflow-x-auto min-w-full">
                    <table class="w-full text-left text-xs min-w-[500px]">
                        <thead class="border-b border-night-800 text-slate-400 font-bold uppercase tracking-wider">
                            <tr>
                                <th class="py-2">Hora</th>
                                <th class="py-2">Tipo</th>
                                <th class="py-2">Motivo</th>
                                <th class="py-2 text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-night-800">
                            @forelse($activeSession->movements as $mov)
                                <tr>
                                    <td class="py-2.5 font-mono text-slate-400">{{ $mov->created_at->format('h:i A') }}</td>
                                    <td class="py-2.5">
                                        <span class="badge-neon {{ $mov->movement_type === 'in' ? 'bg-emerald-950 text-emerald-300 border-emerald-700' : 'bg-rose-950 text-rose-300 border-rose-700' }} text-[10px]">
                                            {{ $mov->movement_type === 'in' ? 'INGRESO' : 'RETIRO' }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 text-slate-200 font-medium">{{ $mov->reason }}</td>
                                    <td class="py-2.5 text-right font-mono font-bold {{ $mov->movement_type === 'in' ? 'text-emerald-400' : 'text-rose-400' }}">
                                        {{ $mov->movement_type === 'in' ? '+' : '-' }}${{ number_format($mov->amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-4 text-center text-slate-500">No hay movimientos manuales en este turno.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @else
        <!-- Empty State: No active cash session -->
        <div class="card-night p-10 flex flex-col items-center justify-center text-center max-w-md mx-auto my-auto">
            <div class="w-16 h-16 rounded-full bg-night-850 border border-night-750 flex items-center justify-center text-slate-400 mb-3">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h3 class="text-base font-bold text-white mb-1">No tienes un turno de caja abierto</h3>
            <p class="text-xs text-slate-400 mb-4">Abre turno de caja con tu base inicial en efectivo para comenzar a registrar ventas.</p>
            <button type="button" @click="showOpenModal = true" class="btn-touch bg-gradient-to-r from-neon-green to-emerald-400 text-night-950 font-black text-sm px-6">
                ABRIR TURNO DE CAJA
            </button>
        </div>
    @endif

    <!-- Modal: Open Session -->
    <div x-show="showOpenModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" style="display: none;">
        <div class="bg-night-900 border border-night-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-black text-white mb-1">Apertura de Turno de Caja</h3>
            <p class="text-xs text-slate-400 mb-4">Ingresa el dinero base en efectivo disponible</p>

            <form action="{{ route('cash.open') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Caja Registradora</label>
                    <select name="cash_register_id" required class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3.5 py-2.5 text-sm focus:border-neon-green focus:outline-none">
                        @foreach($registers as $reg)
                            <option value="{{ $reg->id }}">{{ $reg->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Base en Efectivo ($ COP) *</label>
                    <input type="number" name="opening_amount" min="0" step="1000" value="100000" required autofocus
                           class="w-full bg-night-850 border border-night-750 text-white font-mono font-bold text-lg rounded-xl px-4 py-3 focus:border-neon-green focus:outline-none">
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="showOpenModal = false" class="btn-touch flex-1 bg-night-800 text-slate-300 text-xs font-bold">Cancelar</button>
                    <button type="submit" class="btn-touch flex-1 bg-neon-green text-night-950 text-xs font-black">ABRIR CAJA</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Close Session & Blind Count Reconciliation -->
    @if($activeSession)
        <div x-show="showCloseModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" style="display: none;">
            <div class="bg-night-900 border border-night-800 rounded-3xl p-6 max-w-md w-full shadow-2xl max-h-[90vh] overflow-y-auto">
                <h3 class="text-base font-black text-white mb-1">Arqueo y Cierre de Turno</h3>
                <p class="text-xs text-slate-400 mb-4">Introduce el efectivo contado físicamente en el cajón</p>

                <form action="{{ route('cash.close', $activeSession->id) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Efectivo Contado Físico ($ COP) *</label>
                        <input type="number" name="counted_cash" min="0" step="100" required autofocus
                                class="w-full bg-night-850 border border-night-750 text-white font-mono font-black text-xl rounded-xl px-4 py-3 focus:border-neon-green focus:outline-none">
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Observaciones / Justificación de Diferencia</label>
                        <textarea name="closing_notes" rows="3" placeholder="Si existe sobrante o faltante, explica el motivo aquí..."
                                  class="w-full bg-night-850 border border-night-750 text-white rounded-xl p-3 text-xs focus:border-neon-green focus:outline-none"></textarea>
                    </div>

                    <div class="flex gap-2">
                        <button type="button" @click="showCloseModal = false" class="btn-touch flex-1 bg-night-800 text-slate-300 text-xs font-bold">Cancelar</button>
                        <button type="submit" class="btn-touch flex-1 bg-rose-600 hover:bg-rose-500 text-white text-xs font-black">CONFIRMAR CIERRE</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: Cash Movement In / Out -->
        <div x-show="showMovementModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" style="display: none;">
            <div class="bg-night-900 border border-night-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl max-h-[90vh] overflow-y-auto">
                <h3 class="text-base font-black text-white mb-1" x-text="movementType === 'in' ? 'Ingreso de Efectivo' : 'Retiro de Efectivo'"></h3>
                <p class="text-xs text-slate-400 mb-4">Registra entradas o salidas manuales de dinero</p>

                <form action="{{ route('cash.movement', $activeSession->id) }}" method="POST">
                    @csrf
                    <input type="hidden" name="movement_type" :value="movementType">

                    <div class="mb-3">
                        <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Monto ($ COP) *</label>
                        <input type="number" name="amount" min="1" step="1000" required autofocus
                               class="w-full bg-night-850 border border-night-750 text-white font-mono font-bold text-lg rounded-xl px-4 py-2.5 focus:border-neon-green focus:outline-none">
                    </div>

                    <div class="mb-4">
                        <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Motivo / Justificación *</label>
                        <input type="text" name="reason" required placeholder="Ej: Cambio menudo, Pago proveedor..."
                               class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3 py-2 text-xs focus:border-neon-green focus:outline-none">
                    </div>

                    <div class="flex gap-2">
                        <button type="button" @click="showMovementModal = false" class="btn-touch flex-1 bg-night-800 text-slate-300 text-xs font-bold">Cancelar</button>
                        <button type="submit" class="btn-touch flex-1 bg-neon-green text-night-950 text-xs font-black">REGISTRAR</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
