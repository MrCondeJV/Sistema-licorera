@extends('layouts.app', ['title' => 'Mesas y Cuentas'])

@section('content')
<div class="h-full min-h-0 flex-1 flex flex-col overflow-hidden" x-data="{ showOpenModal: false, selectedTableId: null, selectedTableName: '' }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4 shrink-0">
        <div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white flex flex-wrap items-center gap-2.5">
                <span>Mesas y Cuentas de Consumo</span>
                <span class="text-xs px-3 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 font-mono font-bold">
                    {{ $tables->where('status', 'occupied')->count() }} ocupadas / {{ $tables->count() }} totales
                </span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">Control visual y táctil de consumo abierto en el local</p>
        </div>

        <div class="flex flex-wrap items-center gap-3 text-xs font-bold">
            <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/50">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                <span class="text-emerald-700 dark:text-emerald-300">Libre</span>
            </div>
            <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/50">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                <span class="text-amber-700 dark:text-amber-300">Ocupada / Abierta</span>
            </div>
        </div>
    </div>

    <!-- Table Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-5 gap-3.5 sm:gap-4 overflow-y-auto flex-1 min-h-0 pr-1 pb-4">
        @foreach($tables as $table)
            @php
                $account = $table->activeAccount;
                $isOccupied = $table->status === 'occupied' && $account;
            @endphp

            @if($isOccupied)
                <!-- Occupied Table Card -->
                <a href="{{ route('tables.show', $table->id) }}"
                   class="bg-amber-50/80 dark:bg-amber-950/20 border-2 border-amber-400 dark:border-amber-500/60 hover:border-amber-500 rounded-3xl p-4 sm:p-5 flex flex-col justify-between transition-all active:scale-98 cursor-pointer shadow-md hover:shadow-lg min-h-[160px] group">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">Mesa {{ $table->name }}</span>
                            <span class="px-2.5 py-1 rounded-full bg-amber-200 dark:bg-amber-900/80 text-amber-900 dark:text-amber-200 border border-amber-300 dark:border-amber-600 text-xs font-black tracking-wide">
                                OCUPADA
                            </span>
                        </div>
                        <p class="text-sm font-bold text-slate-800 dark:text-slate-100 truncate flex items-center gap-1">
                            <span>👤</span>
                            <span>{{ $account->client_name ?: 'Cliente General' }}</span>
                        </p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1.5 font-medium">
                            <span>🕒 {{ $account->opened_at->diffForHumans(null, true) }}</span>
                            <span>&bull;</span>
                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ $account->items->sum('quantity') }} ítems</span>
                        </p>
                    </div>

                    <div class="mt-4 pt-2.5 border-t border-amber-300/60 dark:border-amber-500/30 flex items-baseline justify-between">
                        <span class="text-xs text-amber-800 dark:text-amber-300 font-bold uppercase">Consumo:</span>
                        <span class="text-lg sm:text-xl font-black font-mono text-amber-700 dark:text-amber-300">${{ number_format($account->total, 0, ',', '.') }}</span>
                    </div>
                </a>
            @else
                <!-- Free Table Card -->
                <div @click="selectedTableId = {{ $table->id }}; selectedTableName = '{{ $table->name }}'; showOpenModal = true"
                     class="bg-emerald-50/50 dark:bg-emerald-950/15 border-2 border-emerald-300 dark:border-emerald-600/40 hover:border-emerald-500 rounded-3xl p-4 sm:p-5 flex flex-col justify-between transition-all active:scale-98 cursor-pointer min-h-[160px] shadow-sm hover:shadow-md group">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">Mesa {{ $table->name }}</span>
                            <span class="px-2.5 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-600 text-xs font-black tracking-wide">
                                LIBRE
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-medium">Capacidad: {{ $table->capacity }} personas</p>
                    </div>

                    <div class="mt-4">
                        <button type="button" class="w-full py-2.5 px-3 bg-emerald-600 group-hover:bg-emerald-500 text-white font-black text-xs sm:text-sm rounded-2xl shadow-md shadow-emerald-600/20 transition-all flex items-center justify-center gap-1.5">
                            <span>+ ABRIR CUENTA</span>
                        </button>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <!-- Modal: Open Account on Table -->
    <div x-show="showOpenModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm" style="display: none;">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-lg font-black text-slate-900 dark:text-white mb-1">Abrir Cuenta en <span class="text-emerald-600 dark:text-emerald-400" x-text="'Mesa ' + selectedTableName"></span></h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Ingresa el nombre o alias para identificar a los clientes en esta mesa</p>

            <form action="{{ route('tables.open') }}" method="POST">
                @csrf
                <input type="hidden" name="table_id" :value="selectedTableId">

                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1.5">Nombre / Alias del Cliente</label>
                    <input type="text" name="client_name" placeholder="Ej: Juan Pérez, Amigos, Barra..." autofocus
                           class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-white rounded-2xl px-4 py-3 text-sm font-semibold focus:border-emerald-500 focus:outline-none">
                </div>

                <div class="flex gap-2.5">
                    <button type="button" @click="showOpenModal = false" class="btn-touch flex-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-2xl">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-touch flex-1 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-lg shadow-emerald-600/30 rounded-2xl">
                        ABRIR MESA
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
