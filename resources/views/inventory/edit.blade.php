@extends('layouts.app', ['title' => 'Editar ' . $product->name])

@section('content')
<div class="max-w-3xl mx-auto py-2">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
            <h2 class="text-xl font-black text-slate-900 dark:text-white">Editar Producto</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $product->name }} (SKU: {{ $product->sku }})</p>
        </div>
        <a href="{{ route('inventory.index') }}" class="btn-touch py-2 px-4 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl">
            &larr; Volver al Catálogo
        </a>
    </div>

    <form action="{{ route('inventory.update', $product->id) }}" method="POST" enctype="multipart/form-data" class="card-night p-6 space-y-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">
                    Nombre del Producto <span class="text-rose-500 font-bold">* Obligatorio</span>
                </label>
                <input type="text" name="name" value="{{ old('name', $product->name) }}" required
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
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ (old('category_id', $product->category_id) == $cat->id) ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
                @error('category_id')
                    <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">
                    SKU / Referencia <span class="text-rose-500 font-bold">* Obligatorio</span>
                </label>
                <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" required
                       class="input-night w-full text-sm font-mono @error('sku') border-rose-500 ring-2 ring-rose-500/20 @enderror">
                @error('sku')
                    <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">
                    Código de Barras <span class="text-slate-400 font-normal">(Opcional)</span>
                </label>
                <input type="text" name="barcode" value="{{ old('barcode', $product->barcode) }}"
                       class="input-night w-full text-sm font-mono">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 border-t border-slate-200 dark:border-slate-800">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">
                    Costo ($) <span class="text-rose-500 font-bold">* Obligatorio</span>
                </label>
                <input type="number" name="price_buy" value="{{ old('price_buy', $product->price_buy) }}" min="0" step="50" required
                       class="input-night w-full font-mono text-sm @error('price_buy') border-rose-500 ring-2 ring-rose-500/20 @enderror">
                @error('price_buy')
                    <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">
                    Precio Venta ($) <span class="text-rose-500 font-bold">* Obligatorio</span>
                </label>
                <input type="number" name="price_sell" value="{{ old('price_sell', $product->price_sell) }}" min="0" step="50" required
                       class="input-night w-full text-emerald-600 dark:text-emerald-400 font-mono font-bold text-sm @error('price_sell') border-rose-500 ring-2 ring-rose-500/20 @enderror">
                @error('price_sell')
                    <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">
                    Stock Mínimo <span class="text-rose-500 font-bold">* Obligatorio</span>
                </label>
                <input type="number" name="stock_min" value="{{ old('stock_min', $product->stock_min) }}" min="0" step="1" required
                       class="input-night w-full font-mono text-sm @error('stock_min') border-rose-500 ring-2 ring-rose-500/20 @enderror">
                @error('stock_min')
                    <p class="text-rose-500 text-xs mt-1 font-semibold">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Image Upload Field with Preview -->
        <div class="pt-3 border-t border-slate-200 dark:border-slate-800" x-data="{ imagePreview: '{{ $product->image_url }}' }">
            <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1.5">Foto del Producto</label>
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 border border-slate-300 dark:border-slate-700 flex items-center justify-center overflow-hidden shrink-0 shadow-inner">
                    <template x-if="imagePreview">
                        <img :src="imagePreview" class="w-full h-full object-cover">
                    </template>
                    <template x-if="!imagePreview">
                        <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </template>
                </div>
                <div class="flex-1">
                    <input type="file" name="image" accept="image/*"
                           @change="const file = $event.target.files[0]; if (file) { const reader = new FileReader(); reader.onload = (e) => imagePreview = e.target.result; reader.readAsDataURL(file); }"
                           class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 dark:file:bg-emerald-950 dark:file:text-emerald-400 cursor-pointer">
                    <p class="text-[11px] text-slate-400 mt-1">Selecciona una imagen para reemplazar la actual. JPG, PNG, WEBP. Máx: 5MB.</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4 pt-3 border-t border-slate-200 dark:border-slate-800">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" {{ $product->is_active ? 'checked' : '' }} class="w-4 h-4 rounded text-emerald-600 focus:ring-0">
                <span class="text-xs font-bold text-slate-900 dark:text-white">Producto Activo en Catálogo</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_favorite" value="1" {{ $product->is_favorite ? 'checked' : '' }} class="w-4 h-4 rounded text-emerald-600 focus:ring-0">
                <span class="text-xs font-bold text-slate-900 dark:text-white">Favorito / Acceso Rápido</span>
            </label>
        </div>

        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-3">
            <a href="{{ route('inventory.index') }}" class="btn-touch py-2.5 px-4 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-semibold rounded-xl">Cancelar</a>
            <button type="submit" class="btn-touch py-2.5 px-6 bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:hover:bg-slate-100 dark:text-slate-900 text-xs font-semibold shadow-xs rounded-xl transition-colors">
                ACTUALIZAR PRODUCTO
            </button>
        </div>
    </form>
</div>
@endsection
