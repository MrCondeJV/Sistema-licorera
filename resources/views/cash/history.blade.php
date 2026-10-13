@extends('layouts.app', ['title' => 'Historial de Cierres de Caja'])

@section('content')
<div class="h-full flex flex-col space-y-4">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-black text-slate-900 dark:text-white">Historial de Turnos y Cierres de Caja</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Auditoría de arqueos, bases iniciales, efectivo contado y diferencias</p>
        </div>
        <a href="{{ route('cash.index') }}" class="btn-touch py-2 px-4 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl">
            &larr; Volver a Caja Activa
        </a>
    </div>

    <!-- Table -->
    <div class="card-night p-4 flex-1 overflow-hidden flex flex-col">
        <div class="flex-1 overflow-y-auto">
            <div class="overflow-x-auto min-w-full">
                <table class="w-full text-left text-xs min-w-[780px]">
                <thead class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-2">Caja / Empleado</th>
                        <th class="py-3 px-2">Apertura</th>
                        <th class="py-3 px-2">Cierre</th>
                        <th class="py-3 px-2 text-right">Base Inicial</th>
                        <th class="py-3 px-2 text-right">Esperado</th>
                        <th class="py-3 px-2 text-right">Contado</th>
                        <th class="py-3 px-2 text-right">Diferencia</th>
                        <th class="py-3 px-2">Observaciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($sessions as $s)
                        <tr>
                            <td class="py-3 px-2">
                                <span class="font-bold text-slate-900 dark:text-white block">{{ $s->cashRegister->name }}</span>
                                <span class="text-slate-500 dark:text-slate-400 text-[11px]">{{ $s->user->name }}</span>
                            </td>
                            <td class="py-3 px-2 font-mono text-slate-700 dark:text-slate-300">{{ $s->opened_at->format('d/m/Y h:i A') }}</td>
                            <td class="py-3 px-2 font-mono text-slate-700 dark:text-slate-300">{{ $s->closed_at?->format('d/m/Y h:i A') ?? 'N/A' }}</td>
                            <td class="py-3 px-2 text-right font-mono text-slate-700 dark:text-slate-300">${{ number_format($s->opening_amount, 0, ',', '.') }}</td>
                            <td class="py-3 px-2 text-right font-mono text-slate-700 dark:text-slate-300">${{ number_format($s->expected_cash, 0, ',', '.') }}</td>
                            <td class="py-3 px-2 text-right font-mono font-bold text-slate-900 dark:text-white">${{ number_format($s->counted_cash, 0, ',', '.') }}</td>
                            <td class="py-3 px-2 text-right font-mono font-black {{ $s->difference == 0 ? 'text-emerald-600 dark:text-emerald-400' : ($s->difference > 0 ? 'text-cyan-600 dark:text-cyan-400' : 'text-rose-600 dark:text-rose-400') }}">
                                {{ $s->difference > 0 ? '+' : '' }}${{ number_format($s->difference, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-2 text-slate-600 dark:text-slate-300 max-w-xs truncate">{{ $s->closing_notes ?? 'Sin observaciones' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400 dark:text-slate-500">No se han registrado turnos cerrados todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
                </table>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-200 dark:border-slate-800">
            {{ $sessions->links() }}
        </div>
    </div>
</div>
@endsection
