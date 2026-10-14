@extends('layouts.app', ['title' => 'Inventario'])

@section('content')
<div class="h-full flex flex-col space-y-4" x-data="{ showAdjustModal: false, adjustProduct: null, newStock: 0, adjustReason: '' }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-black text-slate-900 dark:text-white">Inventario de Bebidas y Productos</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Control de unidades base, presentaciones de cervezas y botellas de licores</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('inventory.kardex') }}" class="btn-touch py-2 px-3 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-750 text-xs font-bold rounded-xl">
                📜 Kardex
            </a>
            <a href="{{ route('inventory.counts') }}" class="btn-touch py-2 px-3 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-750 text-xs font-bold rounded-xl">
                📋 Conteo Físico
            </a>
            @if(auth()->user()->is_superadmin || auth()->user()->hasRoleInTenant($currentTenant->id ?? 0, 'admin'))
                <a href="{{ route('inventory.create') }}" class="btn-touch py-2 px-4 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-lg shadow-emerald-600/30 rounded-xl">
                    + NUEVO PRODUCTO
                </a>
            @endif
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="card-night p-3 flex flex-wrap items-center gap-2">
        <form method="GET" action="{{ route('inventory.index') }}" class="flex-1 flex flex-wrap items-center gap-2 w-full">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre, SKU o código..."
                   class="bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-white rounded-xl px-3 py-2 text-xs flex-1 min-w-[200px] focus:outline-none focus:border-emerald-500">

            <select name="category_id" class="bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-white rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-emerald-500">
                <option value="">Todas las categorías</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>

            <select name="filter" class="bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-white rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-emerald-500">
                <option value="">Todos los estados</option>
                <option value="low_stock" {{ request('filter') == 'low_stock' ? 'selected' : '' }}>Stock Bajo</option>
                <option value="out_of_stock" {{ request('filter') == 'out_of_stock' ? 'selected' : '' }}>Agotados</option>
            </select>

            <button type="submit" class="px-4 py-2 bg-slate-800 dark:bg-slate-700 hover:bg-slate-700 dark:hover:bg-slate-600 text-white font-bold text-xs rounded-xl shadow-sm">
                Filtrar
            </button>
            @if(request()->hasAny(['search', 'category_id', 'filter']))
                <a href="{{ route('inventory.index') }}" class="px-3 py-2 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white text-xs">Limpiar</a>
            @endif
        </form>
    </div>

    <!-- Products Table -->
    <div class="card-night p-4 flex-1 overflow-hidden flex flex-col">
        <div class="flex-1 overflow-y-auto">
            <div class="overflow-x-auto min-w-full">
                <table class="w-full text-left text-sm min-w-[700px]">
                <thead class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-3">Producto / Categoría</th>
                        <th class="py-3.5 px-3">SKU</th>
                        <th class="py-3.5 px-3 text-right">Costo Unit.</th>
                        <th class="py-3.5 px-3 text-right">Precio Venta</th>
                        <th class="py-3.5 px-3 text-center">Stock Base</th>
                        <th class="py-3.5 px-3">Presentaciones</th>
                        <th class="py-3.5 px-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($products as $prod)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors {{ $prod->stock <= 0 ? 'bg-rose-50/50 dark:bg-rose-950/10' : ($prod->isLowStock() ? 'bg-amber-50/50 dark:bg-amber-950/10' : '') }}">
                            <td class="py-3 px-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-11 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center overflow-hidden shrink-0 shadow-sm">
                                        @if($prod->image_url)
                                            <img src="{{ $prod->image_url }}" alt="{{ $prod->name }}" class="w-full h-full object-cover">
                                        @else
                                            <span class="text-xl">🍾</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <span class="font-bold text-slate-900 dark:text-white text-sm block leading-snug">{{ $prod->name }}</span>
                                        <span class="text-slate-500 dark:text-slate-400 text-xs">{{ $prod->category->name ?? 'Sin categoría' }}</span>
                                        @if($prod->is_liquor)
                                            <span class="inline-block text-[10px] text-amber-600 dark:text-amber-400 font-semibold mt-0.5">🍾 Botella completa</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 px-2 font-mono text-slate-600 dark:text-slate-300">{{ $prod->sku }}</td>
                            <td class="py-3 px-2 text-right font-mono text-slate-600 dark:text-slate-300">${{ number_format($prod->price_buy, 0, ',', '.') }}</td>
                            <td class="py-3 px-2 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">${{ number_format($prod->price_sell, 0, ',', '.') }}</td>
                            <td class="py-3 px-2 text-center">
                                <span class="px-2 py-0.5 rounded-full font-mono font-bold text-xs {{ $prod->stock <= 0 ? 'bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-700' : ($prod->isLowStock() ? 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-700' : 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700') }}">
                                    {{ rtrim(rtrim($prod->stock, '0'), '.') }} {{ $prod->base_unit }}
                                </span>
                            </td>
                            <td class="py-3 px-2">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($prod->presentations as $pres)
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 text-[10px] font-mono">
                                            {{ $pres->name }}: ${{ number_format($pres->sale_price, 0, ',', '.') }}
                                        </span>
                                    @empty
                                        <span class="text-slate-400 text-[11px]">-</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="py-3 px-2 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Quick Adjust Modal trigger -->
                                    <button type="button"
                                            @click="adjustProduct = {{ json_encode($prod) }}; newStock = {{ $prod->stock }}; showAdjustModal = true"
                                            class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-cyan-600 dark:text-cyan-400 rounded-lg text-xs font-bold border border-slate-200 dark:border-slate-700 transition-colors"
                                            title="Ajuste rápido de stock">
                                        +/- Stock
                                    </button>

                                    @if(auth()->user()->is_superadmin || auth()->user()->hasRoleInTenant($currentTenant->id ?? 0, 'admin'))
                                        <a href="{{ route('inventory.edit', $prod->id) }}"
                                           class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-bold border border-slate-200 dark:border-slate-700 transition-colors">
                                            Editar
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 dark:text-slate-500">No se encontraron productos con los filtros seleccionados.</td>
                        </tr>
                    @endforelse
                </tbody>
                </table>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-200 dark:border-slate-800">
            {{ $products->links() }}
        </div>
    </div>

    <!-- Quick Stock Adjustment Modal -->
    <div x-show="showAdjustModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" style="display: none;">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-black text-slate-900 dark:text-white mb-1">Ajuste de Stock</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4" x-text="'Producto: ' + (adjustProduct ? adjustProduct.name : '')"></p>

            <form :action="'/inventory/' + (adjustProduct ? adjustProduct.id : 0) + '/adjust'" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Nuevo Stock Físico *</label>
                    <input type="number" name="new_stock" x-model.number="newStock" min="0" step="1" required autofocus
                           class="input-night w-full font-mono font-black text-xl">
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Motivo del Ajuste *</label>
                    <input type="text" name="reason" x-model="adjustReason" required placeholder="Ej: Rotura, Vencimiento, Corrección..."
                           class="input-night w-full text-xs">
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="showAdjustModal = false" class="btn-touch flex-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl">Cancelar</button>
                    <button type="submit" class="btn-touch flex-1 bg-cyan-600 hover:bg-cyan-500 text-white text-xs font-black shadow-lg shadow-cyan-600/30 rounded-xl">GUARDAR AJUSTE</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
