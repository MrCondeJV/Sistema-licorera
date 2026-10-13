@extends('layouts.app', ['title' => 'Detalle Mesa ' . $table->name])

@section('content')
<div class="h-full flex flex-col lg:flex-row gap-3 -m-3 sm:-m-4 p-3 sm:p-4 overflow-hidden"
     x-data="{ showTransferModal: false, showCheckoutModal: false, selectedCategory: 'all', search: '' }">

    <!-- LEFT: Add Products to this Open Account -->
    <div class="flex-1 flex flex-col h-full overflow-hidden bg-night-900/60 border border-night-800 rounded-2xl p-3">
        <div class="flex items-center justify-between mb-2">
            <h3 class="text-sm font-black text-white uppercase tracking-wider">Agregar Productos a la Mesa</h3>
            <span class="text-xs text-neon-cyan font-bold">Precios congelados al momento de agregar</span>
        </div>

        <!-- Categories -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 shrink-0 select-none">
            <button type="button" @click="selectedCategory = 'all'"
                    class="px-3 py-1.5 rounded-xl border text-xs font-bold whitespace-nowrap"
                    :class="selectedCategory === 'all' ? 'bg-neon-green text-night-950 border-neon-green' : 'bg-night-850 text-slate-300 border-night-750'">
                Todos
            </button>
            @foreach($categories as $cat)
                <button type="button" @click="selectedCategory = '{{ $cat->id }}'"
                        class="px-3 py-1.5 rounded-xl border text-xs font-bold whitespace-nowrap"
                        :class="selectedCategory === '{{ $cat->id }}' ? 'bg-neon-cyan text-night-950 border-neon-cyan' : 'bg-night-850 text-slate-300 border-night-750'">
                    {{ $cat->name }}
                </button>
            @endforeach
        </div>

        <!-- Product Cards -->
        <div class="flex-1 overflow-y-auto pr-1 grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-2.5 mt-2">
            @foreach($products as $product)
                <div class="card-night p-3 flex flex-col justify-between"
                     x-show="selectedCategory === 'all' || selectedCategory === '{{ $product->category_id }}'">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="badge-neon bg-emerald-950 text-emerald-400 border border-emerald-800 text-[10px]">{{ $product->stock }} disp.</span>
                            @if($product->is_liquor)
                                <span class="text-[10px] text-amber-400 font-semibold">🍾 Botella</span>
                            @endif
                        </div>
                        <h4 class="text-xs font-bold text-white line-clamp-2">{{ $product->name }}</h4>
                    </div>

                    <div class="mt-2 space-y-1">
                        <!-- Add Base Unit -->
                        <form action="{{ route('tables.add-item', $table->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="w-full py-1.5 px-2 bg-night-800 hover:bg-neon-green hover:text-night-950 rounded-lg text-xs font-bold flex justify-between items-center transition-all">
                                <span>{{ $product->base_unit }}</span>
                                <span class="font-mono">${{ number_format($product->price_sell, 0, ',', '.') }}</span>
                            </button>
                        </form>

                        <!-- Add Extra Presentations (Six-pack, etc.) -->
                        @foreach($product->presentations as $pres)
                            <form action="{{ route('tables.add-item', $table->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <input type="hidden" name="product_presentation_id" value="{{ $pres->id }}">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="w-full py-1 px-2 bg-night-850 hover:bg-neon-cyan hover:text-night-950 rounded-lg text-[10px] font-semibold flex justify-between items-center transition-all">
                                    <span>{{ $pres->name }}</span>
                                    <span class="font-mono text-neon-cyan">${{ number_format($pres->sale_price, 0, ',', '.') }}</span>
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- RIGHT: Active Tab Consumption Detail & Actions -->
    <div class="w-full lg:w-96 flex flex-col h-full bg-night-900 border border-night-800 rounded-2xl p-4 shadow-2xl shrink-0">
        <!-- Account Header -->
        <div class="pb-3 border-b border-night-800 flex items-start justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xl font-black text-white">Mesa {{ $table->name }}</span>
                    <span class="badge-neon bg-amber-950 text-amber-300 border border-amber-600 text-[10px]">Cuenta Abierta</span>
                </div>
                <p class="text-xs font-semibold text-slate-300 mt-0.5">{{ $account?->client_name }}</p>
                <p class="text-[11px] text-slate-400 mt-0.5">Abierta por: {{ $account?->opener?->name }} &bull; {{ $account?->opened_at?->diffForHumans() }}</p>
            </div>

            <!-- Transfer Button -->
            <button type="button" @click="showTransferModal = true" class="p-2 bg-night-800 hover:bg-night-750 text-slate-300 rounded-xl text-xs font-semibold border border-night-750" title="Trasladar de Mesa">
                🔄 Trasladar
            </button>
        </div>

        <!-- Consumed Items List -->
        <div class="flex-1 overflow-y-auto py-3 space-y-2 pr-1">
            @if(!$account || $account->items->isEmpty())
                <div class="h-full flex flex-col items-center justify-center text-center p-6 text-slate-500">
                    <p class="text-xs">No hay productos consumidos aún en esta mesa.</p>
                </div>
            @else
                @foreach($account->items as $item)
                    <div class="p-2.5 bg-night-850 border border-night-750 rounded-xl flex items-center justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-white truncate">{{ $item->product_name_snapshot }}</p>
                            <p class="text-[11px] text-slate-400">
                                <span class="text-neon-cyan">{{ $item->presentation_name_snapshot }}</span> &bull; 
                                ${{ number_format($item->unit_price, 0, ',', '.') }} c/u
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-night-900 border border-night-750 text-white">
                                x{{ rtrim(rtrim($item->quantity, '0'), '.') }}
                            </span>
                            <span class="font-mono text-xs font-bold text-amber-400">
                                ${{ number_format($item->subtotal, 0, ',', '.') }}
                            </span>

                            <form action="{{ route('tables.remove-item', ['table' => $table->id, 'itemId' => $item->id]) }}" method="POST" onsubmit="return confirm('¿Quitar este ítem de la cuenta?');">
                                @csrf
                                <button type="submit" class="text-slate-500 hover:text-rose-400 p-1">&times;</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <!-- Totals & Checkout -->
        <div class="pt-3 border-t border-night-800 space-y-2">
            <div class="flex justify-between items-baseline">
                <span class="text-xs font-bold text-slate-400 uppercase">Total Acumulado:</span>
                <span class="text-2xl font-black font-mono text-amber-400">${{ number_format($account?->total ?? 0, 0, ',', '.') }}</span>
            </div>

            <div class="grid grid-cols-2 gap-2 pt-1">
                <!-- Cobrar en Mostrador POS -->
                <a href="{{ route('pos.index') }}" class="btn-touch col-span-2 bg-gradient-to-r from-neon-green to-emerald-400 text-night-950 font-black text-sm flex items-center justify-center gap-2 shadow-lg shadow-neon-green/20">
                    <span>💵 COBRAR CUENTA EN CAJA</span>
                </a>

                <!-- Cancelar Cuenta -->
                <form action="{{ route('tables.cancel', $table->id) }}" method="POST" class="col-span-2" onsubmit="return confirm('¿Cancelar esta cuenta y liberar la mesa?');">
                    @csrf
                    <input type="hidden" name="reason" value="Cancelación autorizada">
                    <button type="submit" class="w-full py-2 text-xs text-rose-400 hover:text-rose-300 font-semibold text-center">
                        Liberar Mesa sin Cobro
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Transfer Account to another Table -->
    <div x-show="showTransferModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" style="display: none;">
        <div class="bg-night-900 border border-night-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl">
            <h3 class="text-base font-black text-white mb-1">Trasladar Mesa {{ $table->name }}</h3>
            <p class="text-xs text-slate-400 mb-4">Selecciona la mesa libre a la cual se moverá todo el consumo</p>

            <form action="{{ route('tables.transfer', $table->id) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Mesa Destino</label>
                    <select name="destination_table_id" required class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3 py-2.5 text-sm focus:border-neon-green focus:outline-none">
                        <option value="">Selecciona mesa...</option>
                        @foreach($freeTables as $ft)
                            <option value="{{ $ft->id }}">{{ $ft->name }} (Capacidad: {{ $ft->capacity }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="showTransferModal = false" class="btn-touch flex-1 bg-night-800 text-slate-300 text-xs font-bold">Cancelar</button>
                    <button type="submit" class="btn-touch flex-1 bg-neon-cyan text-night-950 text-xs font-black">TRASLADAR</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
