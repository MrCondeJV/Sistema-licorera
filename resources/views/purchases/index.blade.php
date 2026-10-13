@extends('layouts.app', ['title' => 'Compras a Proveedores'])

@section('content')
<div class="h-full flex flex-col space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-black text-white">Compras y Proveedores</h2>
            <p class="text-xs text-slate-400 mt-0.5">Entrada de mercancía y actualización automática de costo promedio ponderado</p>
        </div>
        <a href="{{ route('purchases.create') }}" class="btn-touch py-2 px-4 bg-gradient-to-r from-neon-green to-emerald-400 text-night-950 text-xs font-black shadow-lg shadow-neon-green/20">
            + NUEVA COMPRA
        </a>
    </div>

    <!-- Purchases Table -->
    <div class="card-night p-4 flex-1 overflow-hidden flex flex-col">
        <div class="flex-1 overflow-y-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-night-800 text-slate-400 font-bold uppercase tracking-wider">
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
                <tbody class="divide-y divide-night-800">
                    @forelse($purchases as $p)
                        <tr>
                            <td class="py-3 px-2 font-mono font-bold text-white">#{{ $p->id }}</td>
                            <td class="py-3 px-2 font-bold text-slate-200">{{ $p->supplier->name }}</td>
                            <td class="py-3 px-2 font-mono text-slate-400">{{ $p->invoice_number ?? 'S/N' }}</td>
                            <td class="py-3 px-2 font-mono text-slate-400">{{ $p->created_at->format('d/m/Y h:i A') }}</td>
                            <td class="py-3 px-2">
                                <span class="badge-neon {{ $p->status === 'received' ? 'bg-emerald-950 text-emerald-400 border-emerald-700' : 'bg-amber-950 text-amber-400 border-amber-700' }} text-[10px]">
                                    {{ $p->status === 'received' ? 'RECIBIDA' : 'BORRADOR' }}
                                </span>
                            </td>
                            <td class="py-3 px-2 text-right font-mono font-bold text-white">${{ number_format($p->total, 0, ',', '.') }}</td>
                            <td class="py-3 px-2 text-right">
                                @if($p->status === 'draft')
                                    <form action="{{ route('purchases.receive', $p->id) }}" method="POST" onsubmit="return confirm('¿Confirmar recepción de mercancía y actualizar stock en inventario?');">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 bg-neon-green hover:bg-emerald-400 text-night-950 font-black text-xs rounded-xl shadow-md">
                                            RECIBIR MERCANCÍA
                                        </button>
                                    </form>
                                @else
                                    <span class="text-slate-500 font-mono text-[11px]">Ingresada {{ $p->received_at?->format('d/m/Y') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500">No hay compras registradas aún.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-3 border-t border-night-800">
            {{ $purchases->links() }}
        </div>
    </div>
</div>
@endsection
