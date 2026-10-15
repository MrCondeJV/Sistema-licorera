@extends('layouts.app', ['title' => 'Conteos Físicos de Inventario'])

@section('content')
<div class="h-full flex flex-col space-y-4" x-data="{ showNewCountModal: false }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-black text-slate-900 dark:text-white">Conteos Físicos de Inventario</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Comparación entre existencias teóricas y conteo real con ajuste auditado</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('inventory.index') }}" class="btn-touch py-2 px-3 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl">
                &larr; Volver
            </a>
            <button type="button" @click="showNewCountModal = true" class="btn-touch py-2 px-4 bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:hover:bg-slate-100 dark:text-slate-900 border border-slate-800 dark:border-white text-xs font-semibold rounded-xl shadow-xs transition-colors">
                + NUEVO CONTEO FÍSICO
            </button>
        </div>
    </div>

    <!-- Counts List -->
    <div class="card-night p-4 flex-1 overflow-hidden flex flex-col">
        <div class="flex-1 overflow-y-auto">
            <div class="overflow-x-auto min-w-full">
                <table class="w-full text-left text-xs min-w-[650px]">
                <thead class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-2">ID</th>
                        <th class="py-3 px-2">Fecha</th>
                        <th class="py-3 px-2">Responsable</th>
                        <th class="py-3 px-2">Estado</th>
                        <th class="py-3 px-2">Notas</th>
                        <th class="py-3 px-2 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($counts as $count)
                        <tr>
                            <td class="py-3 px-2 font-mono font-bold text-slate-900 dark:text-white">#{{ $count->id }}</td>
                            <td class="py-3 px-2 font-mono text-slate-600 dark:text-slate-300">{{ $count->created_at->format('d/m/Y h:i A') }}</td>
                            <td class="py-3 px-2 text-slate-800 dark:text-slate-200 font-semibold">{{ $count->user->name }}</td>
                            <td class="py-3 px-2">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $count->status === 'applied' ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-700' : 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-700' }}">
                                    {{ strtoupper($count->status) }}
                                </span>
                            </td>
                            <td class="py-3 px-2 text-slate-500 dark:text-slate-400 max-w-xs truncate">{{ $count->notes ?? 'Sin observaciones' }}</td>
                            <td class="py-3 px-2 text-right">
                                @if($count->status === 'pending')
                                    <form action="{{ route('inventory.counts.apply', $count->id) }}" method="POST"
                                          data-confirm="¿Deseas aplicar este conteo físico y ajustar el stock en el kardex de la empresa?"
                                          data-confirm-title="Aplicar Conteo Físico #{{ $count->id }}"
                                          data-confirm-btn="Sí, aplicar ajustes"
                                          data-confirm-danger="false"
                                          data-confirm-icon="question">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:hover:bg-slate-100 dark:text-slate-900 border border-slate-800 dark:border-white font-semibold text-xs rounded-lg shadow-xs transition-colors">
                                            Aplicar Ajustes
                                        </button>
                                    </form>
                                @else
                                    <span class="text-slate-400 dark:text-slate-500 font-mono text-[11px]">Aplicado el {{ $count->applied_at?->format('d/m/Y') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 dark:text-slate-500">No hay conteos físicos registrados aún.</td>
                        </tr>
                    @endforelse
                </tbody>
                </table>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-200 dark:border-slate-800">
            {{ $counts->links() }}
        </div>
    </div>

    <!-- Modal: New Physical Count -->
    <div x-show="showNewCountModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" style="display: none;">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-2xl w-full shadow-2xl max-h-[90vh] flex flex-col">
            <h3 class="text-base font-black text-slate-900 dark:text-white mb-1">Registrar Conteo Físico</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Ingresa el stock físico contado en el establecimiento</p>

            <form action="{{ route('inventory.counts.store') }}" method="POST" class="flex-1 flex flex-col overflow-hidden">
                @csrf
                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Notas / Motivo del Conteo</label>
                    <input type="text" name="notes" placeholder="Ej: Conteo fin de semana, Auditoría mensual..."
                           class="input-night w-full text-xs">
                </div>

                <div class="flex-1 overflow-y-auto space-y-2 pr-1 mb-4 border-y border-slate-200 dark:border-slate-800 py-3">
                    @foreach($products as $idx => $prod)
                        <div class="p-2.5 bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl flex items-center justify-between gap-3">
                            <input type="hidden" name="counts[{{ $idx }}][product_id]" value="{{ $prod->id }}">
                            <div class="flex-1">
                                <span class="text-xs font-bold text-slate-900 dark:text-white block">{{ $prod->name }}</span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">Stock en sistema: {{ rtrim(rtrim($prod->stock, '0'), '.') }}</span>
                            </div>
                            <div class="w-32">
                                <input type="number" name="counts[{{ $idx }}][physical_stock]" value="{{ rtrim(rtrim($prod->stock, '0'), '.') }}" min="0" step="1" required
                                       class="input-night w-full text-emerald-600 dark:text-emerald-400 font-mono font-bold text-sm text-center py-1.5">
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="showNewCountModal = false" class="btn-touch flex-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl">Cancelar</button>
                    <button type="submit" class="btn-touch flex-1 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-lg shadow-emerald-600/30 rounded-xl">GUARDAR CONTEO</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
