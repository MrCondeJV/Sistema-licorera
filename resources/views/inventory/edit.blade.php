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

    <form action="{{ route('inventory.update', $product->id) }}" method="POST" class="card-night p-6 space-y-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Nombre del Producto *</label>
                <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                       class="input-night w-full text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Categoría *</label>
                <select name="category_id" required class="input-night w-full text-sm">
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ (old('category_id', $product->category_id) == $cat->id) ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">SKU *</label>
                <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" required
                       class="input-night w-full text-sm font-mono">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Código de Barras</label>
                <input type="text" name="barcode" value="{{ old('barcode', $product->barcode) }}"
                       class="input-night w-full text-sm font-mono">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 border-t border-slate-200 dark:border-slate-800">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Costo ($)</label>
                <input type="number" name="price_buy" value="{{ old('price_buy', $product->price_buy) }}" min="0" step="100" required
                       class="input-night w-full font-mono text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Precio Venta ($)</label>
                <input type="number" name="price_sell" value="{{ old('price_sell', $product->price_sell) }}" min="0" step="100" required
                       class="input-night w-full text-emerald-600 dark:text-emerald-400 font-mono font-bold text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Stock Mínimo</label>
                <input type="number" name="stock_min" value="{{ old('stock_min', $product->stock_min) }}" min="0" step="1" required
                       class="input-night w-full font-mono text-sm">
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
            <a href="{{ route('inventory.index') }}" class="btn-touch py-2.5 px-4 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl">Cancelar</a>
            <button type="submit" class="btn-touch py-2.5 px-6 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-lg shadow-emerald-600/30 rounded-xl">
                ACTUALIZAR PRODUCTO
            </button>
        </div>
    </form>
</div>
@endsection
