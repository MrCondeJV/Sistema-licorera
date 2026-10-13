@extends('layouts.app', ['title' => 'Venta Rápida POS'])

@section('content')
<div class="h-full flex flex-col lg:flex-row gap-4 -m-3 sm:-m-4 lg:-m-6 p-3 sm:p-4 lg:p-6 overflow-hidden relative"
     x-data="posApp()">

    <!-- LEFT / MAIN: Catalog & Products Area -->
    <div class="flex-1 flex flex-col h-full overflow-hidden bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-3 sm:p-4 shadow-sm">
        
        <!-- Cash Session Check Alert -->
        @if(!$openCashSession)
            <div class="mb-3 p-3 bg-amber-50 border border-amber-200 dark:bg-amber-950/80 dark:border-amber-500/40 rounded-2xl text-amber-800 dark:text-amber-300 flex items-center justify-between text-xs sm:text-sm">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <span><strong>Atención:</strong> No tienes turno de caja abierto. Debes abrir caja para registrar ventas.</span>
                </div>
                <a href="{{ route('cash.index') }}" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-400 text-white font-bold rounded-xl text-xs shrink-0 shadow-sm">
                    Abrir Caja &rarr;
                </a>
            </div>
        @endif

        <!-- Search Bar, Table Indicator & Mobile Cart Button -->
        <div class="flex items-center gap-2 mb-3">
            <div class="relative flex-1">
                <input type="text" x-model="search" placeholder="Buscar por nombre o categoría..."
                       class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-white rounded-2xl pl-10 pr-4 py-2.5 text-xs sm:text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>

            <!-- Mobile Cart Drawer Trigger (Screens < lg) -->
            <button type="button" @click="mobileCartOpen = true"
                    class="lg:hidden flex items-center gap-2 px-3.5 py-2.5 rounded-2xl bg-emerald-600 text-white font-bold text-xs shadow-md shadow-emerald-600/30 shrink-0 active:scale-95">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <span class="font-mono" x-text="cart.reduce((sum, i) => sum + i.quantity, 0)"></span>
            </button>

            <button type="button" @click="selectedCategory = 'all'"
                    class="hidden sm:inline-flex px-3.5 py-2.5 rounded-2xl border text-xs font-bold uppercase transition-all"
                    :class="selectedCategory === 'all' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700'">
                Todos
            </button>
        </div>

        <!-- Horizontal Scrollable Categories -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 shrink-0 select-none">
            <button type="button" @click="selectedCategory = 'all'"
                    class="sm:hidden px-3 py-1.5 rounded-xl border text-xs font-bold uppercase transition-all shrink-0"
                    :class="selectedCategory === 'all' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700'">
                Todos
            </button>
            @foreach($categories as $cat)
                <button type="button" @click="selectedCategory = '{{ $cat->id }}'"
                        class="px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl border text-xs font-bold whitespace-nowrap transition-all flex items-center gap-1.5 shrink-0"
                        :class="selectedCategory === '{{ $cat->id }}' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-750'">
                    <span>{{ $cat->name }}</span>
                </button>
            @endforeach
        </div>

        <!-- Products Grid with internal scroll -->
        <div class="flex-1 overflow-y-auto pr-1 grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-2.5 sm:gap-3 mt-2">
            <template x-for="product in filteredProducts" :key="product.id">
                <div class="bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-2xl p-2.5 sm:p-3 flex flex-col justify-between hover:border-emerald-400 dark:hover:border-emerald-600 transition-all relative group shadow-sm"
                     :class="product.stock <= 0 ? 'opacity-50' : ''">

                    <div>
                        <!-- Header / Badges -->
                        <div class="flex items-start justify-between gap-1 mb-1.5">
                            <span class="text-[9px] sm:text-[10px] font-mono font-bold uppercase tracking-wider text-slate-400 truncate" x-text="product.category ? product.category.name : ''"></span>
                            <span class="px-2 py-0.5 rounded-full text-[9px] sm:text-[10px] font-bold uppercase tracking-wider"
                                  :class="product.stock <= 0 ? 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-400 border border-rose-200 dark:border-rose-800' : (product.stock <= product.stock_min ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400 border border-amber-200 dark:border-amber-800' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800')">
                                <span x-text="product.stock <= 0 ? 'Agotado' : product.stock + ' disp.'"></span>
                            </span>
                        </div>

                        <!-- Product Name -->
                        <h3 class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white leading-tight line-clamp-2" x-text="product.name"></h3>

                        <!-- Rule description -->
                        <template x-if="product.is_liquor">
                            <span class="inline-block mt-1 text-[9px] sm:text-[10px] text-amber-600 dark:text-amber-400 font-semibold">🍾 Botella completa</span>
                        </template>
                    </div>

                    <!-- Presentations & Price Buttons -->
                    <div class="mt-2.5 pt-2 border-t border-slate-200 dark:border-slate-800">
                        <!-- Single Unit Price Button -->
                        <button type="button"
                                @click="addToCart(product, null)"
                                :disabled="product.stock <= 0"
                                class="w-full btn-touch min-h-[42px] sm:min-h-[44px] py-1.5 sm:py-2 bg-white dark:bg-slate-800 hover:bg-emerald-600 hover:text-white dark:hover:bg-emerald-600 text-slate-800 dark:text-white font-bold text-xs sm:text-sm rounded-xl border border-slate-200 dark:border-slate-700 transition-all flex items-center justify-between px-2.5 sm:px-3 active:scale-95 shadow-sm">
                            <span x-text="product.base_unit || 'Unidad'"></span>
                            <span class="font-mono text-emerald-600 dark:text-emerald-400 group-hover:text-white" x-text="formatMoney(product.price_sell)"></span>
                        </button>

                        <!-- Extra Presentations for Beers (Six-Pack, Caja 24) -->
                        <template x-if="product.presentations && product.presentations.length > 0">
                            <div class="grid grid-cols-2 gap-1 mt-1.5">
                                <template x-for="pres in product.presentations" :key="pres.id">
                                    <button type="button"
                                            @click="addToCart(product, pres)"
                                            :disabled="product.stock < pres.conversion_factor"
                                            class="py-1 px-1.5 bg-white dark:bg-slate-800 hover:bg-cyan-600 hover:text-white dark:hover:bg-cyan-600 text-slate-700 dark:text-slate-200 font-semibold text-[10px] sm:text-[11px] rounded-lg border border-slate-200 dark:border-slate-700 transition-all flex flex-col items-center justify-center">
                                        <span class="truncate max-w-full" x-text="pres.name"></span>
                                        <span class="font-mono text-[9px] sm:text-[10px] text-cyan-600 dark:text-cyan-400" x-text="formatMoney(pres.sale_price)"></span>
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Mobile Cart Drawer Backdrop -->
    <div x-show="mobileCartOpen" @click="mobileCartOpen = false"
         class="fixed inset-0 bg-slate-900/60 z-40 lg:hidden backdrop-blur-sm transition-opacity"
         style="display: none;"></div>

    <!-- RIGHT: Cart & Checkout Sidebar (Desktop sticky & Mobile slide-over drawer) -->
    <div class="fixed inset-y-0 right-0 z-50 w-full sm:w-96 bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 p-4 shadow-2xl flex flex-col lg:static lg:w-96 lg:h-full lg:border lg:rounded-3xl lg:shadow-sm shrink-0 transition-transform duration-300"
         :class="mobileCartOpen ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'">
        
        <!-- Cart Header: Table or Counter Mode -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Destino de la Venta</span>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                    <span class="text-sm font-black text-slate-900 dark:text-white" x-text="selectedTable ? 'Mesa ' + selectedTable.name : 'Venta de Mostrador'"></span>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <!-- Table Selector Modal Trigger -->
                <button type="button" @click="showTableModal = true" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-700 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                    <span x-text="selectedTable ? 'Cambiar' : 'Asignar Mesa'"></span>
                </button>

                <!-- Mobile Close Cart Button -->
                <button type="button" @click="mobileCartOpen = false" class="lg:hidden p-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-800 dark:hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Cart Items List (Internal scroll) -->
        <div class="flex-1 overflow-y-auto py-2 space-y-2 pr-1">
            <template x-if="cart.length === 0">
                <div class="h-full flex flex-col items-center justify-center text-center p-6 text-slate-400">
                    <svg class="w-12 h-12 mb-2 text-slate-300 dark:text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    <p class="text-xs font-semibold text-slate-600 dark:text-slate-300">El carrito está vacío</p>
                    <p class="text-[11px] text-slate-400 mt-1">Toca productos del catálogo para añadir a la orden</p>
                </div>
            </template>

            <template x-for="(item, idx) in cart" :key="idx">
                <div class="p-2.5 bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-2xl flex items-center justify-between gap-2">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-slate-900 dark:text-white truncate" x-text="item.name"></p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">
                            <span class="text-cyan-600 dark:text-cyan-400 font-semibold" x-text="item.presentation_name"></span> &bull; 
                            <span x-text="formatMoney(item.unit_price)"></span>
                        </p>
                    </div>

                    <!-- Stepper & Subtotal -->
                    <div class="flex items-center gap-2">
                        <div class="flex items-center bg-white dark:bg-slate-850 border border-slate-300 dark:border-slate-700 rounded-xl overflow-hidden shadow-sm">
                            <button type="button" @click="decreaseQty(idx)" class="w-7 h-7 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-bold active:scale-95">-</button>
                            <span class="w-7 text-center text-xs font-mono font-bold text-slate-900 dark:text-white" x-text="item.quantity"></span>
                            <button type="button" @click="increaseQty(idx)" class="w-7 h-7 flex items-center justify-center text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-bold active:scale-95">+</button>
                        </div>

                        <span class="w-16 text-right text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400" x-text="formatMoney(item.unit_price * item.quantity)"></span>

                        <button type="button" @click="removeFromCart(idx)" class="text-slate-400 hover:text-rose-500 p-1">
                            &times;
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- Totals & Big Checkout Button -->
        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 space-y-2">
            <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400">
                <span>Subtotal</span>
                <span class="font-mono text-slate-800 dark:text-slate-200 font-bold" x-text="formatMoney(cartSubtotal)"></span>
            </div>

            <div class="flex justify-between items-baseline pt-1 border-t border-slate-200 dark:border-slate-800">
                <span class="text-xs sm:text-sm font-bold text-slate-900 dark:text-white">TOTAL A COBRAR</span>
                <span class="text-2xl font-black font-mono text-emerald-600 dark:text-emerald-400" x-text="formatMoney(cartTotal)"></span>
            </div>

            <!-- Cobrar Action Button -->
            <button type="button"
                    @click="openPaymentModal()"
                    :disabled="cart.length === 0 || isProcessing"
                    class="w-full btn-touch bg-emerald-600 hover:bg-emerald-500 active:scale-98 text-white font-black text-base rounded-2xl shadow-lg shadow-emerald-600/30 disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                <span x-show="!isProcessing" x-text="'COBRAR ' + formatMoney(cartTotal)"></span>
                <span x-show="isProcessing">PROCESANDO...</span>
            </button>

            <button type="button" @click="clearCart()" :disabled="cart.length === 0"
                    class="w-full py-1.5 text-xs font-semibold text-slate-400 hover:text-rose-600 transition-all text-center">
                Limpiar Carrito
            </button>
        </div>
    </div>

    <!-- Modal 1: Table Selector -->
    <div x-show="showTableModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" style="display: none;">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-5 max-w-lg w-full shadow-2xl">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800 mb-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Seleccionar Mesa</h3>
                <button type="button" @click="showTableModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl">&times;</button>
            </div>

            <div class="grid grid-cols-4 gap-2 mb-4">
                <button type="button" @click="selectedTable = null; showTableModal = false"
                        class="p-3 rounded-xl border text-center font-bold text-xs"
                        :class="selectedTable === null ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-white border-slate-200 dark:border-slate-700'">
                    Mostrador
                </button>
                @foreach($tables as $t)
                    <button type="button" @click="selectedTable = {{ json_encode($t) }}; showTableModal = false"
                            class="p-3 rounded-xl border text-center font-bold text-xs transition-all"
                            :class="{{ $t->status === 'occupied' ? 'true' : 'false' }} ? 'bg-amber-50 dark:bg-amber-950/80 border-amber-300 dark:border-amber-600/50 text-amber-800 dark:text-amber-300' : 'bg-emerald-50 dark:bg-emerald-950/80 border-emerald-300 dark:border-emerald-600/50 text-emerald-800 dark:text-emerald-300'">
                        {{ $t->name }}
                        <span class="block text-[10px] font-normal uppercase">{{ $t->status === 'occupied' ? 'Ocupada' : 'Libre' }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Modal 2: Payment / Checkout Modal -->
    <div x-show="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" style="display: none;">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-xl w-full shadow-2xl max-h-[90vh] flex flex-col">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                <div>
                    <h3 class="text-lg font-black text-slate-900 dark:text-white">Completar Cobro</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Total a pagar: <strong class="text-emerald-600 dark:text-emerald-400 font-mono text-sm" x-text="formatMoney(cartTotal)"></strong></p>
                </div>
                <button type="button" @click="showPaymentModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl">&times;</button>
            </div>

            <!-- Payment Methods Tabs -->
            <div class="flex gap-2 py-3 overflow-x-auto shrink-0">
                @foreach($paymentMethods as $pm)
                    <button type="button" @click="selectedMethodId = {{ $pm->id }}; isCashMethod = {{ $pm->is_cash ? 'true' : 'false' }}"
                            class="px-3.5 py-2.5 rounded-xl border text-xs font-bold whitespace-nowrap transition-all"
                            :class="selectedMethodId === {{ $pm->id }} ? 'bg-emerald-600 text-white border-emerald-600 font-black shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700'">
                        {{ $pm->name }}
                    </button>
                @endforeach
            </div>

            <!-- Cash Quick Denominations (If Cash Selected) -->
            <template x-if="isCashMethod">
                <div class="space-y-3 mb-4">
                    <div class="flex items-center gap-2">
                        <button type="button" @click="setCashReceived(cartTotal)" class="flex-1 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-800 dark:text-white hover:bg-slate-200">
                            Efectivo Exacto
                        </button>
                        <button type="button" @click="setCashReceived(20000)" class="flex-1 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-800 dark:text-white hover:bg-slate-200">$ 20.000</button>
                        <button type="button" @click="setCashReceived(50000)" class="flex-1 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-800 dark:text-white hover:bg-slate-200">$ 50.000</button>
                        <button type="button" @click="setCashReceived(100000)" class="flex-1 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-bold text-slate-800 dark:text-white hover:bg-slate-200">$ 100.000</button>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Efectivo Recibido</label>
                            <input type="number" x-model.number="cashReceived"
                                   class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-mono text-lg font-bold rounded-xl px-3 py-2.5 focus:border-emerald-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Cambio / Vueltas</label>
                            <div class="w-full bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-400 font-mono text-lg font-black rounded-xl px-3 py-2.5 flex items-center justify-between">
                                <span x-text="formatMoney(cashChange)"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Electronic / Voucher Reference -->
            <template x-if="!isCashMethod">
                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Referencia / Comprobante (Opcional)</label>
                    <input type="text" x-model="paymentReference" placeholder="Ej: Aprobación #123456"
                           class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-xl px-3 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
                </div>
            </template>

            <!-- Action Buttons -->
            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex gap-3">
                <button type="button" @click="showPaymentModal = false" class="btn-touch flex-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-bold text-sm">
                    Cancelar
                </button>
                <button type="button" @click="confirmPayment()"
                        :disabled="isProcessing || (isCashMethod && cashReceived < cartTotal)"
                        class="btn-touch flex-[2] bg-emerald-600 hover:bg-emerald-500 active:scale-98 text-white font-black text-base shadow-lg shadow-emerald-600/30 disabled:opacity-40">
                    <span x-show="!isProcessing">CONFIRMAR VENTA</span>
                    <span x-show="isProcessing">REGISTRANDO...</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function posApp() {
    return {
        products: @json($products),
        categories: @json($categories),
        paymentMethods: @json($paymentMethods),
        search: '',
        selectedCategory: 'all',
        cart: [],
        selectedTable: null,
        mobileCartOpen: false,
        showTableModal: false,
        showPaymentModal: false,
        isProcessing: false,

        // Payment details
        selectedMethodId: {{ $paymentMethods->first()->id ?? 1 }},
        isCashMethod: {{ ($paymentMethods->first()->is_cash ?? true) ? 'true' : 'false' }},
        cashReceived: 0,
        paymentReference: '',

        get filteredProducts() {
            return this.products.filter(p => {
                const matchesCat = this.selectedCategory === 'all' || p.category_id == this.selectedCategory;
                const matchesSearch = !this.search || 
                    p.name.toLowerCase().includes(this.search.toLowerCase()) || 
                    (p.sku && p.sku.toLowerCase().includes(this.search.toLowerCase())) ||
                    (p.barcode && p.barcode.includes(this.search));
                return matchesCat && matchesSearch;
            });
        },

        get cartSubtotal() {
            return this.cart.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0);
        },

        get cartTotal() {
            return this.cartSubtotal;
        },

        get cashChange() {
            return Math.max(0, this.cashReceived - this.cartTotal);
        },

        addToCart(product, presentation) {
            window.PosSound.beep('add');

            const factor = presentation ? presentation.conversion_factor : 1;
            const price = presentation ? presentation.sale_price : product.price_sell;
            const presName = presentation ? presentation.name : (product.base_unit || 'Unidad');
            const presId = presentation ? presentation.id : null;

            // Check if item exists in cart
            const existingIdx = this.cart.findIndex(i => i.product_id === product.id && i.product_presentation_id === presId);

            if (existingIdx >= 0) {
                const newQty = this.cart[existingIdx].quantity + 1;
                if ((newQty * factor) > product.stock) {
                    alert(`Stock insuficiente para agregar más unidades de ${product.name}`);
                    return;
                }
                this.cart[existingIdx].quantity = newQty;
            } else {
                if (factor > product.stock) {
                    alert(`Stock insuficiente para agregar ${product.name}`);
                    return;
                }
                this.cart.push({
                    product_id: product.id,
                    name: product.name,
                    product_presentation_id: presId,
                    presentation_name: presName,
                    conversion_factor: factor,
                    unit_price: parseFloat(price),
                    quantity: 1,
                });
            }
        },

        increaseQty(idx) {
            const item = this.cart[idx];
            const prod = this.products.find(p => p.id === item.product_id);
            if (prod && ((item.quantity + 1) * item.conversion_factor) > prod.stock) {
                alert('Stock insuficiente');
                return;
            }
            item.quantity++;
            window.PosSound.beep('add');
        },

        decreaseQty(idx) {
            if (this.cart[idx].quantity > 1) {
                this.cart[idx].quantity--;
                window.PosSound.beep('add');
            } else {
                this.removeFromCart(idx);
            }
        },

        removeFromCart(idx) {
            this.cart.splice(idx, 1);
        },

        clearCart() {
            this.cart = [];
        },

        openPaymentModal() {
            this.cashReceived = this.cartTotal;
            this.showPaymentModal = true;
        },

        setCashReceived(amount) {
            this.cashReceived = amount;
        },

        formatMoney(val) {
            return '$ ' + Math.round(val || 0).toLocaleString('es-CO');
        },

        async confirmPayment() {
            if (this.isProcessing) return;
            this.isProcessing = true;

            const payload = {
                items: this.cart.map(i => ({
                    product_id: i.product_id,
                    quantity: i.quantity,
                    product_presentation_id: i.product_presentation_id,
                })),
                payments: [{
                    payment_method_id: this.selectedMethodId,
                    amount: this.cartTotal,
                    received_amount: this.isCashMethod ? this.cashReceived : this.cartTotal,
                    change_amount: this.isCashMethod ? this.cashChange : 0,
                    reference: this.paymentReference,
                }],
                account_id: this.selectedTable?.active_account?.id || null,
            };

            try {
                const response = await fetch('{{ route("pos.checkout") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Error al procesar la venta');
                }

                // Play success sound
                window.PosSound.beep('success');

                // Clear cart and close modal
                this.cart = [];
                this.showPaymentModal = false;

                // Update product stocks locally
                if (data.sale && data.sale.items) {
                    data.sale.items.forEach(si => {
                        const p = this.products.find(prod => prod.id === si.product_id);
                        if (p) {
                            p.stock -= (si.quantity * (si.conversion_factor_snapshot || 1));
                        }
                    });
                }

                // Open thermal receipt modal/window
                if (data.receipt_url) {
                    window.open(data.receipt_url, '_blank', 'width=400,height=600');
                }

                alert(data.message);
            } catch (err) {
                window.PosSound.beep('error');
                alert(err.message);
            } finally {
                this.isProcessing = false;
            }
        }
    };
}
</script>
@endpush
