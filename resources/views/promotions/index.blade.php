@extends('layouts.app', ['title' => 'Promociones y Happy Hour'])

@section('content')
<div class="h-full flex flex-col space-y-4" x-data="{ showNewPromoModal: false, promoType: 'happy_hour' }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-black text-slate-900 dark:text-white">Promociones y Happy Hour</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Precios especiales por horario, cantidad y días de la semana</p>
        </div>
        <button type="button" @click="showNewPromoModal = true" class="btn-touch py-2 px-4 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-lg shadow-emerald-600/30 rounded-xl">
            + NUEVA PROMOCIÓN
        </button>
    </div>

    <!-- Promotions Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 flex-1 overflow-y-auto pb-4">
        @forelse($promotions as $promo)
            <div class="card-night p-5 flex flex-col justify-between border-slate-200 dark:border-slate-800 {{ $promo->isCurrentlyActive() ? 'border-emerald-500/50 shadow-md shadow-emerald-500/10' : 'opacity-80' }}">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $promo->isCurrentlyActive() ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700' }}">
                            {{ $promo->isCurrentlyActive() ? '🟢 ACTIVA AHORA' : ($promo->is_active ? 'PROGRAMADA' : 'INACTIVA') }}
                        </span>
                        <span class="text-xs font-mono font-bold text-cyan-600 dark:text-cyan-400">
                            {{ $promo->discount_percentage ? $promo->discount_percentage . '% OFF' : '$' . number_format($promo->fixed_price, 0, ',', '.') }}
                        </span>
                    </div>

                    <h3 class="text-base font-black text-slate-900 dark:text-white mb-1">{{ $promo->name }}</h3>

                    <div class="space-y-1 text-xs text-slate-500 dark:text-slate-400 mt-2 font-medium">
                        @if($promo->start_time && $promo->end_time)
                            <p class="flex items-center gap-1.5">
                                <span>⏰ Horario:</span>
                                <strong class="text-slate-800 dark:text-slate-200 font-mono">{{ $promo->start_time }} a {{ $promo->end_time }}</strong>
                            </p>
                        @endif
                        @if($promo->min_quantity > 1)
                            <p class="flex items-center gap-1.5">
                                <span>📦 Cantidad mínima:</span>
                                <strong class="text-slate-800 dark:text-slate-200 font-mono">{{ $promo->min_quantity }} unidades</strong>
                            </p>
                        @endif
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400">Estado:</span>
                    <form action="{{ route('promotions.toggle', $promo->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-3 py-1 rounded-xl text-xs font-bold transition-all {{ $promo->is_active ? 'bg-slate-100 hover:bg-rose-100 text-rose-600 dark:bg-slate-800 dark:hover:bg-rose-950 dark:text-rose-300' : 'bg-slate-100 hover:bg-emerald-100 text-emerald-600 dark:bg-slate-800 dark:hover:bg-emerald-950 dark:text-emerald-300' }}">
                            {{ $promo->is_active ? 'Desactivar' : 'Activar' }}
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="card-night p-8 text-center text-slate-400 dark:text-slate-500 col-span-3">
                No hay promociones registradas. Crea una promoción o Happy Hour para incentivar las ventas.
            </div>
        @endforelse
    </div>

    <!-- Modal: New Promo -->
    <div x-show="showNewPromoModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" style="display: none;">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-black text-slate-900 dark:text-white mb-1">Nueva Promoción / Happy Hour</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Los descuentos son calculados y congelados en el servidor</p>

            <form action="{{ route('promotions.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Nombre de la Promoción *</label>
                    <input type="text" name="name" required placeholder="Ej: Happy Hour Cervezas 2x1..." autofocus
                           class="input-night w-full text-xs">
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Tipo de Promoción *</label>
                    <select name="type" x-model="promoType" required class="input-night w-full text-xs">
                        <option value="happy_hour">Happy Hour (Horario Especial)</option>
                        <option value="discount_percentage">Descuento Porcentual</option>
                        <option value="fixed_price">Precio Fijo Especial</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Porcentaje Descuento (%)</label>
                        <input type="number" name="discount_percentage" min="1" max="100" placeholder="Ej: 20"
                               class="input-night w-full font-mono text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Cantidad Mínima</label>
                        <input type="number" name="min_quantity" min="1" value="1" required
                               class="input-night w-full font-mono text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Hora Inicio</label>
                        <input type="time" name="start_time" value="18:00"
                               class="input-night w-full font-mono text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Hora Fin</label>
                        <input type="time" name="end_time" value="21:00"
                               class="input-night w-full font-mono text-xs">
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="showNewPromoModal = false" class="btn-touch flex-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl">Cancelar</button>
                    <button type="submit" class="btn-touch flex-1 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-lg shadow-emerald-600/30 rounded-xl">CREAR PROMOCIÓN</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
