@extends('layouts.app', ['title' => 'Conteos Físicos de Inventario'])

@section('content')
<div class="h-full flex flex-col space-y-4" x-data="{ showNewCountModal: false }">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-black text-white">Conteos Físicos de Inventario</h2>
            <p class="text-xs text-slate-400 mt-0.5">Comparación entre existencias teóricas y conteo real con ajuste auditado</p>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('inventory.index') }}" class="btn-touch py-2 px-3 bg-night-800 text-slate-300 text-xs font-bold">
                &larr; Volver
            </a>
            <button type="button" @click="showNewCountModal = true" class="btn-touch py-2 px-4 bg-neon-green text-night-950 text-xs font-black shadow-lg shadow-neon-green/20">
                + NUEVO CONTEO FÍSICO
            </button>
        </div>
    </div>

    <!-- Counts List -->
    <div class="card-night p-4 flex-1 overflow-hidden flex flex-col">
        <div class="flex-1 overflow-y-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-night-800 text-slate-400 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-2">ID</th>
                        <th class="py-3 px-2">Fecha</th>
                        <th class="py-3 px-2">Responsable</th>
                        <th class="py-3 px-2">Estado</th>
                        <th class="py-3 px-2">Notas</th>
                        <th class="py-3 px-2 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-night-800">
                    @forelse($counts as $count)
                        <tr>
                            <td class="py-3 px-2 font-mono font-bold text-white">#{{ $count->id }}</td>
                            <td class="py-3 px-2 font-mono text-slate-300">{{ $count->created_at->format('d/m/Y h:i A') }}</td>
                            <td class="py-3 px-2 text-slate-200 font-semibold">{{ $count->user->name }}</td>
                            <td class="py-3 px-2">
                                <span class="badge-neon {{ $count->status === 'applied' ? 'bg-emerald-950 text-emerald-400 border-emerald-700' : 'bg-amber-950 text-amber-400 border-amber-700' }} text-[10px]">
                                    {{ strtoupper($count->status) }}
                                </span>
                            </td>
                            <td class="py-3 px-2 text-slate-400 max-w-xs truncate">{{ $count->notes ?? 'Sin observaciones' }}</td>
                            <td class="py-3 px-2 text-right">
                                @if($count->status === 'pending')
                                    <form action="{{ route('inventory.counts.apply', $count->id) }}" method="POST" onsubmit="return confirm('¿Aplicar este conteo físico y ajustar el stock en el kardex?');">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 bg-neon-cyan hover:bg-cyan-400 text-night-950 font-bold text-xs rounded-xl shadow-md">
                                            Aplicar Ajustes
                                        </button>
                                    </form>
                                @else
                                    <span class="text-slate-500 font-mono text-[11px]">Aplicado el {{ $count->applied_at?->format('d/m/Y') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500">No hay conteos físicos registrados aún.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-3 border-t border-night-800">
            {{ $counts->links() }}
        </div>
    </div>

    <!-- Modal: New Physical Count -->
    <div x-show="showNewCountModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" style="display: none;">
        <div class="bg-night-900 border border-night-800 rounded-3xl p-6 max-w-2xl w-full shadow-2xl max-h-[90vh] flex flex-col">
            <h3 class="text-base font-black text-white mb-1">Registrar Conteo Físico</h3>
            <p class="text-xs text-slate-400 mb-4">Ingresa el stock físico contado en el establecimiento</p>

            <form action="{{ route('inventory.counts.store') }}" method="POST" class="flex-1 flex flex-col overflow-hidden">
                @csrf
                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Notas / Motivo del Conteo</label>
                    <input type="text" name="notes" placeholder="Ej: Conteo fin de semana, Auditoría mensual..."
                           class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3.5 py-2 text-xs focus:border-neon-green focus:outline-none">
                </div>

                <div class="flex-1 overflow-y-auto space-y-2 pr-1 mb-4 border-y border-night-800 py-3">
                    @foreach($products as $idx => $prod)
                        <div class="p-2.5 bg-night-850 border border-night-750 rounded-xl flex items-center justify-between gap-3">
                            <input type="hidden" name="counts[{{ $idx }}][product_id]" value="{{ $prod->id }}">
                            <div class="flex-1">
                                <span class="text-xs font-bold text-white block">{{ $prod->name }}</span>
                                <span class="text-[11px] text-slate-400 font-mono">Stock en sistema: {{ rtrim(rtrim($prod->stock, '0'), '.') }}</span>
                            </div>
                            <div class="w-32">
                                <input type="number" name="counts[{{ $idx }}][physical_stock]" value="{{ rtrim(rtrim($prod->stock, '0'), '.') }}" min="0" step="1" required
                                       class="w-full bg-night-900 border border-night-700 text-neon-green font-mono font-bold text-sm text-center rounded-lg py-1.5 focus:border-neon-green focus:outline-none">
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="showNewCountModal = false" class="btn-touch flex-1 bg-night-800 text-slate-300 text-xs font-bold">Cancelar</button>
                    <button type="submit" class="btn-touch flex-1 bg-neon-green text-night-950 text-xs font-black">GUARDAR CONTEO</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
