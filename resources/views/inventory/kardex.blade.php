@extends('layouts.app', ['title' => 'Kardex de Inventario'])

@section('content')
<div class="h-full flex flex-col space-y-4">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-black text-slate-900 dark:text-white">Kardex y Trazabilidad de Inventario</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Historial inmutable de entradas, salidas, ventas y ajustes de stock</p>
        </div>
        <a href="{{ route('inventory.index') }}" class="btn-touch py-2 px-4 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl">
            &larr; Volver a Inventario
        </a>
    </div>

    <!-- Filter -->
    <div class="card-night p-3">
        <form method="GET" action="{{ route('inventory.kardex') }}" class="flex flex-wrap items-center gap-2">
            <select name="product_id" class="input-night text-xs flex-1 min-w-[200px]">
                <option value="">Todos los productos</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>

            <select name="movement_type" class="input-night text-xs">
                <option value="">Todos los tipos</option>
                <option value="sale" {{ request('movement_type') == 'sale' ? 'selected' : '' }}>Ventas</option>
                <option value="purchase" {{ request('movement_type') == 'purchase' ? 'selected' : '' }}>Compras</option>
                <option value="adjustment" {{ request('movement_type') == 'adjustment' ? 'selected' : '' }}>Ajustes</option>
                <option value="return" {{ request('movement_type') == 'return' ? 'selected' : '' }}>Devoluciones</option>
            </select>

            <button type="submit" class="px-4 py-2 bg-slate-800 dark:bg-slate-700 hover:bg-slate-700 dark:hover:bg-slate-600 text-white font-bold text-xs rounded-xl shadow-sm">Filtrar</button>
            @if(request()->hasAny(['product_id', 'movement_type']))
                <a href="{{ route('inventory.kardex') }}" class="px-3 py-2 text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-white text-xs">Limpiar</a>
            @endif
        </form>
    </div>

    <!-- Kardex Table -->
    <div class="card-night p-4 flex-1 overflow-hidden flex flex-col">
        <div class="flex-1 overflow-y-auto">
            <div class="overflow-x-auto min-w-full">
                <table class="w-full text-left text-xs min-w-[750px]">
                <thead class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-2">Fecha y Hora</th>
                        <th class="py-3 px-2">Producto</th>
                        <th class="py-3 px-2">Tipo</th>
                        <th class="py-3 px-2 text-right">Cantidad</th>
                        <th class="py-3 px-2 text-right">Stock Previo</th>
                        <th class="py-3 px-2 text-right">Stock Posterior</th>
                        <th class="py-3 px-2">Motivo</th>
                        <th class="py-3 px-2">Usuario</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-mono">
                    @forelse($movements as $mov)
                        <tr>
                            <td class="py-2.5 px-2 text-slate-500 dark:text-slate-400">{{ $mov->created_at->format('d/m/Y h:i:s A') }}</td>
                            <td class="py-2.5 px-2 font-sans font-bold text-slate-900 dark:text-white">{{ $mov->product->name }}</td>
                            <td class="py-2.5 px-2 font-sans">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $mov->quantity < 0 ? 'bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800' : 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800' }}">
                                    {{ strtoupper($mov->movement_type) }}
                                </span>
                            </td>
                            <td class="py-2.5 px-2 text-right font-bold {{ $mov->quantity < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                {{ $mov->quantity > 0 ? '+' : '' }}{{ rtrim(rtrim($mov->quantity, '0'), '.') }}
                            </td>
                            <td class="py-2.5 px-2 text-right text-slate-500 dark:text-slate-400">{{ rtrim(rtrim($mov->stock_before, '0'), '.') }}</td>
                            <td class="py-2.5 px-2 text-right text-slate-900 dark:text-white font-bold">{{ rtrim(rtrim($mov->stock_after, '0'), '.') }}</td>
                            <td class="py-2.5 px-2 font-sans text-slate-700 dark:text-slate-300 max-w-xs truncate">{{ $mov->reason }}</td>
                            <td class="py-2.5 px-2 font-sans text-slate-500 dark:text-slate-400">{{ $mov->user->name ?? 'Sistema' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400 dark:text-slate-500 font-sans">No hay movimientos registrados en el kardex.</td>
                        </tr>
                    @endforelse
                </tbody>
                </table>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-200 dark:border-slate-800">
            {{ $movements->links() }}
        </div>
    </div>
</div>
@endsection
