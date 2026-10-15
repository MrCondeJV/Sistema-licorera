@extends('layouts.app', ['title' => 'Copias de Seguridad'])

@section('content')
<div class="h-full flex flex-col space-y-5 max-w-6xl mx-auto" x-data="{
    backupType: 'general',
    selectedTenant: '{{ $defaultTenantId ?? ($availableTenants->first()?->id ?? '') }}'
}">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white">Copias de Seguridad (Backups)</h2>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                    Respaldo Seguro
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Genera respaldos completos de toda la plataforma o extrae la base de datos de un negocio en específico
            </p>
        </div>
    </div>

    <!-- Backup Generator Form Card -->
    <div class="card-night p-5 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <form action="{{ auth()->user()->is_superadmin && !session('superadmin_impersonating') ? route('superadmin.backups.create') : route('backups.create') }}" method="POST">
            @csrf
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                <!-- Left: Scope Selector -->
                <div class="space-y-3 flex-1">
                    <span class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 block">
                        Selecciona el Alcance de la Copia de Seguridad:
                    </span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <!-- Option 1: General Backup -->
                        <label class="relative flex items-start gap-3 p-3.5 rounded-2xl border cursor-pointer transition-all"
                               :class="backupType === 'general' ? 'bg-purple-500/10 border-purple-500 dark:border-purple-400 ring-1 ring-purple-500' : 'bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 hover:border-slate-300'">
                            <input type="radio" name="backup_type" value="general" x-model="backupType" class="mt-1 text-purple-600 focus:ring-purple-500">
                            <div>
                                <span class="text-xs font-black text-slate-900 dark:text-white block flex items-center gap-1.5">
                                    <span>🌐</span> Copia General (De Todos los Equipos)
                                </span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 block leading-tight">
                                    Respalda la base de datos completa de la plataforma con todas las licoreras, ventas y usuarios.
                                </span>
                            </div>
                        </label>

                        <!-- Option 2: Specific Tenant Backup -->
                        <label class="relative flex items-start gap-3 p-3.5 rounded-2xl border cursor-pointer transition-all"
                               :class="backupType === 'specific_tenant' ? 'bg-emerald-500/10 border-emerald-500 dark:border-emerald-400 ring-1 ring-emerald-500' : 'bg-slate-50 dark:bg-slate-800/50 border-slate-200 dark:border-slate-700 hover:border-slate-300'">
                            <input type="radio" name="backup_type" value="specific_tenant" x-model="backupType" class="mt-1 text-emerald-600 focus:ring-emerald-500">
                            <div>
                                <span class="text-xs font-black text-slate-900 dark:text-white block flex items-center gap-1.5">
                                    <span>🏢</span> Negocio en Específico
                                </span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 block leading-tight">
                                    Extrae el catálogo, inventarios, kardex, ventas, clientes y caja de una licorera puntual.
                                </span>
                            </div>
                        </label>
                    </div>

                    <!-- Dropdown for selecting specific tenant when Option 2 is chosen -->
                    <div x-show="backupType === 'specific_tenant'" x-cloak class="pt-2">
                        <label for="tenant_id" class="text-xs font-bold text-slate-700 dark:text-slate-300 block mb-1.5">
                            Selecciona el negocio / licorera:
                        </label>
                        <select name="tenant_id" id="tenant_id" x-model="selectedTenant"
                                class="w-full sm:w-80 text-xs font-bold py-2.5 px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 shadow-sm">
                            @foreach($availableTenants as $t)
                                <option value="{{ $t->id }}">
                                    🏢 {{ $t->name }} ({{ $t->branches->count() }} sedes)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Right: Submit Action Button -->
                <div class="shrink-0 flex items-end">
                    <button type="submit" class="w-full sm:w-auto btn-touch py-3.5 px-6 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-black shadow-lg shadow-emerald-600/30 rounded-2xl flex items-center justify-center gap-2.5 transition-all active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                        </svg>
                        <span>GENERAR RESPALDO AHORA</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Assurance Banner -->
    <div class="p-4 rounded-2xl bg-cyan-500/10 border border-cyan-500/20 flex items-start gap-3">
        <span class="text-xl">🛡️</span>
        <div class="text-xs text-slate-600 dark:text-slate-300">
            <strong class="text-cyan-700 dark:text-cyan-400 font-bold block mb-0.5">Seguridad y Portabilidad Total:</strong>
            Las copias generales preservan toda la base de datos SQLite lista para restauración completa. Las copias de negocio en específico generan un archivo JSON estructurado e independiente que contiene todos los productos, ventas, clientes, compras y turnos de caja de la licorera seleccionada.
        </div>
    </div>

    <!-- Backups History Table -->
    <div class="card-night p-5 rounded-3xl flex-1 flex flex-col space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                <span>Archivos de Respaldo Generados</span>
                <span class="text-xs font-mono font-bold text-slate-400">({{ count($backups) }})</span>
            </h3>
            <span class="text-[11px] text-slate-400 font-medium">Ubicación local en servidor: storage/app/backups</span>
        </div>

        <div class="overflow-x-auto flex-1">
            <table class="w-full text-left text-xs min-w-[720px]">
                <thead class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-3">Tipo / Alcance</th>
                        <th class="py-3 px-3">Archivo de Respaldo</th>
                        <th class="py-3 px-3">Tamaño</th>
                        <th class="py-3 px-3">Fecha y Hora</th>
                        <th class="py-3 px-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($backups as $b)
                        <tr>
                            <td class="py-3 px-3">
                                @if($b['type'] === 'general')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                                        <span>🌐</span> GENERAL (TODOS LOS EQUIPOS)
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        <span>🏢</span> {{ $b['target_label'] }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3 font-mono font-bold text-slate-900 dark:text-white">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm">{{ $b['extension'] === 'sqlite' ? '🗄️' : '📄' }}</span>
                                    <span class="truncate max-w-[260px]" title="{{ $b['filename'] }}">{{ $b['filename'] }}</span>
                                </div>
                            </td>
                            <td class="py-3 px-3 font-mono text-slate-600 dark:text-slate-300">{{ $b['size'] }}</td>
                            <td class="py-3 px-3 font-mono text-slate-500 dark:text-slate-400">{{ $b['created_at'] }}</td>
                            <td class="py-3 px-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ auth()->user()->is_superadmin && !session('superadmin_impersonating') ? route('superadmin.backups.download', $b['filename']) : route('backups.download', $b['filename']) }}"
                                       class="px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900 font-bold text-[11px] border border-emerald-300 dark:border-emerald-700 transition-colors flex items-center gap-1">
                                        <span>⬇️</span> Descargar
                                    </a>
                                    <form action="{{ auth()->user()->is_superadmin && !session('superadmin_impersonating') ? route('superadmin.backups.destroy', $b['filename']) : route('backups.destroy', $b['filename']) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar permanentemente esta copia de seguridad?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1.5 rounded-xl bg-rose-50 dark:bg-rose-950 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900 font-bold text-[11px] border border-rose-300 dark:border-rose-700 transition-colors" title="Eliminar">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-400">
                                No se ha generado ninguna copia de seguridad todavía. Selecciona el tipo de respaldo arriba y haz clic en "Generar Respaldo Ahora".
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
