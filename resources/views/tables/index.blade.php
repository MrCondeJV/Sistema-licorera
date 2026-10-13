@extends('layouts.app', ['title' => 'Mesas y Cuentas'])

@section('content')
<div class="h-full flex flex-col" x-data="{ showOpenModal: false, selectedTableId: null, selectedTableName: '' }">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-xl font-black text-white flex items-center gap-2">
                <span>Mesas y Cuentas Abiertas</span>
                <span class="text-xs px-2.5 py-1 rounded-full bg-night-800 text-slate-300 border border-night-750 font-mono">
                    {{ $tables->where('status', 'occupied')->count() }} ocupadas / {{ $tables->count() }} totales
                </span>
            </h2>
            <p class="text-xs text-slate-400 mt-0.5">Control táctil de consumo dentro del establecimiento</p>
        </div>

        <div class="flex items-center gap-3 text-xs font-semibold">
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-500"></span><span>Libre</span></div>
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-amber-500"></span><span>Ocupada</span></div>
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-rose-500"></span><span>Por Cobrar</span></div>
        </div>
    </div>

    <!-- Table Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 sm:gap-4 overflow-y-auto flex-1 pb-4">
        @foreach($tables as $table)
            @php
                $account = $table->activeAccount;
                $isOccupied = $table->status === 'occupied' && $account;
            @endphp

            @if($isOccupied)
                <!-- Occupied Table Card -->
                <a href="{{ route('tables.show', $table->id) }}"
                   class="card-night p-4 border-amber-500/50 hover:border-amber-400 bg-amber-950/20 flex flex-col justify-between transition-all active:scale-95 cursor-pointer shadow-lg shadow-amber-950/30 min-h-[140px]">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-lg font-black text-white">{{ $table->name }}</span>
                            <span class="badge-neon bg-amber-950 text-amber-300 border border-amber-600/60 text-[10px]">OCUPADA</span>
                        </div>
                        <p class="text-xs font-semibold text-slate-200 truncate">{{ $account->client_name }}</p>
                        <p class="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
                            <span>🕒</span>
                            <span>{{ $account->opened_at->diffForHumans(null, true) }}</span>
                            <span>&bull;</span>
                            <span>{{ $account->items->sum('quantity') }} ítems</span>
                        </p>
                    </div>

                    <div class="mt-3 pt-2 border-t border-amber-500/20 flex items-baseline justify-between">
                        <span class="text-[10px] text-amber-300 font-bold uppercase">Total:</span>
                        <span class="text-base font-black font-mono text-amber-400">${{ number_format($account->total, 0, ',', '.') }}</span>
                    </div>
                </a>
            @else
                <!-- Free Table Card -->
                <div @click="selectedTableId = {{ $table->id }}; selectedTableName = '{{ $table->name }}'; showOpenModal = true"
                     class="card-night p-4 border-emerald-500/30 hover:border-emerald-400/80 bg-emerald-950/10 hover:bg-emerald-950/25 flex flex-col justify-between transition-all active:scale-95 cursor-pointer min-h-[140px]">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-lg font-black text-white">{{ $table->name }}</span>
                            <span class="badge-neon bg-emerald-950 text-emerald-300 border border-emerald-600/60 text-[10px]">LIBRE</span>
                        </div>
                        <p class="text-xs text-slate-400">Capacidad: {{ $table->capacity }} personas</p>
                    </div>

                    <div class="mt-4">
                        <button type="button" class="w-full py-2 bg-emerald-950 hover:bg-emerald-900 border border-emerald-600/40 text-emerald-300 font-bold text-xs rounded-xl transition-all">
                            + ABRIR CUENTA
                        </button>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <!-- Modal: Open Account on Table -->
    <div x-show="showOpenModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" style="display: none;">
        <div class="bg-night-900 border border-night-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl">
            <h3 class="text-base font-black text-white mb-1">Abrir Cuenta en <span class="text-neon-green" x-text="selectedTableName"></span></h3>
            <p class="text-xs text-slate-400 mb-4">Asigna un nombre o alias opcional para identificar a los clientes</p>

            <form action="{{ route('tables.open') }}" method="POST">
                @csrf
                <input type="hidden" name="table_id" :value="selectedTableId">

                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Nombre / Alias del Cliente</label>
                    <input type="text" name="client_name" placeholder="Ej: Juan, Amigos, Cumpleaños..." autofocus
                           class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-4 py-3 text-sm focus:border-neon-green focus:outline-none">
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="showOpenModal = false" class="btn-touch flex-1 bg-night-800 text-slate-300 text-xs font-bold">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-touch flex-1 bg-gradient-to-r from-neon-green to-emerald-400 text-night-950 text-xs font-black shadow-lg shadow-neon-green/20">
                        CONFIRMAR APERTURA
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
