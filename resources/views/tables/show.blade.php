@extends('layouts.app', ['title' => 'Detalle Mesa ' . $table->name])

@section('content')
<div class="h-full min-h-0 flex-1 flex flex-col lg:flex-row gap-3.5 sm:gap-4 overflow-hidden"
     x-data="{ activeTab: 'products', showTransferModal: false, selectedCategory: 'all', search: '' }">

    <!-- Mobile Switcher Tabs (Only visible on screens < lg) -->
    <div class="lg:hidden flex bg-slate-100 dark:bg-slate-850 p-1.5 rounded-2xl border border-slate-200 dark:border-slate-800 shrink-0">
        <button type="button" @click="activeTab = 'products'"
                class="flex-1 py-2.5 px-3 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-1.5"
                :class="activeTab === 'products' ? 'bg-emerald-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'">
            <span>🛍️ Catálogo de Productos</span>
        </button>
        <button type="button" @click="activeTab = 'bill'"
                class="flex-1 py-2.5 px-3 rounded-xl text-xs font-black transition-all flex items-center justify-center gap-1.5"
                :class="activeTab === 'bill' ? 'bg-amber-600 text-white shadow-md' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'">
            <span>📋 Cuenta Mesa (${{ number_format($account?->total ?? 0, 0, ',', '.') }})</span>
        </button>
    </div>

    <!-- LEFT: Add Products to this Open Account (Matching POS 3-column layout) -->
    <div class="flex-1 min-w-0 flex flex-col h-full min-h-0 overflow-hidden bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-3.5 sm:p-5 shadow-sm"
         :class="activeTab === 'products' ? 'flex' : 'hidden lg:flex'">
        
        <!-- Header with Back Button & Search -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 mb-3 shrink-0">
            <div class="flex items-center gap-2.5">
                <a href="{{ route('tables.index') }}" 
                   class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 transition-colors shrink-0"
                   title="Volver a mesas">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <div>
                    <h2 class="text-base sm:text-lg font-black text-slate-900 dark:text-white">Agregar a Mesa {{ $table->name }}</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Precios y presentaciones congeladas para esta cuenta</p>
                </div>
            </div>

            <!-- Search box -->
            <div class="relative w-full sm:w-64 shrink-0">
                <input type="text" x-model="search" placeholder="Filtrar productos..."
                       class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-white rounded-xl pl-9 pr-3 py-2 text-xs font-medium focus:outline-none focus:border-emerald-500">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>

        <!-- Categories horizontal bar -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 shrink-0 select-none no-scrollbar">
            <button type="button" @click="selectedCategory = 'all'"
                    class="px-4 py-2 rounded-xl border text-xs font-black whitespace-nowrap transition-all shrink-0"
                    :class="selectedCategory === 'all' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-750'">
                Todos
            </button>
            @foreach($categories as $cat)
                <button type="button" @click="selectedCategory = '{{ $cat->id }}'"
                        class="px-4 py-2 rounded-xl border text-xs font-bold whitespace-nowrap transition-all shrink-0"
                        :class="selectedCategory === '{{ $cat->id }}' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-750'">
                    {{ $cat->name }}
                </button>
            @endforeach
        </div>

        <!-- Product Cards Grid: Responsive 2-4 columns based on available space -->
        <div class="flex-1 min-h-0 overflow-y-auto pr-1 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-3 sm:gap-3.5 mt-2">
            @foreach($products as $product)
                <div class="bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-5 flex flex-col justify-between hover:border-emerald-400 dark:hover:border-emerald-600 transition-all shadow-sm hover:shadow-md"
                     x-show="(selectedCategory === 'all' || selectedCategory === '{{ $product->category_id }}') && ('{{ strtolower(addslashes($product->name)) }}'.includes(search.toLowerCase()) || search === '')">
                    <div>
                        <!-- Header with Photo and Info -->
                        <div class="flex items-start gap-3 mb-2.5">
                            <!-- Product Photo -->
                            <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-center overflow-hidden shrink-0 shadow-sm">
                                @if($product->image_url)
                                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-full h-full object-cover">
                                @else
                                    <span class="text-2xl">{{ $product->is_beer ? '🍺' : ($product->is_liquor ? '🍾' : '🥤') }}</span>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-1 mb-1">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 truncate">{{ $product->category?->name ?? 'General' }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold tracking-wide {{ $product->stock <= 0 ? 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-400 border border-rose-200' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400 border border-emerald-200' }}">
                                        {{ $product->stock <= 0 ? 'Agotado' : $product->stock . ' disp.' }}
                                    </span>
                                </div>
                                <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white leading-snug break-words">{{ $product->name }}</h3>
                                @if($product->is_liquor)
                                    <span class="inline-block mt-0.5 text-[11px] text-amber-600 dark:text-amber-400 font-semibold">🍾 Botella completa</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Presentations & Price Buttons -->
                    <div class="mt-3.5 pt-3 border-t border-slate-200 dark:border-slate-800 space-y-2">
                        <!-- Add Base Unit -->
                        <form action="{{ route('tables.add-item', $table->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" 
                                    @if($product->stock <= 0) disabled @endif
                                    class="w-full btn-touch min-h-[46px] py-2.5 px-3.5 bg-white dark:bg-slate-800 hover:bg-emerald-600 hover:text-white dark:hover:bg-emerald-600 text-slate-800 dark:text-white font-bold text-sm sm:text-base rounded-xl border border-slate-200 dark:border-slate-700 transition-all flex items-center justify-between active:scale-95 shadow-sm disabled:opacity-40 disabled:cursor-not-allowed">
                                <span>{{ $product->base_unit ?: 'Unidad' }}</span>
                                <span class="font-mono font-black text-emerald-600 dark:text-emerald-400 group-hover:text-white">${{ number_format($product->price_sell, 0, ',', '.') }}</span>
                            </button>
                        </form>

                        <!-- Extra Presentations for Beers / Drinks -->
                        @if($product->presentations->isNotEmpty())
                            <div class="grid grid-cols-2 gap-1.5">
                                @foreach($product->presentations as $pres)
                                    <form action="{{ route('tables.add-item', $table->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                                        <input type="hidden" name="product_presentation_id" value="{{ $pres->id }}">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit"
                                                @if($product->stock < $pres->conversion_factor) disabled @endif
                                                class="w-full py-2 px-2 bg-white dark:bg-slate-800 hover:bg-cyan-600 hover:text-white dark:hover:bg-cyan-600 text-slate-700 dark:text-slate-200 font-semibold text-xs rounded-xl border border-slate-200 dark:border-slate-700 transition-all flex flex-col items-center justify-center min-h-[42px] disabled:opacity-40">
                                            <span class="truncate max-w-full font-medium">{{ $pres->name }}</span>
                                            <span class="font-mono text-xs font-bold text-cyan-600 dark:text-cyan-400">${{ number_format($pres->sale_price, 0, ',', '.') }}</span>
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- RIGHT: Active Tab Consumption Detail & Big Controls (Proportional PC Ticket) -->
    <div class="w-full lg:w-80 xl:w-96 2xl:w-[420px] flex flex-col h-full min-h-0 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-4 sm:p-5 shadow-sm shrink-0 overflow-hidden"
         :class="activeTab === 'bill' ? 'flex' : 'hidden lg:flex'">
        
        <!-- Header: Table Info & Transfer Button -->
        <div class="pb-3 border-b border-slate-200 dark:border-slate-800 shrink-0">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white truncate">Mesa {{ $table->name }}</span>
                        <span class="px-2.5 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700 text-[11px] font-black tracking-wide shrink-0">CUENTA ABIERTA</span>
                    </div>
                    <p class="text-xs sm:text-sm font-bold text-slate-700 dark:text-slate-200 mt-1 flex items-center gap-1.5 truncate">
                        <span>👤</span>
                        <span class="truncate">{{ $account?->client_name ?: 'Consumidor General' }}</span>
                    </p>
                    <p class="text-[11px] text-slate-400 mt-0.5 truncate">
                        Abierta por: {{ $account?->opener?->name }} &bull; {{ $account?->opened_at?->diffForHumans() }}
                    </p>
                </div>

                <!-- Transfer Button -->
                <button type="button" @click="showTransferModal = true" 
                        class="px-2.5 sm:px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 transition-colors shrink-0 shadow-sm"
                        title="Trasladar todo a otra mesa">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                    <span>Trasladar</span>
                </button>
            </div>
        </div>

        <!-- Consumed Items List: INTERNAL SCROLL ONLY, LARGE READABLE ITEMS & BIG STEPPERS -->
        <div class="flex-1 min-h-0 overflow-y-auto py-3 space-y-2.5 pr-1">
            @if(!$account || $account->items->isEmpty())
                <div class="h-full min-h-[160px] flex flex-col items-center justify-center text-center p-4 text-slate-400">
                    <svg class="w-12 h-12 mb-2 text-slate-300 dark:text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200">No hay productos agregados</p>
                    <p class="text-xs text-slate-400 mt-1 max-w-[220px]">Selecciona productos del catálogo a la izquierda</p>
                </div>
            @else
                @foreach($account->items as $item)
                    <div class="p-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col gap-2.5">
                        <!-- Top Row: Photo + Product Title & Unit Price + Delete Button -->
                        <div class="flex items-start justify-between gap-2.5">
                            <div class="flex items-center gap-2.5 min-w-0 flex-1">
                                <div class="w-11 h-11 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-750 flex items-center justify-center overflow-hidden shrink-0 shadow-sm">
                                    @if($item->product?->image_url)
                                        <img src="{{ $item->product->image_url }}" alt="{{ $item->product_name_snapshot }}" class="w-full h-full object-cover">
                                    @else
                                        <span class="text-xl">🍾</span>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm sm:text-base font-bold text-slate-900 dark:text-white leading-tight break-normal">
                                        {{ $item->product_name_snapshot }}
                                    </p>
                                    <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                        <span class="px-2 py-0.5 rounded-md bg-cyan-50 dark:bg-cyan-950 text-cyan-700 dark:text-cyan-400 border border-cyan-200 dark:border-cyan-800 text-[11px] font-bold font-mono">
                                            {{ $item->presentation_name_snapshot }}
                                        </span>
                                        <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                                            ${{ number_format($item->unit_price, 0, ',', '.') }} c/u
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Remove All Trash Button (Top-Right) -->
                            <form action="{{ route('tables.remove-item', ['table' => $table->id, 'itemId' => $item->id]) }}" method="POST" 
                                  data-confirm="¿Deseas quitar todos los ítems de {{ $item->product_name_snapshot }} de esta mesa?"
                                  data-confirm-title="Eliminar ítem"
                                  data-confirm-btn="Sí, eliminar ítem"
                                  data-confirm-danger="true"
                                  class="shrink-0">
                                @csrf
                                <button type="submit" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-xl transition-all" title="Eliminar ítem de la cuenta">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>

                        <!-- Bottom Row: Big Stepper Buttons + Subtotal -->
                        <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800/80">
                            <!-- Large Stepper Buttons -->
                            <div class="flex items-center bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-1 rounded-xl shadow-inner">
                                <!-- Minus 1 Button -->
                                <form action="{{ route('tables.remove-item', ['table' => $table->id, 'itemId' => $item->id]) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="qty" value="1">
                                    <button type="submit"
                                            class="w-10 h-10 sm:w-11 sm:h-11 rounded-lg bg-white dark:bg-slate-900 text-slate-800 dark:text-white font-black text-xl flex items-center justify-center active:scale-90 shadow-sm transition-all"
                                            title="Restar 1">-</button>
                                </form>

                                <!-- Quantity -->
                                <span class="w-10 sm:w-11 text-center text-base sm:text-lg font-mono font-black text-slate-900 dark:text-white">
                                    {{ rtrim(rtrim($item->quantity, '0'), '.') }}
                                </span>

                                <!-- Plus 1 Button -->
                                <form action="{{ route('tables.add-item', $table->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $item->product_id }}">
                                    @if($item->product_presentation_id)
                                        <input type="hidden" name="product_presentation_id" value="{{ $item->product_presentation_id }}">
                                    @endif
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit"
                                            class="w-10 h-10 sm:w-11 sm:h-11 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xl flex items-center justify-center active:scale-90 shadow-sm shadow-emerald-600/30 transition-all"
                                            title="Sumar 1">+</button>
                                </form>
                            </div>

                            <!-- Subtotal -->
                            <div class="text-right">
                                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Subtotal</span>
                                <span class="text-lg sm:text-xl font-mono font-black text-amber-600 dark:text-amber-400">
                                    ${{ number_format($item->subtotal, 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <!-- Totals & Big Checkout Button: PINNED TO BOTTOM, NO WINDOW SCROLLING (shrink-0) -->
        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 space-y-2.5 shrink-0">
            <div class="flex justify-between items-baseline">
                <span class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Total Acumulado:</span>
                <span class="text-3xl font-black font-mono text-amber-600 dark:text-amber-400">
                    ${{ number_format($account?->total ?? 0, 0, ',', '.') }}
                </span>
            </div>

            <!-- Cobrar en Mostrador POS: Pre-loads this table into the POS cart directly -->
            <a href="{{ route('pos.index', ['table_id' => $table->id]) }}" 
               class="w-full btn-touch min-h-[52px] bg-emerald-600 hover:bg-emerald-500 active:scale-98 text-white font-black text-base rounded-2xl shadow-xl shadow-emerald-600/30 flex items-center justify-center gap-2 uppercase tracking-wide transition-all">
                <span>💵 COBRAR CUENTA EN CAJA</span>
            </a>

            <!-- Liberar mesa sin cobro -->
            <form action="{{ route('tables.cancel', $table->id) }}" method="POST" 
                  data-confirm="¿Estás completamente seguro de cancelar esta cuenta y liberar la mesa sin registrar ningún cobro?"
                  data-confirm-title="Liberar Mesa {{ $table->name }}"
                  data-confirm-btn="Sí, liberar mesa"
                  data-confirm-danger="true">
                @csrf
                <input type="hidden" name="reason" value="Cancelación autorizada">
                <button type="submit" class="w-full py-1.5 text-xs text-rose-500 hover:text-rose-600 font-bold text-center transition-colors">
                    Liberar Mesa sin Cobro
                </button>
            </form>
        </div>
    </div>

    <!-- Modal: Transfer Account to another Table -->
    <div x-show="showTransferModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" style="display: none;">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-black text-slate-900 dark:text-white mb-1">Trasladar Mesa {{ $table->name }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Selecciona la mesa libre a la cual se moverá todo el consumo</p>

            <form action="{{ route('tables.transfer', $table->id) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1.5">Mesa Destino</label>
                    <select name="destination_table_id" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 text-slate-800 dark:text-slate-200 rounded-xl px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
                        <option value="">Selecciona mesa...</option>
                        @foreach($freeTables as $ft)
                            <option value="{{ $ft->id }}">{{ $ft->name }} (Capacidad: {{ $ft->capacity }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="showTransferModal = false" class="btn-touch flex-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-xl">Cancelar</button>
                    <button type="submit" class="btn-touch flex-1 bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:hover:bg-slate-100 dark:text-slate-900 border border-slate-800 dark:border-white text-xs font-semibold rounded-xl shadow-xs transition-colors">TRASLADAR</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
