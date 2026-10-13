@extends('layouts.app', ['title' => 'Kardex de Inventario'])

@section('content')
<div class="h-full flex flex-col space-y-4">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-black text-white">Kardex y Trazabilidad de Inventario</h2>
            <p class="text-xs text-slate-400 mt-0.5">Historial inmutable de entradas, salidas, ventas y ajustes de stock</p>
        </div>
        <a href="{{ route('inventory.index') }}" class="btn-touch py-2 px-4 bg-night-800 hover:bg-night-750 text-slate-300 text-xs font-bold">
            &larr; Volver a Inventario
        </a>
    </div>

    <!-- Filter -->
    <div class="card-night p-3">
        <form method="GET" action="{{ route('inventory.kardex') }}" class="flex flex-wrap items-center gap-2">
            <select name="product_id" class="bg-night-850 border border-night-750 text-white rounded-xl px-3 py-2 text-xs flex-1 min-w-[200px] focus:outline-none focus:border-neon-green">
                <option value="">Todos los productos</option>
                @foreach($products as $p)
                    <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>

            <select name="movement_type" class="bg-night-850 border border-night-750 text-white rounded-xl px-3 py-2 text-xs focus:outline-none focus:border-neon-green">
                <option value="">Todos los tipos</option>
                <option value="sale" {{ request('movement_type') == 'sale' ? 'selected' : '' }}>Ventas</option>
                <option value="purchase" {{ request('movement_type') == 'purchase' ? 'selected' : '' }}>Compras</option>
                <option value="adjustment" {{ request('movement_type') == 'adjustment' ? 'selected' : '' }}>Ajustes</option>
                <option value="return" {{ request('movement_type') == 'return' ? 'selected' : '' }}>Devoluciones</option>
            </select>

            <button type="submit" class="px-4 py-2 bg-night-800 hover:bg-night-750 text-white font-bold text-xs rounded-xl border border-night-750">Filtrar</button>
            @if(request()->hasAny(['product_id', 'movement_type']))
                <a href="{{ route('inventory.kardex') }}" class="px-3 py-2 text-slate-400 hover:text-white text-xs">Limpiar</a>
            @endif
        </form>
    </div>

    <!-- Kardex Table -->
    <div class="card-night p-4 flex-1 overflow-hidden flex flex-col">
        <div class="flex-1 overflow-y-auto">
            <div class="overflow-x-auto min-w-full">
                <table class="w-full text-left text-xs min-w-[750px]">
                <thead class="border-b border-night-800 text-slate-400 font-bold uppercase tracking-wider">
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
                <tbody class="divide-y divide-night-800 font-mono">
                    @forelse($movements as $mov)
                        <tr>
                            <td class="py-2.5 px-2 text-slate-400">{{ $mov->created_at->format('d/m/Y h:i:s A') }}</td>
                            <td class="py-2.5 px-2 font-sans font-bold text-white">{{ $mov->product->name }}</td>
                            <td class="py-2.5 px-2 font-sans">
                                <span class="badge-neon text-[10px] {{ $mov->quantity < 0 ? 'bg-rose-950 text-rose-300 border-rose-800' : 'bg-emerald-950 text-emerald-300 border-emerald-800' }}">
                                    {{ strtoupper($mov->movement_type) }}
                                </span>
                            </td>
                            <td class="py-2.5 px-2 text-right font-bold {{ $mov->quantity < 0 ? 'text-rose-400' : 'text-emerald-400' }}">
                                {{ $mov->quantity > 0 ? '+' : '' }}{{ rtrim(rtrim($mov->quantity, '0'), '.') }}
                            </td>
                            <td class="py-2.5 px-2 text-right text-slate-400">{{ rtrim(rtrim($mov->stock_before, '0'), '.') }}</td>
                            <td class="py-2.5 px-2 text-right text-white font-bold">{{ rtrim(rtrim($mov->stock_after, '0'), '.') }}</td>
                            <td class="py-2.5 px-2 font-sans text-slate-300 max-w-xs truncate">{{ $mov->reason }}</td>
                            <td class="py-2.5 px-2 font-sans text-slate-400">{{ $mov->user->name ?? 'Sistema' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-500 font-sans">No hay movimientos registrados en el kardex.</td>
                        </tr>
                    @endforelse
                </tbody>
                </table>
            </div>
        </div>

        <div class="pt-3 border-t border-night-800">
            {{ $movements->links() }}
        </div>
    </div>
</div>
@endsection
