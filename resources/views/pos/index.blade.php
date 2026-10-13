@extends('layouts.app', ['title' => 'Venta Rápida POS'])

@section('content')
<div class="h-full flex flex-col lg:flex-row gap-3 -m-3 sm:-m-4 p-3 sm:p-4 overflow-hidden"
     x-data="posApp()">

    <!-- LEFT / MAIN: Catalog & Products Area -->
    <div class="flex-1 flex flex-col h-full overflow-hidden bg-night-900/60 border border-night-800/80 rounded-2xl p-3">
        
        <!-- Cash Session Check Alert -->
        @if(!$openCashSession)
            <div class="mb-3 p-3 bg-amber-950/80 border border-amber-500/40 rounded-xl text-amber-300 flex items-center justify-between text-xs sm:text-sm">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <span><strong>Atención:</strong> No tienes turno de caja abierto. Debes abrir caja para registrar ventas.</span>
                </div>
                <a href="{{ route('cash.index') }}" class="px-3 py-1.5 bg-amber-500 hover:bg-amber-400 text-night-950 font-bold rounded-lg text-xs shrink-0">
                    Abrir Caja &rarr;
                </a>
            </div>
        @endif

        <!-- Search Bar & Filters -->
        <div class="flex items-center gap-2 mb-3">
            <div class="relative flex-1">
                <input type="text" x-model="search" placeholder="Buscar por nombre, código o SKU..."
                       class="w-full bg-night-850 border border-night-750 text-white rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:border-neon-green">
                <svg class="w-5 h-5 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <button type="button" @click="selectedCategory = 'all'"
                    class="px-3 py-2.5 rounded-xl border text-xs font-bold uppercase transition-all"
                    :class="selectedCategory === 'all' ? 'bg-neon-green text-night-950 border-neon-green' : 'bg-night-850 text-slate-300 border-night-750'">
                Todos
            </button>
        </div>

        <!-- Horizontal Scrollable Categories -->
        <div class="flex items-center gap-2 overflow-x-auto pb-2 shrink-0 select-none">
            @foreach($categories as $cat)
                <button type="button" @click="selectedCategory = '{{ $cat->id }}'"
                        class="px-3.5 py-2 rounded-xl border text-xs font-bold whitespace-nowrap transition-all flex items-center gap-1.5"
                        :class="selectedCategory === '{{ $cat->id }}' ? 'bg-neon-cyan text-night-950 border-neon-cyan shadow-lg shadow-neon-cyan/20' : 'bg-night-850 text-slate-300 border-night-750 hover:bg-night-800'">
                    <span>{{ $cat->name }}</span>
                </button>
            @endforeach
        </div>

        <!-- Products Grid with internal scroll -->
        <div class="flex-1 overflow-y-auto pr-1 grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-2.5 mt-2">
            <template x-for="product in filteredProducts" :key="product.id">
                <div class="card-night p-3 flex flex-col justify-between border-night-750/60 hover:border-night-600 transition-all relative group"
                     :class="product.stock <= 0 ? 'opacity-50' : ''">

                    <div>
                        <!-- Header / Badges -->
                        <div class="flex items-start justify-between gap-1 mb-1.5">
                            <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-400 truncate" x-text="product.category ? product.category.name : ''"></span>
                            <span class="badge-neon text-[10px]"
                                  :class="product.stock <= 0 ? 'bg-rose-950 text-rose-400 border border-rose-800' : (product.stock <= product.stock_min ? 'bg-amber-950 text-amber-400 border border-amber-800' : 'bg-emerald-950 text-emerald-400 border border-emerald-800')">
                                <span x-text="product.stock <= 0 ? 'Agotado' : product.stock + ' disp.'"></span>
                            </span>
                        </div>

                        <!-- Product Name -->
                        <h3 class="text-sm font-bold text-white leading-tight line-clamp-2" x-text="product.name"></h3>

                        <!-- Rule description -->
                        <template x-if="product.is_liquor">
                            <span class="inline-block mt-1 text-[10px] text-amber-400/90 font-semibold">🍾 Botella completa</span>
                        </template>
                    </div>

                    <!-- Presentations & Price Buttons -->
                    <div class="mt-3 pt-2 border-t border-night-800/80">
                        <!-- Single Unit Price Button -->
                        <button type="button"
                                @click="addToCart(product, null)"
                                :disabled="product.stock <= 0"
                                class="w-full btn-touch min-h-[46px] py-2 bg-night-800 hover:bg-neon-green hover:text-night-950 text-white font-bold text-xs sm:text-sm rounded-xl border border-night-700/80 transition-all flex items-center justify-between px-3 active:scale-95">
                            <span x-text="product.base_unit || 'Unidad'"></span>
                            <span class="font-mono text-neon-green group-hover:text-night-950" x-text="formatMoney(product.price_sell)"></span>
                        </button>

                        <!-- Extra Presentations for Beers (Six-Pack, Caja 24) -->
                        <template x-if="product.presentations && product.presentations.length > 0">
                            <div class="grid grid-cols-2 gap-1.5 mt-1.5">
                                <template x-for="pres in product.presentations" :key="pres.id">
                                    <button type="button"
                                            @click="addToCart(product, pres)"
                                            :disabled="product.stock < pres.conversion_factor"
                                            class="py-1.5 px-2 bg-night-850 hover:bg-neon-cyan hover:text-night-950 text-slate-200 font-semibold text-[11px] rounded-lg border border-night-750 transition-all flex flex-col items-center justify-center">
                                        <span class="truncate max-w-full" x-text="pres.name"></span>
                                        <span class="font-mono text-[10px] text-neon-cyan" x-text="formatMoney(pres.sale_price)"></span>
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- RIGHT: Cart & Checkout Sidebar (Tactile 60px buttons, order summary) -->
    <div class="w-full lg:w-96 flex flex-col h-full bg-night-900 border border-night-800 rounded-2xl p-3.5 shadow-2xl shrink-0">
        
        <!-- Cart Header: Table or Counter Mode -->
        <div class="flex items-center justify-between pb-3 border-b border-night-800">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Destino de la Venta</span>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-neon-green"></span>
                    <span class="text-sm font-black text-white" x-text="selectedTable ? 'Mesa ' + selectedTable.name : 'Venta de Mostrador'"></span>
                </div>
            </div>

            <!-- Table Selector Modal Trigger -->
            <button type="button" @click="showTableModal = true" class="px-2.5 py-1.5 bg-night-800 hover:bg-night-750 text-slate-300 rounded-xl text-xs font-semibold border border-night-750 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                </svg>
                <span x-text="selectedTable ? 'Cambiar' : 'Asignar Mesa'"></span>
            </button>
        </div>

        <!-- Cart Items List (Internal scroll) -->
        <div class="flex-1 overflow-y-auto py-2 space-y-2 pr-1">
            <template x-if="cart.length === 0">
                <div class="h-full flex flex-col items-center justify-center text-center p-6 text-slate-500">
                    <svg class="w-12 h-12 mb-2 text-night-750" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    <p class="text-xs font-semibold">El carrito está vacío</p>
                    <p class="text-[11px] text-slate-600 mt-1">Selecciona productos del catálogo para comenzar la orden</p>
                </div>
            </template>

            <template x-for="(item, idx) in cart" :key="idx">
                <div class="p-2.5 bg-night-850 border border-night-750 rounded-xl flex items-center justify-between gap-2">
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-white truncate" x-text="item.name"></p>
                        <p class="text-[11px] text-slate-400">
                            <span class="text-neon-cyan" x-text="item.presentation_name"></span> &bull; 
                            <span x-text="formatMoney(item.unit_price)"></span>
                        </p>
                    </div>

                    <!-- Stepper & Subtotal -->
                    <div class="flex items-center gap-2">
                        <div class="flex items-center bg-night-900 border border-night-750 rounded-lg overflow-hidden">
                            <button type="button" @click="decreaseQty(idx)" class="w-8 h-8 flex items-center justify-center text-slate-300 hover:bg-night-800 font-bold active:scale-95">-</button>
                            <span class="w-8 text-center text-xs font-mono font-bold text-white" x-text="item.quantity"></span>
                            <button type="button" @click="increaseQty(idx)" class="w-8 h-8 flex items-center justify-center text-slate-300 hover:bg-night-800 font-bold active:scale-95">+</button>
                        </div>

                        <span class="w-16 text-right text-xs font-mono font-bold text-neon-green" x-text="formatMoney(item.unit_price * item.quantity)"></span>

                        <button type="button" @click="removeFromCart(idx)" class="text-slate-500 hover:text-rose-400 p-1">
                            &times;
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- Totals & Big Checkout Button -->
        <div class="pt-3 border-t border-night-800 space-y-2">
            <div class="flex justify-between text-xs text-slate-400">
                <span>Subtotal</span>
                <span class="font-mono text-slate-200" x-text="formatMoney(cartSubtotal)"></span>
            </div>

            <div class="flex justify-between items-baseline pt-1 border-t border-night-800/60">
                <span class="text-sm font-bold text-white">TOTAL A COBRAR</span>
                <span class="text-2xl font-black font-mono text-neon-green" x-text="formatMoney(cartTotal)"></span>
            </div>

            <!-- Cobrar Action Button (>= 60px touch target) -->
            <button type="button"
                    @click="openPaymentModal()"
                    :disabled="cart.length === 0 || isProcessing"
                    class="w-full btn-touch bg-gradient-to-r from-neon-green to-emerald-400 hover:from-neon-green hover:to-emerald-300 active:scale-95 text-night-950 font-black text-lg shadow-xl shadow-neon-green/20 disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                <span x-show="!isProcessing" x-text="'COBRAR ' + formatMoney(cartTotal)"></span>
                <span x-show="isProcessing">PROCESANDO...</span>
            </button>

            <button type="button" @click="clearCart()" :disabled="cart.length === 0"
                    class="w-full py-2 text-xs font-semibold text-slate-400 hover:text-rose-400 transition-all text-center">
                Limpiar Carrito
            </button>
        </div>
    </div>

    <!-- Modal 1: Table Selector -->
    <div x-show="showTableModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" style="display: none;">
        <div class="bg-night-900 border border-night-800 rounded-3xl p-5 max-w-lg w-full shadow-2xl">
            <div class="flex items-center justify-between pb-3 border-b border-night-800 mb-4">
                <h3 class="text-base font-bold text-white">Seleccionar Mesa</h3>
                <button type="button" @click="showTableModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <div class="grid grid-cols-4 gap-2 mb-4">
                <button type="button" @click="selectedTable = null; showTableModal = false"
                        class="p-3 rounded-xl border border-night-750 text-center font-bold text-xs"
                        :class="selectedTable === null ? 'bg-neon-green text-night-950' : 'bg-night-850 text-white'">
                    Mostrador
                </button>
                @foreach($tables as $t)
                    <button type="button" @click="selectedTable = {{ json_encode($t) }}; showTableModal = false"
                            class="p-3 rounded-xl border text-center font-bold text-xs transition-all"
                            :class="{{ $t->status === 'occupied' ? 'true' : 'false' }} ? 'bg-amber-950/80 border-amber-600/50 text-amber-300' : 'bg-emerald-950/80 border-emerald-600/50 text-emerald-300'">
                        {{ $t->name }}
                        <span class="block text-[10px] font-normal uppercase">{{ $t->status === 'occupied' ? 'Ocupada' : 'Libre' }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Modal 2: Payment / Checkout Modal (Mixed Payments, Change Calculator) -->
    <div x-show="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" style="display: none;">
        <div class="bg-night-900 border border-night-800 rounded-3xl p-6 max-w-xl w-full shadow-2xl max-h-[90vh] flex flex-col">
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-3 border-b border-night-800">
                <div>
                    <h3 class="text-lg font-black text-white">Completar Cobro</h3>
                    <p class="text-xs text-slate-400">Total a pagar: <strong class="text-neon-green font-mono text-sm" x-text="formatMoney(cartTotal)"></strong></p>
                </div>
                <button type="button" @click="showPaymentModal = false" class="text-slate-400 hover:text-white text-xl">&times;</button>
            </div>

            <!-- Payment Methods Tabs -->
            <div class="flex gap-2 py-3 overflow-x-auto shrink-0">
                @foreach($paymentMethods as $pm)
                    <button type="button" @click="selectedMethodId = {{ $pm->id }}; isCashMethod = {{ $pm->is_cash ? 'true' : 'false' }}"
                            class="px-3.5 py-2.5 rounded-xl border text-xs font-bold whitespace-nowrap transition-all"
                            :class="selectedMethodId === {{ $pm->id }} ? 'bg-neon-green text-night-950 border-neon-green font-black shadow-lg shadow-neon-green/20' : 'bg-night-850 text-slate-300 border-night-750'">
                        {{ $pm->name }}
                    </button>
                @endforeach
            </div>

            <!-- Cash Quick Denominations (If Cash Selected) -->
            <template x-if="isCashMethod">
                <div class="space-y-3 mb-4">
                    <div class="flex items-center gap-2">
                        <button type="button" @click="setCashReceived(cartTotal)" class="flex-1 py-2 bg-night-800 border border-night-750 rounded-xl text-xs font-bold text-white hover:bg-night-750">
                            Efectivo Exacto
                        </button>
                        <button type="button" @click="setCashReceived(20000)" class="flex-1 py-2 bg-night-800 border border-night-750 rounded-xl text-xs font-bold text-white hover:bg-night-750">$ 20.000</button>
                        <button type="button" @click="setCashReceived(50000)" class="flex-1 py-2 bg-night-800 border border-night-750 rounded-xl text-xs font-bold text-white hover:bg-night-750">$ 50.000</button>
                        <button type="button" @click="setCashReceived(100000)" class="flex-1 py-2 bg-night-800 border border-night-750 rounded-xl text-xs font-bold text-white hover:bg-night-750">$ 100.000</button>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Efectivo Recibido</label>
                            <input type="number" x-model.number="cashReceived"
                                   class="w-full bg-night-850 border border-night-750 text-white font-mono text-lg font-bold rounded-xl px-3 py-2.5 focus:border-neon-green focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Cambio / Vueltas</label>
                            <div class="w-full bg-night-950 border border-night-800 text-neon-green font-mono text-lg font-black rounded-xl px-3 py-2.5 flex items-center justify-between">
                                <span x-text="formatMoney(cashChange)"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Electronic / Voucher Reference -->
            <template x-if="!isCashMethod">
                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Referencia / Comprobante (Opcional)</label>
                    <input type="text" x-model="paymentReference" placeholder="Ej: Aprobación #123456"
                           class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3 py-2.5 text-sm focus:border-neon-green focus:outline-none">
                </div>
            </template>

            <!-- Action Buttons -->
            <div class="pt-4 border-t border-night-800 flex gap-3">
                <button type="button" @click="showPaymentModal = false" class="btn-touch flex-1 bg-night-800 text-slate-300 hover:text-white font-bold text-sm">
                    Cancelar
                </button>
                <button type="button" @click="confirmPayment()"
                        :disabled="isProcessing || (isCashMethod && cashReceived < cartTotal)"
                        class="btn-touch flex-[2] bg-gradient-to-r from-neon-green to-emerald-400 hover:from-neon-green hover:to-emerald-300 text-night-950 font-black text-base shadow-xl shadow-neon-green/30 disabled:opacity-40">
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
