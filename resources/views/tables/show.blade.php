@extends('layouts.app', ['title' => 'Detalle Mesa ' . $table->name])

@section('content')
<div class="h-full flex flex-col lg:flex-row gap-4 -m-3 sm:-m-4 p-3 sm:p-4 overflow-hidden"
     x-data="{ activeTab: 'products', showTransferModal: false, showCheckoutModal: false, selectedCategory: 'all', search: '' }">

    <!-- Mobile Switcher Tabs (Only visible on screens < lg) -->
    <div class="lg:hidden flex bg-slate-100 dark:bg-slate-850 p-1 rounded-2xl border border-slate-200 dark:border-slate-800 shrink-0">
        <button type="button" @click="activeTab = 'products'"
                class="flex-1 py-2 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5"
                :class="activeTab === 'products' ? 'bg-emerald-600 text-white shadow-md font-black' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'">
            <span>🛍️ Productos</span>
        </button>
        <button type="button" @click="activeTab = 'bill'"
                class="flex-1 py-2 px-3 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-1.5"
                :class="activeTab === 'bill' ? 'bg-cyan-600 text-white shadow-md font-black' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'">
            <span>📋 Cuenta (${{ number_format($account?->total ?? 0, 0, ',', '.') }})</span>
        </button>
    </div>

    <!-- LEFT: Add Products to this Open Account -->
    <div class="flex-1 flex-col h-full overflow-hidden bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm"
         :class="activeTab === 'products' ? 'flex' : 'hidden lg:flex'">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-2">
                <a href="{{ route('tables.index') }}" class="p-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-600 dark:text-slate-300 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">Agregar Productos a la Mesa</h3>
            </div>
            <span class="text-xs text-emerald-600 dark:text-emerald-400 font-bold bg-emerald-50 dark:bg-emerald-950/50 px-2 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-800">Precios congelados</span>
        </div>

        <!-- Categories -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 shrink-0 select-none no-scrollbar">
            <button type="button" @click="selectedCategory = 'all'"
                    class="px-3.5 py-1.5 rounded-xl border text-xs font-bold whitespace-nowrap transition-all"
                    :class="selectedCategory === 'all' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-750'">
                Todos
            </button>
            @foreach($categories as $cat)
                <button type="button" @click="selectedCategory = '{{ $cat->id }}'"
                        class="px-3.5 py-1.5 rounded-xl border text-xs font-bold whitespace-nowrap transition-all"
                        :class="selectedCategory === '{{ $cat->id }}' ? 'bg-cyan-600 text-white border-cyan-600 shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-750'">
                    {{ $cat->name }}
                </button>
            @endforeach
        </div>

        <!-- Product Cards -->
        <div class="flex-1 overflow-y-auto pr-1 grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 mt-3">
            @foreach($products as $product)
                <div class="bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 hover:border-emerald-500 rounded-xl p-3 flex flex-col justify-between transition-all"
                     x-show="selectedCategory === 'all' || selectedCategory === '{{ $product->category_id }}'">
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="px-2 py-0.5 rounded bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 text-[10px] font-bold">{{ $product->stock }} disp.</span>
                            @if($product->is_liquor)
                                <span class="text-[10px] text-amber-600 dark:text-amber-400 font-bold">🍾 Botella</span>
                            @endif
                        </div>
                        <h4 class="text-xs font-bold text-slate-900 dark:text-white line-clamp-2">{{ $product->name }}</h4>
                    </div>

                    <div class="mt-2.5 space-y-1.5">
                        <!-- Add Base Unit -->
                        <form action="{{ route('tables.add-item', $table->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="w-full py-1.5 px-2.5 bg-white dark:bg-slate-800 hover:bg-emerald-600 hover:text-white dark:hover:bg-emerald-600 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-bold flex justify-between items-center transition-all shadow-sm">
                                <span>{{ $product->base_unit }}</span>
                                <span class="font-mono font-black">${{ number_format($product->price_sell, 0, ',', '.') }}</span>
                            </button>
                        </form>

                        <!-- Add Extra Presentations (Six-pack, etc.) -->
                        @foreach($product->presentations as $pres)
                            <form action="{{ route('tables.add-item', $table->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <input type="hidden" name="product_presentation_id" value="{{ $pres->id }}">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="w-full py-1 px-2.5 bg-slate-100 dark:bg-slate-850 hover:bg-cyan-600 hover:text-white dark:hover:bg-cyan-600 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-800 rounded-lg text-[10px] font-semibold flex justify-between items-center transition-all">
                                    <span>{{ $pres->name }}</span>
                                    <span class="font-mono text-cyan-600 dark:text-cyan-400 font-bold">${{ number_format($pres->sale_price, 0, ',', '.') }}</span>
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- RIGHT: Active Tab Consumption Detail & Actions -->
    <div class="w-full lg:w-96 flex-col h-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 shadow-sm shrink-0"
         :class="activeTab === 'bill' ? 'flex' : 'hidden lg:flex'">
        <!-- Account Header -->
        <div class="pb-3 border-b border-slate-200 dark:border-slate-800 flex items-start justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xl font-black text-slate-900 dark:text-white">Mesa {{ $table->name }}</span>
                    <span class="px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-700 text-[10px] font-bold">Cuenta Abierta</span>
                </div>
                <p class="text-xs font-semibold text-slate-700 dark:text-slate-300 mt-0.5">{{ $account?->client_name }}</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Abierta por: {{ $account?->opener?->name }} &bull; {{ $account?->opened_at?->diffForHumans() }}</p>
            </div>

            <!-- Transfer Button -->
            <button type="button" @click="showTransferModal = true" class="p-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-700 transition-colors" title="Trasladar de Mesa">
                🔄 Trasladar
            </button>
        </div>

        <!-- Consumed Items List -->
        <div class="flex-1 overflow-y-auto py-3 space-y-2 pr-1">
            @if(!$account || $account->items->isEmpty())
                <div class="h-full flex flex-col items-center justify-center text-center p-6 text-slate-400 dark:text-slate-500">
                    <p class="text-xs">No hay productos consumidos aún en esta mesa.</p>
                </div>
            @else
                @foreach($account->items as $item)
                    <div class="p-2.5 bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl flex items-center justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ $item->product_name_snapshot }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                <span class="text-cyan-600 dark:text-cyan-400 font-semibold">{{ $item->presentation_name_snapshot }}</span> &bull; 
                                ${{ number_format($item->unit_price, 0, ',', '.') }} c/u
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-bold px-2 py-0.5 rounded bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-800 dark:text-slate-200">
                                x{{ rtrim(rtrim($item->quantity, '0'), '.') }}
                            </span>
                            <span class="font-mono text-xs font-bold text-amber-600 dark:text-amber-400">
                                ${{ number_format($item->subtotal, 0, ',', '.') }}
                            </span>

                            <form action="{{ route('tables.remove-item', ['table' => $table->id, 'itemId' => $item->id]) }}" method="POST" onsubmit="return confirm('¿Quitar este ítem de la cuenta?');">
                                @csrf
                                <button type="submit" class="text-slate-400 hover:text-rose-600 p-1 font-bold">&times;</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <!-- Totals & Checkout -->
        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 space-y-2">
            <div class="flex justify-between items-baseline">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Total Acumulado:</span>
                <span class="text-2xl font-black font-mono text-amber-600 dark:text-amber-400">${{ number_format($account?->total ?? 0, 0, ',', '.') }}</span>
            </div>

            <div class="grid grid-cols-2 gap-2 pt-1">
                <!-- Cobrar en Mostrador POS -->
                <a href="{{ route('pos.index') }}" class="btn-touch col-span-2 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-sm flex items-center justify-center gap-2 shadow-lg shadow-emerald-600/30 rounded-2xl">
                    <span>💵 COBRAR CUENTA EN CAJA</span>
                </a>

                <!-- Cancelar Cuenta -->
                <form action="{{ route('tables.cancel', $table->id) }}" method="POST" class="col-span-2" onsubmit="return confirm('¿Cancelar esta cuenta y liberar la mesa?');">
                    @csrf
                    <input type="hidden" name="reason" value="Cancelación autorizada">
                    <button type="submit" class="w-full py-2 text-xs text-rose-500 hover:text-rose-600 font-semibold text-center transition-colors">
                        Liberar Mesa sin Cobro
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Transfer Account to another Table -->
    <div x-show="showTransferModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" style="display: none;">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-black text-slate-900 dark:text-white mb-1">Trasladar Mesa {{ $table->name }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Selecciona la mesa libre a la cual se moverá todo el consumo</p>

            <form action="{{ route('tables.transfer', $table->id) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Mesa Destino</label>
                    <select name="destination_table_id" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-800 dark:text-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
                        <option value="">Selecciona mesa...</option>
                        @foreach($freeTables as $ft)
                            <option value="{{ $ft->id }}">{{ $ft->name }} (Capacidad: {{ $ft->capacity }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="showTransferModal = false" class="btn-touch flex-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl">Cancelar</button>
                    <button type="submit" class="btn-touch flex-1 bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-black shadow-lg shadow-cyan-600/30 rounded-xl">TRASLADAR</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
