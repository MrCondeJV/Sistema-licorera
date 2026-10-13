@extends('layouts.app', ['title' => 'Historial de Cierres de Caja'])

@section('content')
<div class="h-full flex flex-col space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-black text-white">Historial de Turnos y Cierres de Caja</h2>
            <p class="text-xs text-slate-400 mt-0.5">Auditoría de arqueos, bases iniciales, efectivo contado y diferencias</p>
        </div>
        <a href="{{ route('cash.index') }}" class="btn-touch py-2 px-4 bg-night-800 hover:bg-night-750 text-slate-300 text-xs font-bold">
            &larr; Volver a Caja Activa
        </a>
    </div>

    <!-- Table -->
    <div class="card-night p-4 flex-1 overflow-hidden flex flex-col">
        <div class="flex-1 overflow-y-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-night-800 text-slate-400 font-bold uppercase tracking-wider">
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
                <tbody class="divide-y divide-night-800">
                    @forelse($sessions as $s)
                        <tr>
                            <td class="py-3 px-2">
                                <span class="font-bold text-white block">{{ $s->cashRegister->name }}</span>
                                <span class="text-slate-400 text-[11px]">{{ $s->user->name }}</span>
                            </td>
                            <td class="py-3 px-2 font-mono text-slate-300">{{ $s->opened_at->format('d/m/Y h:i A') }}</td>
                            <td class="py-3 px-2 font-mono text-slate-300">{{ $s->closed_at?->format('d/m/Y h:i A') ?? 'N/A' }}</td>
                            <td class="py-3 px-2 text-right font-mono">${{ number_format($s->opening_amount, 0, ',', '.') }}</td>
                            <td class="py-3 px-2 text-right font-mono">${{ number_format($s->expected_cash, 0, ',', '.') }}</td>
                            <td class="py-3 px-2 text-right font-mono font-bold text-white">${{ number_format($s->counted_cash, 0, ',', '.') }}</td>
                            <td class="py-3 px-2 text-right font-mono font-black {{ $s->difference == 0 ? 'text-emerald-400' : ($s->difference > 0 ? 'text-cyan-400' : 'text-rose-400') }}">
                                {{ $s->difference > 0 ? '+' : '' }}${{ number_format($s->difference, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-2 text-slate-300 max-w-xs truncate">{{ $s->closing_notes ?? 'Sin observaciones' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-500">No se han registrado turnos cerrados todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-3 border-t border-night-800">
            {{ $sessions->links() }}
        </div>
    </div>
</div>
@endsection
