@extends('layouts.app', ['title' => 'Promociones y Happy Hour'])

@section('content')
<div class="h-full flex flex-col space-y-4" x-data="{ showNewPromoModal: false, promoType: 'happy_hour' }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-black text-white">Promociones y Happy Hour</h2>
            <p class="text-xs text-slate-400 mt-0.5">Precios especiales por horario, cantidad y días de la semana</p>
        </div>
        <button type="button" @click="showNewPromoModal = true" class="btn-touch py-2 px-4 bg-gradient-to-r from-neon-green to-emerald-400 text-night-950 text-xs font-black shadow-lg shadow-neon-green/20">
            + NUEVA PROMOCIÓN
        </button>
    </div>

    <!-- Promotions Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 flex-1 overflow-y-auto pb-4">
        @forelse($promotions as $promo)
            <div class="card-night p-5 flex flex-col justify-between border-night-750 {{ $promo->isCurrentlyActive() ? 'border-neon-green/50 shadow-lg shadow-neon-green/10' : 'opacity-70' }}">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="badge-neon {{ $promo->isCurrentlyActive() ? 'bg-emerald-950 text-emerald-300 border-emerald-600' : 'bg-night-800 text-slate-400 border-night-700' }} text-[10px]">
                            {{ $promo->isCurrentlyActive() ? '🟢 ACTIVA AHORA' : ($promo->is_active ? 'PROGRAMADA' : 'INACTIVA') }}
                        </span>
                        <span class="text-xs font-mono font-bold text-neon-cyan">
                            {{ $promo->discount_percentage ? $promo->discount_percentage . '% OFF' : '$' . number_format($promo->fixed_price, 0, ',', '.') }}
                        </span>
                    </div>

                    <h3 class="text-base font-black text-white mb-1">{{ $promo->name }}</h3>

                    <div class="space-y-1 text-xs text-slate-400 mt-2 font-medium">
                        @if($promo->start_time && $promo->end_time)
                            <p class="flex items-center gap-1.5">
                                <span>⏰ Horario:</span>
                                <strong class="text-slate-200 font-mono">{{ $promo->start_time }} a {{ $promo->end_time }}</strong>
                            </p>
                        @endif
                        @if($promo->min_quantity > 1)
                            <p class="flex items-center gap-1.5">
                                <span>📦 Cantidad mínima:</span>
                                <strong class="text-slate-200 font-mono">{{ $promo->min_quantity }} unidades</strong>
                            </p>
                        @endif
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-night-800 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400">Estado:</span>
                    <form action="{{ route('promotions.toggle', $promo->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-3 py-1 rounded-xl text-xs font-bold transition-all {{ $promo->is_active ? 'bg-night-800 hover:bg-rose-950 text-rose-300' : 'bg-night-800 hover:bg-emerald-950 text-emerald-300' }}">
                            {{ $promo->is_active ? 'Desactivar' : 'Activar' }}
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="card-night p-8 text-center text-slate-500 col-span-3">
                No hay promociones registradas. Crea una promoción o Happy Hour para incentivar las ventas.
            </div>
        @endforelse
    </div>

    <!-- Modal: New Promo -->
    <div x-show="showNewPromoModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" style="display: none;">
        <div class="bg-night-900 border border-night-800 rounded-3xl p-6 max-w-md w-full shadow-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-black text-white mb-1">Nueva Promoción / Happy Hour</h3>
            <p class="text-xs text-slate-400 mb-4">Los descuentos son calculados y congelados en el servidor</p>

            <form action="{{ route('promotions.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Nombre de la Promoción *</label>
                    <input type="text" name="name" required placeholder="Ej: Happy Hour Cervezas 2x1..." autofocus
                           class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3 py-2 text-xs focus:border-neon-green focus:outline-none">
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Tipo de Promoción *</label>
                    <select name="type" x-model="promoType" required class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3 py-2 text-xs focus:border-neon-green focus:outline-none">
                        <option value="happy_hour">Happy Hour (Horario Especial)</option>
                        <option value="discount_percentage">Descuento Porcentual</option>
                        <option value="fixed_price">Precio Fijo Especial</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Porcentaje Descuento (%)</label>
                        <input type="number" name="discount_percentage" min="1" max="100" placeholder="Ej: 20"
                               class="w-full bg-night-850 border border-night-750 text-white font-mono rounded-xl px-3 py-2 text-xs focus:border-neon-green focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Cantidad Mínima</label>
                        <input type="number" name="min_quantity" min="1" value="1" required
                               class="w-full bg-night-850 border border-night-750 text-white font-mono rounded-xl px-3 py-2 text-xs focus:border-neon-green focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Hora Inicio</label>
                        <input type="time" name="start_time" value="18:00"
                               class="w-full bg-night-850 border border-night-750 text-white font-mono rounded-xl px-3 py-2 text-xs focus:border-neon-green focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Hora Fin</label>
                        <input type="time" name="end_time" value="21:00"
                               class="w-full bg-night-850 border border-night-750 text-white font-mono rounded-xl px-3 py-2 text-xs focus:border-neon-green focus:outline-none">
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="showNewPromoModal = false" class="btn-touch flex-1 bg-night-800 text-slate-300 text-xs font-bold">Cancelar</button>
                    <button type="submit" class="btn-touch flex-1 bg-neon-green text-night-950 text-xs font-black">CREAR PROMOCIÓN</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
