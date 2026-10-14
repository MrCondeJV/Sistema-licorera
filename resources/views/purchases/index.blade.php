@extends('layouts.app', ['title' => 'Compras a Proveedores'])

@section('content')
<div class="h-full flex flex-col space-y-5">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-3 py-1 rounded-lg text-xs font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                    Facturas de Compra
                </span>
                <span class="text-xs text-slate-400">/</span>
                <a href="{{ route('suppliers.index') }}" class="px-3 py-1 rounded-lg text-xs font-bold text-slate-500 hover:text-slate-800 dark:hover:text-white bg-slate-100 dark:bg-slate-800 transition-colors">
                    Directorio de Proveedores &rarr;
                </a>
            </div>
            <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Compras y Entradas de Mercancía</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Entrada de stock y actualización automática de costo promedio ponderado</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('suppliers.index') }}" class="btn-touch py-2.5 px-4 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                <span>PROVEEDORES</span>
            </a>
            <a href="{{ route('purchases.create') }}" class="btn-touch py-2.5 px-5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-lg shadow-emerald-600/30 rounded-xl flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                <span>NUEVA COMPRA</span>
            </a>
        </div>
    </div>

    <!-- Purchases Table -->
    <div class="card-night p-5 flex-1 overflow-hidden flex flex-col">
        <div class="flex-1 overflow-y-auto">
            <div class="overflow-x-auto min-w-full">
                <table class="w-full text-left text-sm min-w-[700px]">
                <thead class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold text-xs uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 px-3">Compra #</th>
                        <th class="py-3.5 px-3">Proveedor</th>
                        <th class="py-3.5 px-3">Factura / Remisión</th>
                        <th class="py-3.5 px-3">Fecha</th>
                        <th class="py-3.5 px-3">Estado</th>
                        <th class="py-3.5 px-3 text-right">Total</th>
                        <th class="py-3.5 px-3 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($purchases as $p)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-3.5 px-3 font-mono font-bold text-slate-900 dark:text-white">#{{ $p->id }}</td>
                            <td class="py-3.5 px-3 font-semibold text-slate-800 dark:text-slate-200">{{ $p->supplier->name }}</td>
                            <td class="py-3.5 px-3 font-mono text-slate-500 dark:text-slate-400">{{ $p->invoice_number ?? 'S/N' }}</td>
                            <td class="py-3.5 px-3 font-mono text-xs text-slate-500 dark:text-slate-400">{{ $p->created_at->format('d/m/Y h:i A') }}</td>
                            <td class="py-3.5 px-3">
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $p->status === 'received' ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-700' : 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-700' }}">
                                    {{ $p->status === 'received' ? 'RECIBIDA' : 'BORRADOR' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-3 text-right font-mono font-bold text-slate-900 dark:text-white text-base">${{ number_format($p->total, 0, ',', '.') }}</td>
                            <td class="py-3.5 px-3 text-right">
                                @if($p->status === 'draft')
                                    <form action="{{ route('purchases.receive', $p->id) }}" method="POST" 
                                          data-confirm="¿Confirmar recepción de mercancía y actualizar stock en el inventario?"
                                          data-confirm-title="Recepción de Compra #{{ $p->id }}"
                                          data-confirm-btn="Sí, Recibir y Actualizar Stock"
                                          data-confirm-danger="false"
                                          data-confirm-icon="question"
                                          class="inline">
                                        @csrf
                                        <button type="submit" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-md transition-all active:scale-95">
                                            RECIBIR MERCANCÍA
                                        </button>
                                    </form>
                                @else
                                    <span class="text-slate-400 dark:text-slate-500 font-mono text-xs">Ingresada {{ $p->received_at?->format('d/m/Y') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400 dark:text-slate-500 text-sm">No hay compras registradas aún. Haz clic en "NUEVA COMPRA" para comenzar.</td>
                        </tr>
                    @endforelse
                </tbody>
                </table>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-200 dark:border-slate-800">
            {{ $purchases->links() }}
        </div>
    </div>
</div>
@endsection
