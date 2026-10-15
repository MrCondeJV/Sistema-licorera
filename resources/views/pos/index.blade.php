@extends('layouts.app', ['title' => 'Venta Rápida POS'])

@section('content')
<div class="h-full min-h-0 flex-1 flex flex-col lg:flex-row gap-3.5 sm:gap-4 overflow-hidden"
     x-data="posApp()">

    <!-- Mobile Switcher Tabs (Only visible on screens < lg) -->
    <div class="lg:hidden flex bg-slate-100 dark:bg-slate-850 p-1.5 rounded-xl border border-slate-200 dark:border-slate-800 shrink-0">
        <button type="button" @click="activeTab = 'catalog'"
                class="flex-1 py-2 px-3 rounded-lg text-xs font-semibold transition-colors flex items-center justify-center gap-1.5"
                :class="activeTab === 'catalog' ? 'bg-slate-900 text-white dark:bg-slate-800 dark:text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'">
            <span>Catálogo</span>
        </button>
        <button type="button" @click="activeTab = 'cart'"
                class="flex-1 py-2 px-3 rounded-lg text-xs font-semibold transition-colors flex items-center justify-center gap-1.5"
                :class="activeTab === 'cart' ? 'bg-slate-900 text-white dark:bg-slate-800 dark:text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'">
            <span>Mostrador (<span x-text="formatMoney(cartTotal)"></span>)</span>
        </button>
    </div>

    <!-- LEFT / MAIN: Catalog & Products Area (Strictly 3 cols on PC, zero scroll) -->
    <div class="flex-1 min-w-0 flex flex-col h-full min-h-0 overflow-hidden bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-3.5 sm:p-5 shadow-sm"
         :class="activeTab === 'catalog' ? 'flex' : 'hidden lg:flex'">
        
        <!-- Cash Session Check Alert -->
        @if(!$openCashSession)
            <div class="mb-3 p-3 bg-amber-50 border border-amber-200 dark:bg-amber-950/80 dark:border-amber-500/40 rounded-2xl text-amber-800 dark:text-amber-300 flex items-center justify-between text-xs sm:text-sm shrink-0">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <span><strong>Atención:</strong> No hay un turno de caja abierto. Debes abrir caja para registrar ventas.</span>
                </div>
                <a href="{{ route('cash.index') }}" class="px-3.5 py-1.5 bg-amber-500 hover:bg-amber-400 text-white font-bold rounded-xl text-xs shrink-0 shadow-sm transition-all">
                    Abrir Caja &rarr;
                </a>
            </div>
        @endif

        <!-- Search Bar -->
        <div class="flex items-center gap-2.5 mb-3 shrink-0">
            <div class="relative flex-1">
                <input type="text" x-model="search" placeholder="Buscar producto por nombre, SKU o código de barra..."
                       class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-white rounded-2xl pl-11 pr-4 py-2.5 sm:py-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 font-medium">
                <svg class="w-5 h-5 text-slate-400 absolute left-3.5 top-3 sm:top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>

            <button type="button" @click="selectedCategory = 'all'"
                    class="hidden sm:inline-flex px-4 py-2.5 sm:py-3 rounded-2xl border text-xs font-bold uppercase tracking-wider transition-all shrink-0"
                    :class="selectedCategory === 'all' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-200'">
                Todos
            </button>
        </div>

        <!-- Horizontal Scrollable Categories -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 shrink-0 select-none no-scrollbar">
            <button type="button" @click="selectedCategory = 'all'"
                    class="sm:hidden px-3.5 py-1.5 rounded-xl border text-xs font-bold uppercase tracking-wider transition-all shrink-0"
                    :class="selectedCategory === 'all' ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700'">
                Todos
            </button>
            @foreach($categories as $cat)
                <button type="button" @click="selectedCategory = '{{ $cat->id }}'"
                        class="px-3.5 py-1.5 sm:py-2 rounded-xl border text-xs font-bold whitespace-nowrap transition-all flex items-center gap-1.5 shrink-0"
                        :class="selectedCategory === '{{ $cat->id }}' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-200 dark:hover:bg-slate-750'">
                    <span>{{ $cat->name }}</span>
                </button>
            @endforeach
        </div>

        <!-- Products Grid: Responsive 2-4 columns based on available space -->
        <div class="flex-1 min-h-0 overflow-y-auto pr-1 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-3 sm:gap-3.5 mt-2">
            <template x-for="product in filteredProducts" :key="product.id">
                <div class="bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-2xl p-4 sm:p-5 flex flex-col justify-between hover:border-emerald-400 dark:hover:border-emerald-600 transition-all relative group shadow-sm hover:shadow-md"
                     :class="product.stock <= 0 ? 'opacity-50' : ''">

                    <div>
                        <!-- Header with Photo and Info -->
                        <div class="flex items-start gap-3 mb-2.5">
                            <!-- Product Photo -->
                            <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-center overflow-hidden shrink-0 shadow-sm">
                                <template x-if="product.image_url">
                                    <img :src="product.image_url" :alt="product.name" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!product.image_url">
                                    <svg class="w-6 h-6 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </template>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-1 mb-1">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 truncate" x-text="product.category ? product.category.name : ''"></span>
                                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold tracking-wide"
                                          :class="product.stock <= 0 ? 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-400 border border-rose-200 dark:border-rose-800' : (product.stock <= product.stock_min ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-400 border border-amber-200 dark:border-amber-800' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800')">
                                        <span x-text="product.stock <= 0 ? 'Agotado' : product.stock + ' disp.'"></span>
                                    </span>
                                </div>
                                <h3 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white leading-snug break-words" x-text="product.name"></h3>
                                <template x-if="product.is_liquor">
                                    <span class="inline-block mt-0.5 text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 tracking-wider">Botella</span>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Presentations & Price Buttons -->
                    <div class="mt-3.5 pt-3 border-t border-slate-200 dark:border-slate-800">
                        <!-- Single Unit Price Button -->
                        <button type="button"
                                @click="addToCart(product, null)"
                                :disabled="product.stock <= 0"
                                class="w-full btn-touch min-h-[46px] py-2.5 px-3.5 bg-white dark:bg-slate-800 hover:bg-slate-900 hover:text-white dark:hover:bg-slate-700 text-slate-800 dark:text-white font-bold text-sm sm:text-base rounded-xl border border-slate-200 dark:border-slate-700 transition-all flex items-center justify-between active:scale-95 shadow-sm disabled:opacity-40 disabled:cursor-not-allowed">
                            <span class="font-medium" x-text="product.base_unit || 'Unidad'"></span>
                            <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400 group-hover:text-white" x-text="formatMoney(product.price_sell)"></span>
                        </button>

                        <!-- Extra Presentations for Beers (Six-Pack, Caja 24) -->
                        <template x-if="product.presentations && product.presentations.length > 0">
                            <div class="grid grid-cols-2 gap-1.5 mt-2">
                                <template x-for="pres in product.presentations" :key="pres.id">
                                    <button type="button"
                                            @click="addToCart(product, pres)"
                                            :disabled="product.stock < pres.conversion_factor"
                                            class="py-2 px-2 bg-white dark:bg-slate-800 hover:bg-slate-900 hover:text-white dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 font-semibold text-xs rounded-lg border border-slate-200 dark:border-slate-700 transition-all flex flex-col items-center justify-center min-h-[42px] disabled:opacity-40">
                                        <span class="truncate max-w-full font-medium" x-text="pres.name"></span>
                                        <span class="font-mono text-xs font-bold text-slate-900 dark:text-slate-100 group-hover:text-white" x-text="formatMoney(pres.sale_price)"></span>
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- RIGHT: Cart & Checkout Sidebar (Proportional PC Ticket, Zero Scroll) -->
    <div class="w-full lg:w-96 xl:w-[420px] 2xl:w-[460px] flex flex-col h-full min-h-0 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-4 sm:p-5 shadow-sm shrink-0 overflow-hidden"
         :class="activeTab === 'cart' ? 'flex' : 'hidden lg:flex'">
        
        <!-- Cart Header: Table & Customer Selector Area -->
        <div class="pb-3 border-b border-slate-200 dark:border-slate-800 space-y-2 shrink-0">
            @if($openCashSession)
                <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-950 px-2.5 py-1 rounded-xl border border-slate-200 dark:border-slate-800">
                    <span class="flex items-center gap-1.5 font-bold text-slate-700 dark:text-slate-300">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        {{ $openCashSession->cashRegister->name ?? 'Caja Principal' }}
                    </span>
                    <span>Turno: <strong class="text-slate-800 dark:text-slate-200">{{ $openCashSession->user->name }}</strong></span>
                </div>
            @endif
            <!-- Row 1: Destination (Mesa vs Mostrador) -->
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Destino de la Venta</span>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="w-2.5 h-2.5 rounded-full" :class="selectedTable ? 'bg-amber-500' : 'bg-emerald-500'"></span>
                        <span class="text-sm font-black text-slate-900 dark:text-white" x-text="selectedTable ? 'Mesa ' + selectedTable.name : 'Venta de Mostrador'"></span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" @click="showTableModal = true" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold border border-slate-200 dark:border-slate-700 flex items-center gap-1.5 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                        <span x-text="selectedTable ? 'Cambiar Mesa' : 'Asignar Mesa'"></span>
                    </button>
                </div>
            </div>

            <!-- Row 2: Customer Assignment -->
            <div class="p-2.5 bg-slate-50 dark:bg-slate-950/80 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2.5 min-w-0 pr-2">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0"
                         :class="selectedCustomer ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' : 'bg-slate-100 dark:bg-slate-800 text-slate-500'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div class="min-w-0 truncate">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Cliente Asignado</span>
                        <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate"
                           x-text="selectedCustomer ? selectedCustomer.name : 'Consumidor Final (General)'"></p>
                        <template x-if="selectedCustomer && parseFloat(selectedCustomer.current_debt) > 0">
                            <span class="text-[10px] text-amber-600 dark:text-amber-400 font-bold block"
                                  x-text="'Deuda acumulada: ' + formatMoney(selectedCustomer.current_debt)"></span>
                        </template>
                    </div>
                </div>

                <div class="flex items-center gap-1.5 shrink-0">
                    <button type="button" @click="openCustomerModal()"
                            class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold transition-colors">
                        <span x-text="selectedCustomer ? 'Cambiar' : '+ Asignar Cliente'"></span>
                    </button>
                    <template x-if="selectedCustomer">
                        <button type="button" @click="clearCustomer()" title="Quitar cliente" class="p-1.5 text-slate-400 hover:text-rose-500 rounded-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Cart Items List: SCROLLS INTERNALLY WITH PHOTO, BIG BUTTONS & FULL LEGIBILITY -->
        <div class="flex-1 min-h-0 overflow-y-auto py-2.5 space-y-2.5 pr-1">
            <template x-if="cart.length === 0">
                <div class="h-full flex flex-col items-center justify-center text-center p-6 text-slate-400">
                    <svg class="w-14 h-14 mb-2.5 text-slate-300 dark:text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    <p class="text-sm font-bold text-slate-700 dark:text-slate-200">El mostrador está listo</p>
                    <p class="text-xs text-slate-400 mt-1">Toca cualquier producto del catálogo para iniciar la venta</p>
                </div>
            </template>

            <template x-for="(item, idx) in cart" :key="idx">
                <div class="p-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm hover:border-slate-300 dark:hover:border-slate-700 transition-all flex flex-col gap-2.5">
                    <!-- Top Row: Thumbnail + Product Name & Unit Price + Delete Button -->
                    <div class="flex items-start justify-between gap-2.5">
                        <div class="flex items-center gap-2.5 min-w-0 flex-1">
                            <div class="w-11 h-11 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-750 flex items-center justify-center overflow-hidden shrink-0 shadow-sm">
                                <template x-if="item.image_url">
                                    <img :src="item.image_url" :alt="item.name" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!item.image_url">
                                    <svg class="w-5 h-5 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                </template>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm sm:text-base font-bold text-slate-900 dark:text-white leading-tight break-normal" x-text="item.name"></p>
                                <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 text-[11px] font-semibold font-mono" x-text="item.presentation_name"></span>
                                    <span class="text-xs text-slate-500 dark:text-slate-400 font-mono" x-text="'$' + Math.round(item.unit_price).toLocaleString('es-CO') + ' c/u'"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Delete Button (Top-Right) -->
                        <button type="button" @click="removeFromCart(idx)" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-xl transition-all shrink-0" title="Eliminar ítem">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </div>

                    <!-- Bottom Row: Big Tactile Stepper + Total Price -->
                    <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-800/80">
                        <!-- Big Stepper -->
                        <div class="flex items-center bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-1 rounded-xl">
                            <button type="button" @click="decreaseQty(idx)" 
                                    class="w-10 h-10 sm:w-11 sm:h-11 rounded-lg bg-white dark:bg-slate-900 text-slate-800 dark:text-white font-black text-xl flex items-center justify-center active:scale-90 shadow-sm transition-all"
                                    title="Restar">-</button>
                            <span class="w-10 sm:w-11 text-center text-base sm:text-lg font-mono font-black text-slate-900 dark:text-white" x-text="item.quantity"></span>
                            <button type="button" @click="increaseQty(idx)" 
                                    class="w-10 h-10 sm:w-11 sm:h-11 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xl flex items-center justify-center active:scale-90 shadow-sm transition-all"
                                    title="Sumar">+</button>
                        </div>

                        <!-- Subtotal -->
                        <div class="text-right">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Subtotal</span>
                            <span class="text-lg sm:text-xl font-mono font-black text-emerald-600 dark:text-emerald-400" x-text="formatMoney(item.unit_price * item.quantity)"></span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Totals & Big Checkout Button: ALWAYS VISIBLE ON SCREEN (shrink-0) -->
        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 space-y-2.5 shrink-0">
            <div class="flex justify-between items-baseline">
                <span class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Total a Cobrar</span>
                <span class="text-3xl font-black font-mono text-emerald-600 dark:text-emerald-400" x-text="formatMoney(cartTotal)"></span>
            </div>

            <!-- Cobrar Action Button -->
            <button type="button"
                    @click="openPaymentModal()"
                    :disabled="cart.length === 0 || isProcessing"
                    class="w-full btn-touch min-h-[50px] bg-emerald-700 hover:bg-emerald-800 active:scale-98 text-white font-bold text-base rounded-xl shadow-md disabled:opacity-40 disabled:cursor-not-allowed transition-all flex items-center justify-center gap-2 uppercase tracking-wide">
                <span x-show="!isProcessing" x-text="'COBRAR ' + formatMoney(cartTotal)"></span>
                <span x-show="isProcessing">PROCESANDO...</span>
            </button>

            <button type="button" @click="clearCart()" :disabled="cart.length === 0"
                    class="w-full py-1 text-xs font-semibold text-slate-400 hover:text-rose-600 transition-all text-center">
                Limpiar Mostrador
            </button>
        </div>
    </div>

    <!-- Modal 1: Table Selector -->
    <div x-show="showTableModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" style="display: none;">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800 mb-4">
                <h3 class="text-lg font-black text-slate-900 dark:text-white">Seleccionar Mesa</h3>
                <button type="button" @click="showTableModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-2xl font-bold">&times;</button>
            </div>

            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2.5 mb-4">
                <button type="button" @click="selectedTable = null; showTableModal = false"
                        class="p-3.5 rounded-2xl border text-center font-bold text-xs transition-all shadow-sm"
                        :class="selectedTable === null ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-white border-slate-200 dark:border-slate-700'">
                    Mostrador
                </button>
                @foreach($tables as $t)
                    <button type="button" @click="selectTable({{ json_encode($t) }})"
                            class="p-3.5 rounded-2xl border text-center font-bold text-xs transition-all shadow-sm"
                            :class="{{ $t->status === 'occupied' ? 'true' : 'false' }} ? 'bg-amber-50 dark:bg-amber-950/80 border-amber-300 dark:border-amber-600/50 text-amber-800 dark:text-amber-300' : 'bg-emerald-50 dark:bg-emerald-950/80 border-emerald-300 dark:border-emerald-600/50 text-emerald-800 dark:text-emerald-300'">
                        {{ $t->name }}
                        <span class="block text-[10px] font-normal uppercase mt-0.5">{{ $t->status === 'occupied' ? 'Ocupada' : 'Libre' }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Modal 2: Customer Selector & Quick Create -->
    <div x-show="showCustomerModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" style="display: none;">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl max-h-[90vh] flex flex-col">
            
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800 mb-4">
                <div>
                    <h3 class="text-lg font-black text-slate-900 dark:text-white">Asignar Cliente</h3>
                    <p class="text-xs text-slate-400">Selecciona o crea un cliente para esta venta</p>
                </div>
                <button type="button" @click="showCustomerModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-2xl font-bold">&times;</button>
            </div>

            <!-- Form: Create New Customer Inline -->
            <div x-show="showNewCustomerForm" class="mb-4 p-4 bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-2xl space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Nuevo Cliente</h4>
                    <button type="button" @click="showNewCustomerForm = false" class="text-xs text-slate-400 hover:text-slate-600">Cancelar</button>
                </div>

                <div class="space-y-2.5">
                    <div>
                        <label class="block text-[11px] font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Nombre Completo *</label>
                        <input type="text" x-model="newCustomer.name" placeholder="Ej: Andrés Restrepo"
                               class="input-night w-full text-xs">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Teléfono / Celular</label>
                            <input type="text" x-model="newCustomer.phone" placeholder="Ej: 3001234567"
                                   class="input-night w-full text-xs font-mono">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Cédula / NIT</label>
                            <input type="text" x-model="newCustomer.tax_id" placeholder="Ej: 10203040"
                                   class="input-night w-full text-xs font-mono">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold uppercase text-slate-600 dark:text-slate-400 mb-1">Dirección (Opcional)</label>
                        <input type="text" x-model="newCustomer.address" placeholder="Ej: Calle 10 # 40-20"
                               class="input-night w-full text-xs">
                    </div>

                    <button type="button" @click="saveQuickCustomer()" :disabled="isSavingCustomer"
                            class="w-full btn-touch py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-md transition-all">
                        <span x-show="!isSavingCustomer">Guardar y Asignar a la Venta</span>
                        <span x-show="isSavingCustomer">Guardando...</span>
                    </button>
                </div>
            </div>

            <!-- Search and Action Toolbar -->
            <div x-show="!showNewCustomerForm" class="space-y-3 mb-3">
                <div class="flex items-center gap-2">
                    <div class="relative flex-1">
                        <input type="text" x-model="customerSearch" placeholder="Buscar por nombre, teléfono o cédula..."
                               class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-white rounded-xl pl-9 pr-3 py-2 text-xs focus:outline-none focus:border-emerald-500">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>

                    <button type="button" @click="showNewCustomerForm = true"
                            class="px-3 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-sm whitespace-nowrap flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                        <span>Nuevo</span>
                    </button>
                </div>

                <!-- Option: Consumidor Final -->
                <button type="button" @click="clearCustomer()"
                        class="w-full p-2.5 rounded-xl border border-dashed border-slate-300 dark:border-slate-700 text-left hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Consumidor Final (General)</span>
                        <span class="block text-[10px] text-slate-400">Venta rápida sin asociar cliente</span>
                    </div>
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">&rarr;</span>
                </button>
            </div>

            <!-- Customers List -->
            <div x-show="!showNewCustomerForm" class="flex-1 overflow-y-auto space-y-1.5 pr-1 divide-y divide-slate-100 dark:divide-slate-800">
                <template x-for="c in filteredCustomers" :key="c.id">
                    <button type="button" @click="selectCustomer(c)"
                            class="w-full py-2.5 px-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors text-left flex items-center justify-between"
                            :class="selectedCustomer && selectedCustomer.id === c.id ? 'bg-emerald-50 dark:bg-emerald-950/60 font-bold border border-emerald-200 dark:border-emerald-800' : ''">
                        <div>
                            <p class="text-xs font-bold text-slate-900 dark:text-white" x-text="c.name"></p>
                            <p class="text-[11px] text-slate-400 font-mono">
                                <span x-show="c.phone" x-text="'Tel: ' + c.phone + ' '"></span>
                                <span x-show="c.tax_id" x-text="'ID: ' + c.tax_id"></span>
                            </p>
                        </div>
                        <template x-if="c.current_debt > 0">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-400 font-mono"
                                  x-text="'Debe: ' + formatMoney(c.current_debt)"></span>
                        </template>
                    </button>
                </template>

                <template x-if="filteredCustomers.length === 0">
                    <div class="py-8 text-center text-slate-400 text-xs">
                        No se encontraron clientes con ese criterio.
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Modal 3: Payment / Checkout Modal -->
    <div x-show="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" style="display: none;">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-xl w-full shadow-2xl max-h-[90vh] flex flex-col">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                <div>
                    <h3 class="text-xl font-black text-slate-900 dark:text-white">Completar Cobro</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400">Total a pagar: <strong class="text-emerald-600 dark:text-emerald-400 font-mono text-base" x-text="formatMoney(cartTotal)"></strong></p>
                </div>
                <button type="button" @click="showPaymentModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-2xl font-bold">&times;</button>
            </div>

            <!-- Customer Summary in Modal -->
            <div class="py-2.5 px-3.5 my-3 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full" :class="selectedCustomer ? 'bg-emerald-600' : 'bg-slate-400'"></span>
                    <span class="text-slate-500 font-medium">Cliente:</span>
                    <strong class="text-slate-900 dark:text-white" x-text="selectedCustomer ? selectedCustomer.name : 'Consumidor Final (General)'"></strong>
                </div>
                <template x-if="selectedCustomer">
                    <div class="text-right">
                        <span class="text-slate-500 text-[11px]">Deuda actual:</span>
                        <span class="font-mono font-bold text-amber-600 dark:text-amber-400" x-text="formatMoney(selectedCustomer.current_debt || 0)"></span>
                    </div>
                </template>
            </div>

            <!-- Payment Methods / Operation Type Tabs -->
            <div class="flex flex-wrap gap-2 pb-3 shrink-0">
                @foreach($paymentMethods as $pm)
                    <button type="button" @click="chargeToDebt = false; selectedMethodId = {{ $pm->id }}; isCashMethod = {{ $pm->is_cash ? 'true' : 'false' }}"
                            class="px-3.5 py-2 rounded-lg border text-xs font-semibold whitespace-nowrap transition-colors flex items-center gap-1.5"
                            :class="(!chargeToDebt && selectedMethodId === {{ $pm->id }}) ? 'bg-slate-900 text-white dark:bg-slate-800 dark:text-white border-slate-900 dark:border-slate-700 shadow-sm' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-200'">
                        <span>{{ $pm->name }}</span>
                    </button>
                @endforeach

                <!-- Option: Agregar a cuenta del cliente (Only when a customer is selected) -->
                <template x-if="selectedCustomer">
                    <button type="button" @click="chargeToDebt = true"
                            class="px-3.5 py-2 rounded-lg border text-xs font-semibold whitespace-nowrap transition-colors flex items-center gap-1.5"
                            :class="chargeToDebt ? 'bg-amber-600 text-white border-amber-600 shadow-sm' : 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800 hover:bg-amber-100'">
                        <span>Agregar a Cuenta</span>
                    </button>
                </template>
            </div>

            <!-- MODE 1: Charge to Customer Account / Debt (No immediate payment) -->
            <template x-if="chargeToDebt">
                <div class="space-y-3 mb-4 p-4 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 rounded-xl">
                    <div class="flex items-center gap-2 text-amber-800 dark:text-amber-300 font-bold text-sm">
                        <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span>Cargar a Deuda del Cliente (Sin Cobro Inmediato)</span>
                    </div>
                    <p class="text-xs text-amber-900/80 dark:text-amber-200/90 leading-relaxed">
                        Esta venta se registrará sin recibir dinero en caja. El total de <strong class="font-mono text-amber-800 dark:text-amber-200 font-bold" x-text="formatMoney(cartTotal)"></strong> se sumará automáticamente a las deudas pendientes de <strong class="text-slate-900 dark:text-white" x-text="selectedCustomer?.name"></strong>.
                    </p>
                    <div class="pt-2 border-t border-amber-200 dark:border-amber-800/80 flex items-center justify-between text-xs">
                        <span class="text-amber-800 dark:text-amber-400 font-semibold">Nuevo saldo de deuda:</span>
                        <span class="font-mono font-bold text-amber-900 dark:text-amber-200 text-base" x-text="formatMoney((parseFloat(selectedCustomer?.current_debt) || 0) + cartTotal)"></span>
                    </div>
                </div>
            </template>

            <!-- MODE 2: Cash Quick Denominations (If Cash Selected and NOT chargeToDebt) -->
            <template x-if="!chargeToDebt && isCashMethod">
                <div class="space-y-3 mb-4">
                    <div class="flex items-center gap-2">
                        <button type="button" @click="setCashReceived(cartTotal)" class="flex-1 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-800 dark:text-white hover:bg-slate-200">
                            Efectivo Exacto
                        </button>
                        <button type="button" @click="setCashReceived(20000)" class="flex-1 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-800 dark:text-white hover:bg-slate-200">$ 20.000</button>
                        <button type="button" @click="setCashReceived(50000)" class="flex-1 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-800 dark:text-white hover:bg-slate-200">$ 50.000</button>
                        <button type="button" @click="setCashReceived(100000)" class="flex-1 py-2 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-semibold text-slate-800 dark:text-white hover:bg-slate-200">$ 100.000</button>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400 mb-1">Efectivo Recibido</label>
                            <input type="number" x-model.number="cashReceived"
                                   class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white font-mono text-xl font-bold rounded-lg px-3.5 py-2.5 focus:border-slate-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400 mb-1">Cambio / Vueltas</label>
                            <div class="w-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-900 dark:text-white font-mono text-xl font-bold rounded-lg px-3.5 py-2.5 flex items-center justify-between">
                                <span x-text="formatMoney(cashChange)"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- MODE 3: Electronic / Voucher Reference (If Bank Selected and NOT chargeToDebt) -->
            <template x-if="!chargeToDebt && !isCashMethod">
                <div class="mb-4">
                    <label class="block text-xs font-semibold uppercase text-slate-500 dark:text-slate-400 mb-1">Referencia / Comprobante de Transacción (Opcional)</label>
                    <input type="text" x-model="paymentReference" placeholder="Ej: Aprobación #123456 / Comprobante Bancario"
                           class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-700 text-slate-900 dark:text-white rounded-lg px-3.5 py-2.5 text-sm focus:border-slate-500 focus:outline-none">
                </div>
            </template>

            <!-- Action Buttons -->
            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex gap-3">
                <button type="button" @click="showPaymentModal = false" class="btn-touch flex-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-semibold text-sm rounded-lg">
                    Cancelar
                </button>
                <button type="button" @click="confirmPayment()"
                        :disabled="isProcessing || (!chargeToDebt && isCashMethod && cashReceived < cartTotal)"
                        class="btn-touch flex-[2] active:scale-98 text-white font-bold text-base shadow-sm disabled:opacity-40 transition-all flex items-center justify-center gap-2 rounded-lg"
                        :class="chargeToDebt ? 'bg-amber-600 hover:bg-amber-700' : 'bg-emerald-700 hover:bg-emerald-800'">
                    <span x-show="!isProcessing && !chargeToDebt">CONFIRMAR VENTA</span>
                    <span x-show="!isProcessing && chargeToDebt">CARGAR A CUENTA</span>
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
        tables: @json($tables),
        customers: @json($customers),
        paymentMethods: @json($paymentMethods),
        search: '',
        selectedCategory: 'all',
        cart: [],
        selectedTable: null,
        selectedCustomer: null,
        activeTab: 'catalog',
        showTableModal: false,
        showCustomerModal: false,
        showPaymentModal: false,
        showNewCustomerForm: false,
        customerSearch: '',
        isProcessing: false,
        isSavingCustomer: false,

        newCustomer: {
            name: '',
            phone: '',
            tax_id: '',
            address: ''
        },

        // Payment details
        chargeToDebt: false,
        selectedMethodId: {{ $paymentMethods->first()->id ?? 1 }},
        isCashMethod: {{ ($paymentMethods->first()->is_cash ?? true) ? 'true' : 'false' }},
        cashReceived: 0,
        paymentReference: '',

        init() {
            // Check if table_id is passed in URL query parameter
            const urlParams = new URLSearchParams(window.location.search);
            const tableId = urlParams.get('table_id');
            if (tableId) {
                const t = this.tables.find(tbl => tbl.id == tableId);
                if (t) {
                    this.selectTable(t);
                }
            }
        },

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

        get filteredCustomers() {
            if (!this.customerSearch) return this.customers;
            const q = this.customerSearch.toLowerCase();
            return this.customers.filter(c =>
                c.name.toLowerCase().includes(q) ||
                (c.phone && c.phone.includes(q)) ||
                (c.tax_id && c.tax_id.toLowerCase().includes(q))
            );
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

        selectTable(table) {
            this.selectedTable = table;
            this.showTableModal = false;

            // If table has an active account with items, load them into cart
            if (table && table.active_account && table.active_account.items && table.active_account.items.length > 0) {
                this.cart = table.active_account.items.map(it => {
                    const prod = this.products.find(p => p.id == it.product_id);
                    return {
                        product_id: it.product_id,
                        name: it.product_name_snapshot,
                        image_url: prod ? prod.image_url : null,
                        product_presentation_id: it.product_presentation_id,
                        presentation_name: it.presentation_name_snapshot,
                        conversion_factor: it.conversion_factor_snapshot || 1,
                        unit_price: parseFloat(it.unit_price),
                        quantity: parseFloat(it.quantity)
                    };
                });
            }
        },

        openCustomerModal() {
            this.showCustomerModal = true;
            this.showNewCustomerForm = false;
            this.customerSearch = '';
        },

        selectCustomer(customer) {
            this.selectedCustomer = customer;
            this.showCustomerModal = false;
        },

        clearCustomer() {
            this.selectedCustomer = null;
            this.showCustomerModal = false;
        },

        async saveQuickCustomer() {
            if (!this.newCustomer.name || !this.newCustomer.name.trim()) {
                window.AppAlert.warning('Nombre requerido', 'El nombre del cliente es obligatorio.');
                return;
            }
            this.isSavingCustomer = true;
            try {
                const response = await fetch('{{ route("pos.customer.quick") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(this.newCustomer)
                });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Error al guardar cliente');
                }
                this.customers.unshift(data.customer);
                this.selectedCustomer = data.customer;
                this.newCustomer = { name: '', phone: '', tax_id: '', address: '' };
                this.showNewCustomerForm = false;
                this.showCustomerModal = false;
                window.PosSound.beep('success');
                window.AppAlert.toast('Cliente creado y seleccionado', 'success');
            } catch (e) {
                window.PosSound.beep('error');
                window.AppAlert.error('Error al guardar', e.message);
            } finally {
                this.isSavingCustomer = false;
            }
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
                    window.AppAlert.warning('Stock Insuficiente', `No hay suficiente stock para agregar más unidades de ${product.name}`);
                    return;
                }
                this.cart[existingIdx].quantity = newQty;
            } else {
                if (factor > product.stock) {
                    window.AppAlert.warning('Stock Insuficiente', `Stock insuficiente para agregar ${product.name}`);
                    return;
                }
                this.cart.push({
                    product_id: product.id,
                    name: product.name,
                    image_url: product.image_url,
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
                window.AppAlert.warning('Stock Insuficiente', 'No hay más unidades disponibles en stock.');
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
            this.chargeToDebt = false;
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
                charge_to_debt: this.chargeToDebt,
                payments: this.chargeToDebt ? [] : [{
                    payment_method_id: this.selectedMethodId,
                    amount: this.cartTotal,
                    received_amount: this.isCashMethod ? this.cashReceived : this.cartTotal,
                    change_amount: this.isCashMethod ? this.cashChange : 0,
                    reference: this.paymentReference,
                }],
                account_id: this.selectedTable?.active_account?.id || null,
                customer_id: this.selectedCustomer?.id || null,
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

                // If sale was charged to debt, update customer current_debt locally
                if (this.chargeToDebt && this.selectedCustomer) {
                    const newDebt = (parseFloat(this.selectedCustomer.current_debt) || 0) + this.cartTotal;
                    this.selectedCustomer.current_debt = newDebt;
                    const custInList = this.customers.find(c => c.id === this.selectedCustomer.id);
                    if (custInList) {
                        custInList.current_debt = newDebt;
                    }
                }

                // Calculate cash change before resetting
                const changeAmount = this.isCashMethod ? this.cashChange : 0;

                // Clear cart and close modal
                this.cart = [];
                this.selectedCustomer = null;
                this.chargeToDebt = false;
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

                // Show alert with Entendido, Imprimir Ticket y Enviar WhatsApp
                if (window.AppAlert && window.AppAlert.saleSuccess) {
                    await window.AppAlert.saleSuccess({
                        title: '¡Venta Completada!',
                        message: data.message,
                        receiptUrl: data.receipt_url,
                        changeAmount: changeAmount,
                        whatsappUrl: data.whatsapp_url,
                        whatsappMessage: data.whatsapp_message,
                        customerPhone: data.customer_phone || (this.selectedCustomer ? this.selectedCustomer.phone : '')
                    });
                } else if (window.AppAlert) {
                    window.AppAlert.success('¡Venta Completada!', data.message);
                }
            } catch (err) {
                window.PosSound.beep('error');
                window.AppAlert.error('Error en cobro', err.message);
            } finally {
                this.isProcessing = false;
            }
        }
    };
}
</script>
@endpush
