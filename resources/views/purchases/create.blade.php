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

    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-xl font-black text-white">Registrar Entrada / Compra</h2>
            <p class="text-xs text-slate-400 mt-0.5">Ingreso de mercancía por proveedor</p>
        </div>
        <a href="{{ route('purchases.index') }}" class="btn-touch py-2 px-4 bg-night-800 text-slate-300 text-xs font-bold">
            &larr; Volver
        </a>
    </div>

    <form action="{{ route('purchases.store') }}" method="POST" class="card-night p-6 space-y-4">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Proveedor *</label>
                <select name="supplier_id" required class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3.5 py-2.5 text-sm focus:border-neon-green focus:outline-none">
                    <option value="">Selecciona proveedor...</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Número de Factura / Remisión</label>
                <input type="text" name="invoice_number" placeholder="Ej: FAC-98765"
                       class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3.5 py-2.5 text-sm font-mono focus:border-neon-green focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Notas</label>
                <input type="text" name="notes" placeholder="Observaciones de compra..."
                       class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3.5 py-2.5 text-sm focus:border-neon-green focus:outline-none">
            </div>
        </div>

        <!-- Line items -->
        <div class="pt-3 border-t border-night-800 space-y-3">
            <div class="flex items-center justify-between">
                <h4 class="text-xs font-bold uppercase tracking-wider text-neon-green">Detalle de Productos</h4>
                <button type="button" @click="addItem()" class="px-3 py-1 bg-night-800 hover:bg-night-750 text-neon-green border border-night-750 rounded-lg text-xs font-bold">
                    + Agregar Fila
                </button>
            </div>

            <template x-for="(item, idx) in items" :key="idx">
                <div class="p-3 bg-night-850 border border-night-750 rounded-xl grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                    <div class="sm:col-span-5">
                        <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Producto *</label>
                        <select :name="'items[' + idx + '][product_id]'" x-model="item.product_id" @change="onProductChange(idx)" required
                                class="w-full bg-night-900 border border-night-750 text-white rounded-lg px-2.5 py-2 text-xs focus:border-neon-green focus:outline-none">
                            <option value="">Selecciona producto...</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} (Stock: {{ rtrim(rtrim($p->stock, '0'), '.') }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Cantidad *</label>
                        <input type="number" :name="'items[' + idx + '][quantity]'" x-model.number="item.quantity" min="1" step="1" required
                               class="w-full bg-night-900 border border-night-750 text-white font-mono rounded-lg px-2.5 py-2 text-xs focus:border-neon-green focus:outline-none">
                    </div>

                    <div class="sm:col-span-3">
                        <label class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Costo Unitario ($) *</label>
                        <input type="number" :name="'items[' + idx + '][unit_cost]'" x-model.number="item.unit_cost" min="0" step="100" required
                               class="w-full bg-night-900 border border-night-750 text-white font-mono rounded-lg px-2.5 py-2 text-xs focus:border-neon-green focus:outline-none">
                    </div>

                    <div class="sm:col-span-1 text-right">
                        <span class="block text-[10px] uppercase font-bold text-slate-400 mb-1">Subtotal</span>
                        <span class="font-mono text-xs font-bold text-neon-green" x-text="'$' + Math.round((item.quantity * item.unit_cost) || 0).toLocaleString('es-CO')"></span>
                    </div>

                    <div class="sm:col-span-1 text-right">
                        <button type="button" @click="removeItem(idx)" class="p-2 text-slate-500 hover:text-rose-400">&times;</button>
                    </div>
                </div>
            </template>
        </div>

        <div class="pt-4 border-t border-night-800 flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="receive_immediately" value="1" checked class="w-4 h-4 rounded text-neon-green">
                <span class="text-xs font-bold text-white">Confirmar recepción e ingresar a stock inmediatamente</span>
            </label>

            <div class="text-right">
                <span class="text-xs font-bold uppercase text-slate-400 mr-2">Total Compra:</span>
                <span class="text-xl font-black font-mono text-white" x-text="'$' + Math.round(totalAmount).toLocaleString('es-CO')"></span>
            </div>
        </div>

        <div class="pt-2 flex justify-end gap-3">
            <a href="{{ route('purchases.index') }}" class="btn-touch py-2.5 px-4 bg-night-800 text-slate-300 text-xs font-bold">Cancelar</a>
            <button type="submit" class="btn-touch py-2.5 px-6 bg-gradient-to-r from-neon-green to-emerald-400 text-night-950 text-xs font-black shadow-lg shadow-neon-green/20">
                GUARDAR COMPRA
            </button>
        </div>
    </form>
</div>
@endsection
