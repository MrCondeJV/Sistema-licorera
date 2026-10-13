@extends('layouts.app', ['title' => 'Mesas y Cuentas'])

@section('content')
<div class="h-full flex flex-col" x-data="{ showOpenModal: false, selectedTableId: null, selectedTableName: '' }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
            <h2 class="text-xl font-black text-slate-900 dark:text-white flex flex-wrap items-center gap-2">
                <span>Mesas y Cuentas Abiertas</span>
                <span class="text-xs px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 font-mono">
                    {{ $tables->where('status', 'occupied')->count() }} ocupadas / {{ $tables->count() }} totales
                </span>
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Control táctil de consumo dentro del establecimiento</p>
        </div>

        <div class="flex flex-wrap items-center gap-3 text-xs font-semibold">
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-emerald-500"></span><span class="text-slate-600 dark:text-slate-300">Libre</span></div>
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-amber-500"></span><span class="text-slate-600 dark:text-slate-300">Ocupada</span></div>
            <div class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-rose-500"></span><span class="text-slate-600 dark:text-slate-300">Por Cobrar</span></div>
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
                   class="card-night p-4 border-amber-300 dark:border-amber-500/50 hover:border-amber-500 bg-amber-50/70 dark:bg-amber-950/20 flex flex-col justify-between transition-all active:scale-95 cursor-pointer shadow-sm hover:shadow-md min-h-[140px]">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-lg font-black text-slate-900 dark:text-white">{{ $table->name }}</span>
                            <span class="px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-600/60 text-[10px] font-bold">OCUPADA</span>
                        </div>
                        <p class="text-xs font-semibold text-slate-700 dark:text-slate-200 truncate">{{ $account->client_name }}</p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1">
                            <span>🕒</span>
                            <span>{{ $account->opened_at->diffForHumans(null, true) }}</span>
                            <span>&bull;</span>
                            <span>{{ $account->items->sum('quantity') }} ítems</span>
                        </p>
                    </div>

                    <div class="mt-3 pt-2 border-t border-amber-200 dark:border-amber-500/20 flex items-baseline justify-between">
                        <span class="text-[10px] text-amber-700 dark:text-amber-300 font-bold uppercase">Total:</span>
                        <span class="text-base font-black font-mono text-amber-600 dark:text-amber-400">${{ number_format($account->total, 0, ',', '.') }}</span>
                    </div>
                </a>
            @else
                <!-- Free Table Card -->
                <div @click="selectedTableId = {{ $table->id }}; selectedTableName = '{{ $table->name }}'; showOpenModal = true"
                     class="card-night p-4 border-emerald-300 dark:border-emerald-500/30 hover:border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/10 hover:bg-emerald-50 dark:hover:bg-emerald-950/25 flex flex-col justify-between transition-all active:scale-95 cursor-pointer min-h-[140px] shadow-sm hover:shadow-md">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-lg font-black text-slate-900 dark:text-white">{{ $table->name }}</span>
                            <span class="px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-600/60 text-[10px] font-bold">LIBRE</span>
                        </div>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Capacidad: {{ $table->capacity }} personas</p>
                    </div>

                    <div class="mt-4">
                        <button type="button" class="w-full py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-sm transition-all">
                            + ABRIR CUENTA
                        </button>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <!-- Modal: Open Account on Table -->
    <div x-show="showOpenModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" style="display: none;">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-black text-slate-900 dark:text-white mb-1">Abrir Cuenta en <span class="text-emerald-600 dark:text-emerald-400" x-text="selectedTableName"></span></h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Asigna un nombre o alias opcional para identificar a los clientes</p>

            <form action="{{ route('tables.open') }}" method="POST">
                @csrf
                <input type="hidden" name="table_id" :value="selectedTableId">

                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-slate-600 dark:text-slate-300 mb-1">Nombre / Alias del Cliente</label>
                    <input type="text" name="client_name" placeholder="Ej: Juan, Amigos, Cumpleaños..." autofocus
                           class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-white rounded-xl px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="showOpenModal = false" class="btn-touch flex-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-touch flex-1 bg-emerald-600 hover:bg-emerald-500 active:scale-98 text-white text-xs font-black shadow-lg shadow-emerald-600/30">
                        CONFIRMAR APERTURA
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
