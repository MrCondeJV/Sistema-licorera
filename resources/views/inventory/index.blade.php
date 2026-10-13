@extends('layouts.app', ['title' => 'Inventario'])

@section('content')
<div class="h-full flex flex-col space-y-4" x-data="{ showAdjustModal: false, adjustProduct: null, newStock: 0, adjustReason: '' }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-black text-white">Inventario de Bebidas y Productos</h2>
            <p class="text-xs text-slate-400 mt-0.5">Control de unidades base, presentaciones de cervezas y botellas de licores</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('inventory.kardex') }}" class="btn-touch py-2 px-3 bg-night-800 hover:bg-night-750 text-slate-300 border border-night-750 text-xs font-bold">
                📜 Kardex
            </a>
            <a href="{{ route('inventory.counts') }}" class="btn-touch py-2 px-3 bg-night-800 hover:bg-night-750 text-slate-300 border border-night-750 text-xs font-bold">
                📋 Conteo Físico
            </a>
            @if(auth()->user()->is_superadmin || auth()->user()->hasRoleInTenant($currentTenant->id ?? 0, 'admin'))
                <a href="{{ route('inventory.create') }}" class="btn-touch py-2 px-4 bg-gradient-to-r from-neon-green to-emerald-400 text-night-950 text-xs font-black shadow-lg shadow-neon-green/20">
                    + NUEVO PRODUCTO
                </a>
            @endif
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="card-night p-3 flex flex-wrap items-center gap-2">
        <form method="GET" action="{{ route('inventory.index') }}" class="flex-1 flex flex-wrap items-center gap-2 w-full">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre, SKU o código..."
                   class="bg-night-850 border border-night-750 text-white rounded-xl px-3 py-2 text-xs flex-1 min-w-[200px] focus:outline-none focus:border-neon-green">

            <select name="category_id" class="bg-night-850 border border-night-750 text-white rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-neon-green">
                <option value="">Todas las categorías</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>

            <select name="filter" class="bg-night-850 border border-night-750 text-white rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-neon-green">
                <option value="">Todos los estados</option>
                <option value="low_stock" {{ request('filter') == 'low_stock' ? 'selected' : '' }}>Stock Bajo</option>
                <option value="out_of_stock" {{ request('filter') == 'out_of_stock' ? 'selected' : '' }}>Agotados</option>
            </select>

            <button type="submit" class="px-4 py-2 bg-night-800 hover:bg-night-750 text-white font-bold text-xs rounded-xl border border-night-750">
                Filtrar
            </button>
            @if(request()->hasAny(['search', 'category_id', 'filter']))
                <a href="{{ route('inventory.index') }}" class="px-3 py-2 text-slate-400 hover:text-white text-xs">Limpiar</a>
            @endif
        </form>
    </div>

    <!-- Products Table -->
    <div class="card-night p-4 flex-1 overflow-hidden flex flex-col">
        <div class="flex-1 overflow-y-auto">
            <div class="overflow-x-auto min-w-full">
                <table class="w-full text-left text-xs min-w-[700px]">
                <thead class="border-b border-night-800 text-slate-400 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-2">Producto / Categoría</th>
                        <th class="py-3 px-2">SKU</th>
                        <th class="py-3 px-2 text-right">Costo Unit.</th>
                        <th class="py-3 px-2 text-right">Precio Venta</th>
                        <th class="py-3 px-2 text-center">Stock Base</th>
                        <th class="py-3 px-2">Presentaciones</th>
                        <th class="py-3 px-2 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-night-800">
                    @forelse($products as $prod)
                        <tr class="{{ $prod->stock <= 0 ? 'bg-rose-950/10' : ($prod->isLowStock() ? 'bg-amber-950/10' : '') }}">
                            <td class="py-3 px-2">
                                <span class="font-bold text-white text-sm block">{{ $prod->name }}</span>
                                <span class="text-slate-400 text-[11px]">{{ $prod->category->name ?? 'Sin categoría' }}</span>
                                @if($prod->is_liquor)
                                    <span class="inline-block text-[10px] text-amber-400 font-semibold mt-0.5">🍾 Botella completa</span>
                                @endif
                            </td>
                            <td class="py-3 px-2 font-mono text-slate-300">{{ $prod->sku }}</td>
                            <td class="py-3 px-2 text-right font-mono text-slate-300">${{ number_format($prod->price_buy, 0, ',', '.') }}</td>
                            <td class="py-3 px-2 text-right font-mono font-bold text-neon-green">${{ number_format($prod->price_sell, 0, ',', '.') }}</td>
                            <td class="py-3 px-2 text-center">
                                <span class="badge-neon font-mono font-bold {{ $prod->stock <= 0 ? 'bg-rose-950 text-rose-300 border-rose-700' : ($prod->isLowStock() ? 'bg-amber-950 text-amber-300 border-amber-700' : 'bg-emerald-950 text-emerald-300 border-emerald-700') }}">
                                    {{ rtrim(rtrim($prod->stock, '0'), '.') }} {{ $prod->base_unit }}
                                </span>
                            </td>
                            <td class="py-3 px-2">
                                <div class="flex flex-wrap gap-1">
                                    @forelse($prod->presentations as $pres)
                                        <span class="px-1.5 py-0.5 rounded bg-night-800 text-slate-300 border border-night-750 text-[10px] font-mono">
                                            {{ $pres->name }}: ${{ number_format($pres->sale_price, 0, ',', '.') }}
                                        </span>
                                    @empty
                                        <span class="text-slate-500 text-[11px]">-</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="py-3 px-2 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Quick Adjust Modal trigger -->
                                    <button type="button"
                                            @click="adjustProduct = {{ json_encode($prod) }}; newStock = {{ $prod->stock }}; showAdjustModal = true"
                                            class="px-2.5 py-1.5 bg-night-800 hover:bg-night-750 text-neon-cyan rounded-lg text-xs font-bold border border-night-750"
                                            title="Ajuste rápido de stock">
                                        +/- Stock
                                    </button>

                                    @if(auth()->user()->is_superadmin || auth()->user()->hasRoleInTenant($currentTenant->id ?? 0, 'admin'))
                                        <a href="{{ route('inventory.edit', $prod->id) }}"
                                           class="px-2.5 py-1.5 bg-night-800 hover:bg-night-750 text-slate-300 rounded-lg text-xs font-bold border border-night-750">
                                            Editar
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500">No se encontraron productos con los filtros seleccionados.</td>
                        </tr>
                    @endforelse
                </tbody>
                </table>
            </div>
        </div>

        <div class="pt-3 border-t border-night-800">
            {{ $products->links() }}
        </div>
    </div>

    <!-- Quick Stock Adjustment Modal -->
    <div x-show="showAdjustModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" style="display: none;">
        <div class="bg-night-900 border border-night-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-black text-white mb-1">Ajuste de Stock</h3>
            <p class="text-xs text-slate-400 mb-4" x-text="'Producto: ' + (adjustProduct ? adjustProduct.name : '')"></p>

            <form :action="'/inventory/' + (adjustProduct ? adjustProduct.id : 0) + '/adjust'" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Nuevo Stock Físico *</label>
                    <input type="number" name="new_stock" x-model.number="newStock" min="0" step="1" required autofocus
                           class="w-full bg-night-850 border border-night-750 text-white font-mono font-black text-xl rounded-xl px-4 py-3 focus:border-neon-green focus:outline-none">
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Motivo del Ajuste *</label>
                    <input type="text" name="reason" x-model="adjustReason" required placeholder="Ej: Rotura, Vencimiento, Corrección..."
                           class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3 py-2 text-xs focus:border-neon-green focus:outline-none">
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="showAdjustModal = false" class="btn-touch flex-1 bg-night-800 text-slate-300 text-xs font-bold">Cancelar</button>
                    <button type="submit" class="btn-touch flex-1 bg-neon-cyan text-night-950 text-xs font-black">GUARDAR AJUSTE</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
