@extends('layouts.app', ['title' => 'Nuevo Producto'])

@section('content')
<div class="max-w-3xl mx-auto py-2" x-data="{ isBeer: false, isLiquor: false }">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
            <h2 class="text-xl font-black text-slate-900 dark:text-white">Crear Nuevo Producto</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Configuración de inventario base y presentaciones de venta</p>
        </div>
        <a href="{{ route('inventory.index') }}" class="btn-touch py-2 px-4 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl">
            &larr; Volver al Catálogo
        </a>
    </div>

    <form action="{{ route('inventory.store') }}" method="POST" enctype="multipart/form-data" class="card-night p-6 space-y-4">
        @csrf

        <!-- General Info -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">
                    Nombre del Producto <span class="text-rose-500 font-bold">* Obligatorio</span>
                </label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="Ej: Cerveza Águila 330ml"
                       class="input-night w-full text-sm @error('name') border-rose-500 ring-2 ring-rose-500/20 @enderror">
                @error('name')
                    <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">
                    Categoría <span class="text-rose-500 font-bold">* Obligatorio</span>
                </label>
                <select name="category_id" required class="input-night w-full text-sm @error('category_id') border-rose-500 ring-2 ring-rose-500/20 @enderror">
                    <option value="">-- Seleccionar Categoría --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
                @error('category_id')
                    <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">
                    SKU / Referencia <span class="text-rose-500 font-bold">* Obligatorio</span>
                </label>
                <input type="text" name="sku" value="{{ old('sku', 'PROD-' . strtoupper(Str::random(6))) }}" required
                       class="input-night w-full text-sm font-mono @error('sku') border-rose-500 ring-2 ring-rose-500/20 @enderror">
                @error('sku')
                    <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">
                    Código de Barras <span class="text-slate-400 font-normal">(Opcional)</span>
                </label>
                <input type="text" name="barcode" value="{{ old('barcode') }}" placeholder="Ej: 770123456789"
                       class="input-night w-full text-sm font-mono">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">
                    Unidad Base <span class="text-rose-500 font-bold">* Obligatorio</span>
                </label>
                <select name="base_unit" required class="input-night w-full text-sm @error('base_unit') border-rose-500 ring-2 ring-rose-500/20 @enderror">
                    <option value="unidad" {{ old('base_unit') == 'unidad' ? 'selected' : '' }}>Unidad</option>
                    <option value="botella" {{ old('base_unit') == 'botella' ? 'selected' : '' }}>Botella</option>
                    <option value="lata" {{ old('base_unit') == 'lata' ? 'selected' : '' }}>Lata</option>
                </select>
                @error('base_unit')
                    <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Financial & Stock -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-2 border-t border-slate-200 dark:border-slate-800">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">
                    Costo Unitario ($) <span class="text-rose-500 font-bold">*</span>
                </label>
                <input type="number" name="price_buy" value="{{ old('price_buy', 0) }}" min="0" step="50" required
                       class="input-night w-full font-mono text-sm @error('price_buy') border-rose-500 ring-2 ring-rose-500/20 @enderror">
                @error('price_buy')
                    <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">
                    Precio Venta ($) <span class="text-rose-500 font-bold">*</span>
                </label>
                <input type="number" name="price_sell" value="{{ old('price_sell', 0) }}" min="0" step="50" required
                       class="input-night w-full text-emerald-600 dark:text-emerald-400 font-mono font-bold text-sm @error('price_sell') border-rose-500 ring-2 ring-rose-500/20 @enderror">
                @error('price_sell')
                    <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">
                    Stock Inicial <span class="text-rose-500 font-bold">*</span>
                </label>
                <input type="number" name="stock" value="{{ old('stock', 0) }}" min="0" step="1" required
                       class="input-night w-full font-mono text-sm @error('stock') border-rose-500 ring-2 ring-rose-500/20 @enderror">
                @error('stock')
                    <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">
                    Stock Mínimo <span class="text-rose-500 font-bold">*</span>
                </label>
                <input type="number" name="stock_min" value="{{ old('stock_min', 5) }}" min="0" step="1" required
                       class="input-night w-full font-mono text-sm @error('stock_min') border-rose-500 ring-2 ring-rose-500/20 @enderror">
                @error('stock_min')
                    <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Image Upload Field with Preview -->
        <div class="pt-3 border-t border-slate-200 dark:border-slate-800" x-data="{ imagePreview: null }">
            <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1.5">
                Foto del Producto <span class="text-slate-400 font-normal">(Opcional)</span>
            </label>
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 flex items-center justify-center overflow-hidden shrink-0 shadow-inner">
                    <template x-if="imagePreview">
                        <img :src="imagePreview" class="w-full h-full object-cover">
                    </template>
                    <template x-if="!imagePreview">
                        <span class="text-2xl">📸</span>
                    </template>
                </div>
                <div class="flex-1">
                    <input type="file" name="image" accept="image/*"
                           @change="const file = $event.target.files[0]; if (file) { const reader = new FileReader(); reader.onload = (e) => imagePreview = e.target.result; reader.readAsDataURL(file); }"
                           class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-950 dark:file:text-emerald-400 cursor-pointer">
                    <p class="text-[11px] text-slate-400 mt-1">Formatos admitidos: JPG, PNG, WEBP. Máx: 5MB.</p>
                </div>
            </div>
        </div>

        <!-- Commercial Liquor / Beer Rules Checkboxes -->
        <div class="pt-3 border-t border-slate-200 dark:border-slate-800 space-y-3">
            <h4 class="text-xs font-bold uppercase tracking-wider text-cyan-600 dark:text-cyan-400">Reglas Comerciales de Licorera (Opcional)</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 rounded-xl cursor-pointer hover:border-emerald-500 transition-colors">
                    <input type="checkbox" name="is_beer" value="1" x-model="isBeer" class="w-4 h-4 rounded text-emerald-600 focus:ring-0">
                    <div>
                        <span class="text-xs font-bold text-slate-900 dark:text-white block">Es Cerveza (Múltiples Presentaciones)</span>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400">Habilita venta individual, six-pack o caja canasta</span>
                    </div>
                </label>

                <label class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 rounded-xl cursor-pointer hover:border-emerald-500 transition-colors">
                    <input type="checkbox" name="is_liquor" value="1" x-model="isLiquor" class="w-4 h-4 rounded text-emerald-600 focus:ring-0">
                    <div>
                        <span class="text-xs font-bold text-slate-900 dark:text-white block">Es Licor (Botella Completa Únicamente)</span>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400">Prohíbe fraccionamiento, venta estricta por botella</span>
                    </div>
                </label>
            </div>
        </div>

        <!-- Beer Presentations Configuration (If isBeer is checked) -->
        <div x-show="isBeer" x-cloak class="p-4 bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800 rounded-2xl space-y-3">
            <div>
                <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Presentaciones de Cerveza (Opcional)</h4>
                <p class="text-[11px] text-slate-400">Ingresa el precio de venta si deseas habilitar la venta por Six-Pack o Caja. Si no vendes paquetes, deja el campo vacío.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Six pack -->
                <div class="p-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl space-y-2 shadow-sm">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Six-Pack (6 Unidades)</span>
                    <input type="hidden" name="presentations[0][name]" value="Six-Pack (6 Uds)" :disabled="!isBeer">
                    <input type="hidden" name="presentations[0][conversion_factor]" value="6" :disabled="!isBeer">
                    <div>
                        <label class="text-[10px] text-slate-500 dark:text-slate-400 block uppercase font-medium">Precio de Venta Six-Pack ($)</label>
                        <input type="number" name="presentations[0][sale_price]" :disabled="!isBeer" placeholder="Ej: 24000 (Opcional)"
                               class="input-night w-full text-emerald-600 dark:text-emerald-400 font-mono text-xs">
                    </div>
                </div>

                <!-- Crate / Caja 24 -->
                <div class="p-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl space-y-2 shadow-sm">
                    <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Caja / Canasta (24 Unidades)</span>
                    <input type="hidden" name="presentations[1][name]" value="Caja (24 Uds)" :disabled="!isBeer">
                    <input type="hidden" name="presentations[1][conversion_factor]" value="24" :disabled="!isBeer">
                    <div>
                        <label class="text-[10px] text-slate-500 dark:text-slate-400 block uppercase font-medium">Precio de Venta Caja ($)</label>
                        <input type="number" name="presentations[1][sale_price]" :disabled="!isBeer" placeholder="Ej: 90000 (Opcional)"
                               class="input-night w-full text-emerald-600 dark:text-emerald-400 font-mono text-xs">
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3">
            <a href="{{ route('inventory.index') }}" class="btn-touch py-2.5 px-4 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl">Cancelar</a>
            <button type="submit" class="btn-touch py-2.5 px-6 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-lg shadow-emerald-600/30 rounded-xl">
                GUARDAR PRODUCTO
            </button>
        </div>
    </form>
</div>
@endsection
