@extends('layouts.app', ['title' => 'Editar ' . $product->name])

@section('content')
<div class="max-w-3xl mx-auto py-2">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
            <h2 class="text-xl font-black text-white">Editar Producto</h2>
            <p class="text-xs text-slate-400 mt-0.5">{{ $product->name }} (SKU: {{ $product->sku }})</p>
        </div>
        <a href="{{ route('inventory.index') }}" class="btn-touch py-2 px-4 bg-night-800 hover:bg-night-750 text-slate-300 text-xs font-bold">
            &larr; Volver al Catálogo
        </a>
    </div>

    <form action="{{ route('inventory.update', $product->id) }}" method="POST" class="card-night p-6 space-y-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Nombre del Producto *</label>
                <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                       class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3.5 py-2.5 text-sm focus:border-neon-green focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Categoría *</label>
                <select name="category_id" required class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3.5 py-2.5 text-sm focus:border-neon-green focus:outline-none">
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ (old('category_id', $product->category_id) == $cat->id) ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-300 mb-1">SKU *</label>
                <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" required
                       class="w-full bg-night-850 border border-night-750 text-white font-mono rounded-xl px-3.5 py-2.5 text-sm focus:border-neon-green focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Código de Barras</label>
                <input type="text" name="barcode" value="{{ old('barcode', $product->barcode) }}"
                       class="w-full bg-night-850 border border-night-750 text-white font-mono rounded-xl px-3.5 py-2.5 text-sm focus:border-neon-green focus:outline-none">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2 border-t border-night-800">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Costo ($)</label>
                <input type="number" name="price_buy" value="{{ old('price_buy', $product->price_buy) }}" min="0" step="100" required
                       class="w-full bg-night-850 border border-night-750 text-white font-mono rounded-xl px-3 py-2 text-sm focus:border-neon-green focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Precio Venta ($)</label>
                <input type="number" name="price_sell" value="{{ old('price_sell', $product->price_sell) }}" min="0" step="100" required
                       class="w-full bg-night-850 border border-night-750 text-neon-green font-mono font-bold rounded-xl px-3 py-2 text-sm focus:border-neon-green focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Stock Mínimo</label>
                <input type="number" name="stock_min" value="{{ old('stock_min', $product->stock_min) }}" min="0" step="1" required
                       class="w-full bg-night-850 border border-night-750 text-white font-mono rounded-xl px-3 py-2 text-sm focus:border-neon-green focus:outline-none">
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4 pt-3 border-t border-night-800">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" {{ $product->is_active ? 'checked' : '' }} class="w-4 h-4 rounded text-neon-green">
                <span class="text-xs font-bold text-white">Producto Activo en Catálogo</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_favorite" value="1" {{ $product->is_favorite ? 'checked' : '' }} class="w-4 h-4 rounded text-neon-green">
                <span class="text-xs font-bold text-white">Favorito / Acceso Rápido</span>
            </label>
        </div>

        <div class="pt-4 border-t border-night-800 flex justify-end gap-3">
            <a href="{{ route('inventory.index') }}" class="btn-touch py-2.5 px-4 bg-night-800 text-slate-300 text-xs font-bold">Cancelar</a>
            <button type="submit" class="btn-touch py-2.5 px-6 bg-neon-green text-night-950 text-xs font-black">
                ACTUALIZAR PRODUCTO
            </button>
        </div>
    </form>
</div>
@endsection
