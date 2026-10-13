@extends('layouts.app', ['title' => 'Compras a Proveedores'])

@section('content')
<div class="h-full flex flex-col space-y-4">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-black text-slate-900 dark:text-white">Compras y Proveedores</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Entrada de mercancía y actualización automática de costo promedio ponderado</p>
        </div>
        <a href="{{ route('purchases.create') }}" class="btn-touch py-2 px-4 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-lg shadow-emerald-600/30 rounded-xl">
            + NUEVA COMPRA
        </a>
    </div>

    <!-- Purchases Table -->
    <div class="card-night p-4 flex-1 overflow-hidden flex flex-col">
        <div class="flex-1 overflow-y-auto">
            <div class="overflow-x-auto min-w-full">
                <table class="w-full text-left text-xs min-w-[650px]">
                <thead class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-2">Compra #</th>
                        <th class="py-3 px-2">Proveedor</th>
                        <th class="py-3 px-2">Factura / Remisión</th>
                        <th class="py-3 px-2">Fecha</th>
                        <th class="py-3 px-2">Estado</th>
                        <th class="py-3 px-2 text-right">Total</th>
                        <th class="py-3 px-2 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($purchases as $p)
                        <tr>
                            <td class="py-3 px-2 font-mono font-bold text-slate-900 dark:text-white">#{{ $p->id }}</td>
                            <td class="py-3 px-2 font-bold text-slate-800 dark:text-slate-200">{{ $p->supplier->name }}</td>
                            <td class="py-3 px-2 font-mono text-slate-500 dark:text-slate-400">{{ $p->invoice_number ?? 'S/N' }}</td>
                            <td class="py-3 px-2 font-mono text-slate-500 dark:text-slate-400">{{ $p->created_at->format('d/m/Y h:i A') }}</td>
                            <td class="py-3 px-2">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $p->status === 'received' ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-700' : 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-700' }}">
                                    {{ $p->status === 'received' ? 'RECIBIDA' : 'BORRADOR' }}
                                </span>
                            </td>
                            <td class="py-3 px-2 text-right font-mono font-bold text-slate-900 dark:text-white">${{ number_format($p->total, 0, ',', '.') }}</td>
                            <td class="py-3 px-2 text-right">
                                @if($p->status === 'draft')
                                    <form action="{{ route('purchases.receive', $p->id) }}" method="POST" onsubmit="return confirm('¿Confirmar recepción de mercancía y actualizar stock en inventario?');">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs rounded-xl shadow-md">
                                            RECIBIR MERCANCÍA
                                        </button>
                                    </form>
                                @else
                                    <span class="text-slate-400 dark:text-slate-500 font-mono text-[11px]">Ingresada {{ $p->received_at?->format('d/m/Y') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 dark:text-slate-500">No hay compras registradas aún.</td>
                        </tr>
                    @endforelse
                </tbody>
                </table>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-200 dark:border-slate-800">
            {{ $purchases->links() }}
        </div>
    </div>
</div>
@endsection
