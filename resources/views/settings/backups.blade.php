@extends('layouts.app', ['title' => 'Copias de Seguridad'])

@section('content')
<div class="h-full flex flex-col space-y-5 max-w-6xl mx-auto" x-data="{
    backupType: 'general',
    selectedTenant: '{{ $defaultTenantId ?? ($availableTenants->first()?->id ?? '') }}'
}">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            @if(auth()->user()->is_superadmin && !session('superadmin_impersonating'))
                <div class="flex items-center gap-2 mb-1">
                    <a href="{{ route('superadmin.index') }}" class="text-xs text-purple-600 dark:text-purple-400 font-bold hover:underline flex items-center gap-1">
                        &larr; Volver al Panel Superadmin
                    </a>
                </div>
            @endif
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
        @if(auth()->user()->is_superadmin && !session('superadmin_impersonating'))
            <div class="flex items-center gap-2">
                <a href="{{ route('superadmin.index') }}" class="btn-touch py-2 px-3.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl border border-slate-300 dark:border-slate-700 shadow-sm transition-all flex items-center gap-1.5">
                    🏢 Ver Licoreras
                </a>
            </div>
        @endif
    </div>

    <!-- Auto-Backup Scheduling & Automation Configuration Card (Cron) -->
    <div class="card-night p-5 rounded-3xl border border-purple-500/30 bg-purple-50/20 dark:bg-purple-950/10 shadow-sm space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-purple-200/50 dark:border-purple-800/40">
            <div>
                <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                    <span>⚙️ Programación de Auto-Copias de Seguridad (Cron Automático)</span>
                    <span class="px-2 py-0.5 rounded-full {{ ($scheduleConfig['enabled'] ?? true) ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700' : 'bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }} text-[10px] font-black">
                        {{ ($scheduleConfig['enabled'] ?? true) ? '🟢 AUTO-BACKUP ACTIVO' : '⚪ DESACTIVADO' }}
                    </span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Generación desatendida en segundo plano sin intervención humana y eliminación automática de archivos caducados
                </p>
            </div>
            <!-- Test Auto-Backup Button -->
            <form action="{{ route('superadmin.backups.run-auto') }}" method="POST">
                @csrf
                <button type="submit" class="btn-touch py-2 px-3.5 bg-purple-600 hover:bg-purple-500 text-white font-black text-xs rounded-xl shadow-md shadow-purple-600/20 flex items-center gap-1.5 transition-all active:scale-95">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Ejecutar Auto-Copia Ahora</span>
                </button>
            </form>
        </div>

        <form action="{{ route('superadmin.backups.schedule') }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- 1. Enable switch -->
                <div>
                    <label class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 block mb-1.5">
                        Estado del Servicio:
                    </label>
                    <label class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 cursor-pointer">
                        <input type="checkbox" name="enabled" value="1" {{ ($scheduleConfig['enabled'] ?? true) ? 'checked' : '' }} class="rounded text-purple-600 focus:ring-purple-500 w-4 h-4">
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Habilitar Cron Automático</span>
                    </label>
                </div>

                <!-- 2. Frequency -->
                <div>
                    <label for="frequency" class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 block mb-1.5">
                        Frecuencia de Respaldo:
                    </label>
                    <select name="frequency" id="frequency" class="w-full text-xs font-bold py-2.5 px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="daily" {{ ($scheduleConfig['frequency'] ?? 'daily') === 'daily' ? 'selected' : '' }}>Diario (1 vez al día - Recomendado)</option>
                        <option value="twice_daily" {{ ($scheduleConfig['frequency'] ?? '') === 'twice_daily' ? 'selected' : '' }}>Cada 12 Horas (Doble turno)</option>
                        <option value="weekly" {{ ($scheduleConfig['frequency'] ?? '') === 'weekly' ? 'selected' : '' }}>Semanal (Todos los domingos)</option>
                    </select>
                </div>

                <!-- 3. Time -->
                <div>
                    <label for="time" class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 block mb-1.5">
                        Hora de Ejecución:
                    </label>
                    <input type="time" name="time" id="time" value="{{ $scheduleConfig['time'] ?? '02:00' }}"
                           class="w-full text-xs font-bold py-2 px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <span class="text-[10px] text-slate-400 mt-1 block">Hora del servidor (Recomendado madrugada)</span>
                </div>

                <!-- 4. Retention -->
                <div>
                    <label for="retention_days" class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 block mb-1.5">
                        Retención de Archivos:
                    </label>
                    <select name="retention_days" id="retention_days" class="w-full text-xs font-bold py-2.5 px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="7" {{ ($scheduleConfig['retention_days'] ?? 30) == 7 ? 'selected' : '' }}>Conservar últimos 7 días</option>
                        <option value="15" {{ ($scheduleConfig['retention_days'] ?? 30) == 15 ? 'selected' : '' }}>Conservar últimos 15 días</option>
                        <option value="30" {{ ($scheduleConfig['retention_days'] ?? 30) == 30 ? 'selected' : '' }}>Conservar últimos 30 días (1 mes)</option>
                        <option value="60" {{ ($scheduleConfig['retention_days'] ?? 30) == 60 ? 'selected' : '' }}>Conservar últimos 60 días (2 meses)</option>
                        <option value="90" {{ ($scheduleConfig['retention_days'] ?? 30) == 90 ? 'selected' : '' }}>Conservar últimos 90 días (3 meses)</option>
                    </select>
                </div>

                <!-- 5. Scope / Type -->
                <div class="sm:col-span-2">
                    <label for="type" class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 block mb-1.5">
                        Contenido del Respaldo Automático:
                    </label>
                    <select name="type" id="type" class="w-full text-xs font-bold py-2.5 px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="all" {{ ($scheduleConfig['type'] ?? 'all') === 'all' ? 'selected' : '' }}>🌐 Respaldo Global Completo + JSON de todas las licoreras</option>
                        <option value="general_only" {{ ($scheduleConfig['type'] ?? '') === 'general_only' ? 'selected' : '' }}>🗄️ Solo Base de Datos SQLite General</option>
                    </select>
                </div>

                <!-- Save Action Button -->
                <div class="sm:col-span-2 flex items-end">
                    <button type="submit" class="w-full btn-touch py-2.5 px-5 bg-slate-900 hover:bg-slate-800 dark:bg-purple-700 dark:hover:bg-purple-600 text-white font-black text-xs rounded-xl shadow-md transition-all active:scale-95 flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>GUARDAR PROGRAMACIÓN DE AUTO-COPIAS</span>
                    </button>
                </div>
            </div>

            <!-- Execution Status Banner -->
            <div class="p-3 rounded-xl bg-white/70 dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-700 dark:text-slate-300">Última ejecución:</span>
                    <span class="font-mono text-purple-600 dark:text-purple-400 font-bold">
                        {{ $scheduleConfig['last_run_at'] ?? 'Aún no ejecutado' }}
                    </span>
                    @if(!empty($scheduleConfig['last_status']))
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase {{ $scheduleConfig['last_status'] === 'success' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' }}">
                            {{ $scheduleConfig['last_status'] }}
                        </span>
                    @endif
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">
                    VPS Cron: <code class="bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded text-purple-700 dark:text-purple-300">* * * * * cd /var/www/sistema-licorera && php artisan schedule:run >> /dev/null 2>&1</code>
                </div>
            </div>
        </form>
    </div>

    <!-- Backup Generator Form Card (Manual Immediate Backups) -->
    <div class="card-night p-5 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-sm">
        <form action="{{ route('superadmin.backups.create') }}" method="POST">
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
                            <!-- Sticky Actions Column -->
                            <td class="py-3 px-3 text-right sticky right-0 bg-white dark:bg-slate-900 z-10 border-l border-slate-100 dark:border-slate-800 shadow-[-6px_0_10px_-2px_rgba(0,0,0,0.06)] dark:shadow-[-6px_0_10px_-2px_rgba(0,0,0,0.5)]">
                                <div class="inline-flex items-center gap-2 whitespace-nowrap">
                                    <a href="{{ route('superadmin.backups.download', $b['filename']) }}"
                                       class="px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900 font-bold text-[11px] border border-emerald-300 dark:border-emerald-700 transition-colors flex items-center gap-1 shadow-sm">
                                        <span>⬇️</span> Descargar
                                    </a>
                                    <form action="{{ route('superadmin.backups.destroy', $b['filename']) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar permanentemente esta copia de seguridad?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1.5 rounded-xl bg-rose-50 dark:bg-rose-950 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900 font-bold text-[11px] border border-rose-300 dark:border-rose-700 transition-colors shadow-sm" title="Eliminar">
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
