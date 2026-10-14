@extends('layouts.app', ['title' => 'Registrar Compra'])

@section('content')
<div class="max-w-5xl mx-auto py-3" x-data="purchaseForm()">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
        <div>
            <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Registrar Entrada / Compra</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Ingreso de mercancía por proveedor y actualización automática de inventario</p>
        </div>
        <a href="{{ route('purchases.index') }}" class="btn-touch py-2.5 px-4 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 text-sm font-bold rounded-xl border border-slate-200 dark:border-slate-700">
            &larr; Volver a Compras
        </a>
    </div>

    <form action="{{ route('purchases.store') }}" method="POST" class="card-night p-6 sm:p-7 space-y-6">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Proveedor *</label>
                    <button type="button" @click="showQuickSupplierModal = true" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                        <span>+ Nuevo Proveedor</span>
                    </button>
                </div>
                <select name="supplier_id" x-model="selectedSupplierId" required class="input-night w-full text-sm font-medium">
                    <option value="">Selecciona proveedor...</option>
                    <template x-for="s in suppliersList" :key="s.id">
                        <option :value="s.id" x-text="s.name"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">Número de Factura / Remisión</label>
                <input type="text" name="invoice_number" placeholder="Ej: FAC-98765"
                       class="input-night w-full text-sm font-mono">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">Notas u Observaciones</label>
                <input type="text" name="notes" placeholder="Observaciones de compra..."
                       class="input-night w-full text-sm">
            </div>
        </div>

        <!-- Line items -->
        <div class="pt-5 border-t border-slate-200 dark:border-slate-800 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Detalle de Productos</h4>
                    <p class="text-xs text-slate-400 dark:text-slate-500">Agrega las líneas de producto recibidas en esta factura</p>
                </div>
                <button type="button" @click="addItem()" class="px-4 py-2 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/60 dark:hover:bg-emerald-900/60 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700 rounded-xl text-xs font-bold transition-all shadow-sm flex items-center gap-1.5 active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    <span>Agregar Fila</span>
                </button>
            </div>

            <div class="space-y-3">
                <template x-for="(item, idx) in items" :key="idx">
                    <div class="p-4 bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-2xl grid grid-cols-1 sm:grid-cols-12 gap-3 items-end shadow-sm">
                        <div class="sm:col-span-5">
                            <label class="block text-xs uppercase font-bold text-slate-500 dark:text-slate-400 mb-1.5">Producto *</label>
                            <select :name="'items[' + idx + '][product_id]'" x-model="item.product_id" @change="onProductChange(idx)" required
                                    class="input-night w-full text-sm font-medium">
                                <option value="">Selecciona producto...</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} (Stock: {{ rtrim(rtrim($p->stock, '0'), '.') }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs uppercase font-bold text-slate-500 dark:text-slate-400 mb-1.5">Cantidad *</label>
                            <input type="number" :name="'items[' + idx + '][quantity]'" x-model.number="item.quantity" min="1" step="1" required
                                   class="input-night w-full font-mono text-sm font-bold text-slate-900 dark:text-white">
                        </div>

                        <div class="sm:col-span-3">
                            <label class="block text-xs uppercase font-bold text-slate-500 dark:text-slate-400 mb-1.5">Costo Unitario ($) *</label>
                            <input type="number" :name="'items[' + idx + '][unit_cost]'" x-model.number="item.unit_cost" min="0" step="100" required
                                   class="input-night w-full font-mono text-sm font-bold text-slate-900 dark:text-white">
                        </div>

                        <div class="sm:col-span-1 text-right">
                            <span class="block text-xs uppercase font-bold text-slate-400 dark:text-slate-500 mb-1">Subtotal</span>
                            <span class="font-mono text-sm font-black text-emerald-600 dark:text-emerald-400 block pb-2.5" x-text="'$' + Math.round((item.quantity * item.unit_cost) || 0).toLocaleString('es-CO')"></span>
                        </div>

                        <div class="sm:col-span-1 text-right pb-1">
                            <button type="button" @click="removeItem(idx)" class="p-2 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 font-bold rounded-lg hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors" title="Eliminar fila">
                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="pt-5 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <label class="flex items-center gap-3 cursor-pointer select-none">
                <input type="checkbox" name="receive_immediately" value="1" checked class="w-5 h-5 rounded text-emerald-600 focus:ring-0 cursor-pointer">
                <span class="text-sm font-bold text-slate-900 dark:text-white">Confirmar recepción e ingresar a stock inmediatamente</span>
            </label>

            <div class="text-right">
                <span class="text-sm font-bold uppercase text-slate-500 dark:text-slate-400 mr-2">Total Compra:</span>
                <span class="text-2xl font-black font-mono text-slate-900 dark:text-white" x-text="'$' + Math.round(totalAmount).toLocaleString('es-CO')"></span>
            </div>
        </div>

        <div class="pt-3 flex justify-end gap-3">
            <a href="{{ route('purchases.index') }}" class="btn-touch py-3 px-5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-sm font-bold rounded-xl">Cancelar</a>
            <button type="submit" class="btn-touch py-3 px-7 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-black shadow-lg shadow-emerald-600/30 rounded-xl">
                GUARDAR COMPRA
            </button>
        </div>
    </form>

    <!-- Modal: Quick Add Supplier -->
    <div x-show="showQuickSupplierModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" style="display: none;">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-black text-slate-900 dark:text-white">Nuevo Proveedor Rápido</h3>
                <button type="button" @click="showQuickSupplierModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Se guardará y seleccionará automáticamente en esta compra</p>

            <template x-if="quickSupplierError">
                <div class="mb-3 p-2.5 bg-rose-50 border border-rose-200 dark:bg-rose-950/70 dark:border-rose-700 rounded-xl text-rose-600 dark:text-rose-300 text-xs font-semibold" x-text="quickSupplierError"></div>
            </template>

            <form @submit.prevent="createQuickSupplier()" class="space-y-3">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Nombre Comercial *</label>
                    <input type="text" x-model="quickSupplier.name" required placeholder="Ej: Bavaria S.A." autofocus
                           class="input-night w-full text-xs font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">NIT / Cédula</label>
                    <input type="text" x-model="quickSupplier.tax_id" placeholder="Ej: 900.123.456-7"
                           class="input-night w-full text-xs font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Teléfono / Celular</label>
                    <input type="text" x-model="quickSupplier.phone" placeholder="Ej: 3101234567"
                           class="input-night w-full text-xs font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Persona de Contacto</label>
                    <input type="text" x-model="quickSupplier.contact_person" placeholder="Ej: Asesor Bavaria"
                           class="input-night w-full text-xs">
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="button" @click="showQuickSupplierModal = false" class="btn-touch flex-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl">Cancelar</button>
                    <button type="submit" :disabled="quickSupplierLoading" class="btn-touch flex-1 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-lg shadow-emerald-600/30 rounded-xl flex items-center justify-center gap-1">
                        <span x-text="quickSupplierLoading ? 'Guardando...' : 'GUARDAR'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function purchaseForm() {
    return {
        items: [{ product_id: '', quantity: 1, unit_cost: 0, conversion_factor: 1 }],
        products: @json($products),
        suppliersList: @json($suppliers),
        selectedSupplierId: '{{ old('supplier_id', '') }}',
        showQuickSupplierModal: false,
        quickSupplier: { name: '', tax_id: '', phone: '', contact_person: '' },
        quickSupplierError: '',
        quickSupplierLoading: false,
        async createQuickSupplier() {
            if (!this.quickSupplier.name.trim()) {
                this.quickSupplierError = 'El nombre del proveedor es obligatorio.';
                return;
            }
            this.quickSupplierLoading = true;
            this.quickSupplierError = '';
            try {
                const res = await fetch('{{ route('suppliers.quick') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(this.quickSupplier)
                });
                const data = await res.json();
                if (data.success && data.supplier) {
                    this.suppliersList.push(data.supplier);
                    this.selectedSupplierId = data.supplier.id;
                    this.quickSupplier = { name: '', tax_id: '', phone: '', contact_person: '' };
                    this.showQuickSupplierModal = false;
                } else {
                    this.quickSupplierError = data.message || 'Error al guardar el proveedor.';
                }
            } catch (err) {
                this.quickSupplierError = 'Error de conexión con el servidor.';
            } finally {
                this.quickSupplierLoading = false;
            }
        },
        addItem() {
            this.items.push({ product_id: '', quantity: 1, unit_cost: 0, conversion_factor: 1 });
        },
        removeItem(idx) {
            if (this.items.length > 1) {
                this.items.splice(idx, 1);
            }
        },
        onProductChange(idx) {
            const p = this.products.find(prod => prod.id == this.items[idx].product_id);
            if (p) {
                this.items[idx].unit_cost = p.price_buy;
                this.items[idx].conversion_factor = 1;
            }
        },
        get totalAmount() {
            return this.items.reduce((sum, i) => sum + ((parseFloat(i.quantity) || 0) * (parseFloat(i.unit_cost) || 0)), 0);
        }
    };
}
</script>
@endpush
