@extends('layouts.app', ['title' => 'Auditoría Inmutable'])

@section('content')
<div class="h-full flex flex-col space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-black text-white">Registro de Auditoría y Trazabilidad</h2>
            <p class="text-xs text-slate-400 mt-0.5">Historial inmutable de operaciones sensibles, aperturas, cierres y anulaciones</p>
        </div>
    </div>

    <!-- Filter -->
    <div class="card-night p-3">
        <form method="GET" action="{{ route('audit.index') }}" class="flex items-center gap-2">
            <select name="action" class="bg-night-850 border border-night-750 text-white rounded-xl px-3 py-2 text-xs focus:border-neon-green focus:outline-none">
                <option value="">Todas las acciones auditadas</option>
                <option value="sale_created" {{ request('action') === 'sale_created' ? 'selected' : '' }}>Ventas Creadas</option>
                <option value="cash_session_opened" {{ request('action') === 'cash_session_opened' ? 'selected' : '' }}>Aperturas de Caja</option>
                <option value="cash_session_closed" {{ request('action') === 'cash_session_closed' ? 'selected' : '' }}>Cierres de Caja</option>
                <option value="stock_adjusted" {{ request('action') === 'stock_adjusted' ? 'selected' : '' }}>Ajustes de Stock</option>
                <option value="sale_refunded" {{ request('action') === 'sale_refunded' ? 'selected' : '' }}>Devoluciones y Anulaciones</option>
                <option value="user_pin_reset" {{ request('action') === 'user_pin_reset' ? 'selected' : '' }}>Restablecimiento de PINs</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-night-800 text-white font-bold text-xs rounded-xl border border-night-750">Filtrar</button>
            @if(request('action'))
                <a href="{{ route('audit.index') }}" class="px-3 py-2 text-slate-400 hover:text-white text-xs">Limpiar</a>
            @endif
        </form>
    </div>

    <!-- Table -->
    <div class="card-night p-4 flex-1 overflow-hidden flex flex-col">
        <div class="flex-1 overflow-y-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-night-800 text-slate-400 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-2">Fecha y Hora</th>
                        <th class="py-3 px-2">Usuario</th>
                        <th class="py-3 px-2">Acción</th>
                        <th class="py-3 px-2">Entidad / ID</th>
                        <th class="py-3 px-2">Motivo / Detalle</th>
                        <th class="py-3 px-2">IP Terminal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-night-800">
                    @forelse($logs as $log)
                        <tr>
                            <td class="py-3 px-2 font-mono text-slate-400">{{ $log->created_at->format('d/m/Y h:i:s A') }}</td>
                            <td class="py-3 px-2 font-bold text-white">{{ $log->user->name ?? 'Sistema' }}</td>
                            <td class="py-3 px-2">
                                <span class="badge-neon bg-night-800 text-neon-cyan border border-night-700 text-[10px]">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="py-3 px-2 font-mono text-slate-300">
                                {{ $log->entity_type ? class_basename($log->entity_type) . ' #' . $log->entity_id : '-' }}
                            </td>
                            <td class="py-3 px-2 text-slate-200 max-w-sm truncate">{{ $log->reason ?? '-' }}</td>
                            <td class="py-3 px-2 font-mono text-slate-500">{{ $log->ip_address ?? '127.0.0.1' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500">No hay registros de auditoría.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-3 border-t border-night-800">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
