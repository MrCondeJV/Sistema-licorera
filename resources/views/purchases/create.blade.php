@extends('layouts.app', ['title' => 'Registrar Compra'])

@section('content')
<div class="max-w-4xl mx-auto py-2"
     x-data="{
         items: [{ product_id: '', quantity: 1, unit_cost: 0, conversion_factor: 1 }],
         products: @json($products),
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
     }">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
            <h2 class="text-xl font-black text-slate-900 dark:text-white">Registrar Entrada / Compra</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Ingreso de mercancía por proveedor</p>
        </div>
        <a href="{{ route('purchases.index') }}" class="btn-touch py-2 px-4 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl">
            &larr; Volver
        </a>
    </div>

    <form action="{{ route('purchases.store') }}" method="POST" class="card-night p-6 space-y-4">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Proveedor *</label>
                <select name="supplier_id" required class="input-night w-full text-sm">
                    <option value="">Selecciona proveedor...</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Número de Factura / Remisión</label>
                <input type="text" name="invoice_number" placeholder="Ej: FAC-98765"
                       class="input-night w-full text-sm font-mono">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Notas</label>
                <input type="text" name="notes" placeholder="Observaciones de compra..."
                       class="input-night w-full text-sm">
            </div>
        </div>

        <!-- Line items -->
        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 space-y-3">
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Detalle de Productos</h4>
                <button type="button" @click="addItem()" class="px-3 py-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-emerald-600 dark:text-emerald-400 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-bold transition-colors">
                    + Agregar Fila
                </button>
            </div>

            <template x-for="(item, idx) in items" :key="idx">
                <div class="p-3 bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                    <div class="sm:col-span-5">
                        <label class="block text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 mb-1">Producto *</label>
                        <select :name="'items[' + idx + '][product_id]'" x-model="item.product_id" @change="onProductChange(idx)" required
                                class="input-night w-full text-xs">
                            <option value="">Selecciona producto...</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} (Stock: {{ rtrim(rtrim($p->stock, '0'), '.') }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 mb-1">Cantidad *</label>
                        <input type="number" :name="'items[' + idx + '][quantity]'" x-model.number="item.quantity" min="1" step="1" required
                               class="input-night w-full font-mono text-xs">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 mb-1">Costo Unitario ($) *</label>
                        <input type="number" :name="'items[' + idx + '][unit_cost]'" x-model.number="item.unit_cost" min="0" step="100" required
                               class="input-night w-full font-mono text-xs">
                    </div>

                    <div class="sm:col-span-1 text-right">
                        <span class="block text-[10px] uppercase font-bold text-slate-500 dark:text-slate-400 mb-1">Subtotal</span>
                        <span class="font-mono text-xs font-bold text-emerald-600 dark:text-emerald-400" x-text="'$' + Math.round((item.quantity * item.unit_cost) || 0).toLocaleString('es-CO')"></span>
                    </div>

                    <div class="sm:col-span-1 text-right">
                        <button type="button" @click="removeItem(idx)" class="p-2 text-slate-400 hover:text-rose-600 font-bold">&times;</button>
                    </div>
                </div>
            </template>
        </div>

        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="receive_immediately" value="1" checked class="w-4 h-4 rounded text-emerald-600 focus:ring-0">
                <span class="text-xs font-bold text-slate-900 dark:text-white">Confirmar recepción e ingresar a stock inmediatamente</span>
            </label>

            <div class="text-right">
                <span class="text-xs font-bold uppercase text-slate-500 dark:text-slate-400 mr-2">Total Compra:</span>
                <span class="text-xl font-black font-mono text-slate-900 dark:text-white" x-text="'$' + Math.round(totalAmount).toLocaleString('es-CO')"></span>
            </div>
        </div>

        <div class="pt-2 flex justify-end gap-3">
            <a href="{{ route('purchases.index') }}" class="btn-touch py-2.5 px-4 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl">Cancelar</a>
            <button type="submit" class="btn-touch py-2.5 px-6 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-lg shadow-emerald-600/30 rounded-xl">
                GUARDAR COMPRA
            </button>
        </div>
    </form>
</div>
@endsection
