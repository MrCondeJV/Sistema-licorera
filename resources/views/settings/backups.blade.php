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
                    <a href="{{ route('superadmin.index') }}" class="text-xs text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white font-semibold hover:underline flex items-center gap-1">
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
                <a href="{{ route('superadmin.index') }}" class="btn-touch py-2 px-3.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-xl border border-slate-300 dark:border-slate-700 shadow-xs transition-all flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    <span>Ver Licoreras</span>
                </a>
            </div>
        @endif
    </div>

    <!-- Auto-Backup Scheduling & Automation Configuration Card (Cron) -->
    <div class="card-night p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-200 dark:border-slate-800">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                    <span>Programación de Auto-Copias de Seguridad (Cron Automático)</span>
                    <span class="px-2 py-0.5 rounded-full {{ ($scheduleConfig['enabled'] ?? true) ? 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-300 dark:border-slate-600' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }} text-[10px] font-semibold inline-flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full {{ ($scheduleConfig['enabled'] ?? true) ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                        {{ ($scheduleConfig['enabled'] ?? true) ? 'AUTO-BACKUP ACTIVO' : 'DESACTIVADO' }}
                    </span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Generación desatendida en segundo plano sin intervención humana y eliminación automática de archivos caducados
                </p>
            </div>
            <!-- Test Auto-Backup Button -->
            <form action="{{ route('superadmin.backups.run-auto') }}" method="POST">
                @csrf
                <button type="submit" class="btn-touch py-2 px-3.5 bg-slate-900 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-900 font-semibold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-all active:scale-95">
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
                        <input type="checkbox" name="enabled" value="1" {{ ($scheduleConfig['enabled'] ?? true) ? 'checked' : '' }} class="rounded text-emerald-700 focus:ring-emerald-600 w-4 h-4">
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Habilitar Cron Automático</span>
                    </label>
                </div>

                <!-- 2. Frequency -->
                <div>
                    <label for="frequency" class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 block mb-1.5">
                        Frecuencia de Respaldo:
                    </label>
                    <select name="frequency" id="frequency" class="w-full text-xs font-bold py-2.5 px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-slate-500 focus:border-slate-500">
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
                           class="w-full text-xs font-bold py-2 px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-slate-500 focus:border-slate-500">
                    <span class="text-[10px] text-slate-400 mt-1 block">Hora del servidor (Recomendado madrugada)</span>
                </div>

                <!-- 4. Retention -->
                <div>
                    <label for="retention_days" class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-300 block mb-1.5">
                        Retención de Archivos:
                    </label>
                    <select name="retention_days" id="retention_days" class="w-full text-xs font-bold py-2.5 px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-slate-500 focus:border-slate-500">
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
                    <select name="type" id="type" class="w-full text-xs font-semibold py-2.5 px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-slate-900">
                        <option value="all" {{ ($scheduleConfig['type'] ?? 'all') === 'all' ? 'selected' : '' }}>Respaldo Global Completo + JSON de todas las licoreras</option>
                        <option value="general_only" {{ ($scheduleConfig['type'] ?? '') === 'general_only' ? 'selected' : '' }}>Solo Base de Datos General</option>
                    </select>
                </div>

                <!-- Save Action Button -->
                <div class="sm:col-span-2 flex items-end">
                    <button type="submit" class="w-full btn-touch py-2.5 px-5 bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:hover:bg-slate-100 dark:text-slate-900 font-semibold text-xs rounded-xl shadow-xs transition-all active:scale-95 flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>GUARDAR PROGRAMACIÓN DE AUTO-COPIAS</span>
                    </button>
                </div>
            </div>

            <!-- Execution Status Banner -->
            <div class="p-3 rounded-xl bg-white/70 dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-700 dark:text-slate-300">Última ejecución:</span>
                    <span class="font-mono text-slate-800 dark:text-slate-200 font-bold">
                        {{ $scheduleConfig['last_run_at'] ?? 'Aún no ejecutado' }}
                    </span>
                    @if(!empty($scheduleConfig['last_status']))
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase {{ $scheduleConfig['last_status'] === 'success' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300' }}">
                            {{ $scheduleConfig['last_status'] }}
                        </span>
                    @endif
                </div>
                <div class="text-[11px] text-slate-500 dark:text-slate-400 font-mono">
                    VPS Cron: <code class="bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded text-slate-800 dark:text-slate-200">* * * * * cd /var/www/sistema-licorera && php artisan schedule:run >> /dev/null 2>&1</code>
                </div>
            </div>
        </form>
    </div>

    <!-- Backup Generator Form Card (Manual Immediate Backups) -->
    <div class="card-night p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-xs">
        <form action="{{ route('superadmin.backups.create') }}" method="POST">
            @csrf
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                <!-- Left: Scope Selector -->
                <div class="space-y-3 flex-1">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 block">
                        Selecciona el Alcance de la Copia de Seguridad:
                    </span>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <!-- Option 1: General Backup -->
                        <label class="relative flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition-all"
                                :class="backupType === 'general' ? 'bg-slate-50 dark:bg-slate-800/80 border-slate-900 dark:border-white ring-1 ring-slate-900 dark:ring-white' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 hover:border-slate-300'">
                            <input type="radio" name="backup_type" value="general" x-model="backupType" class="mt-1 text-slate-900 focus:ring-slate-900">
                            <div>
                                <span class="text-xs font-bold text-slate-900 dark:text-white block flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    Copia General (De Todos los Negocios)
                                </span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 block leading-tight">
                                    Respalda la base de datos completa de la plataforma con todas las licoreras, ventas y usuarios.
                                </span>
                            </div>
                        </label>

                        <!-- Option 2: Specific Tenant Backup -->
                        <label class="relative flex items-start gap-3 p-3.5 rounded-xl border cursor-pointer transition-all"
                                :class="backupType === 'specific_tenant' ? 'bg-slate-50 dark:bg-slate-800/80 border-slate-900 dark:border-white ring-1 ring-slate-900 dark:ring-white' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 hover:border-slate-300'">
                            <input type="radio" name="backup_type" value="specific_tenant" x-model="backupType" class="mt-1 text-slate-900 focus:ring-slate-900">
                            <div>
                                <span class="text-xs font-bold text-slate-900 dark:text-white block flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                    Negocio en Específico
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
                                class="w-full sm:w-80 text-xs font-semibold py-2.5 px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-slate-900 shadow-xs">
                            @foreach($availableTenants as $t)
                                <option value="{{ $t->id }}">
                                    {{ $t->name }} ({{ $t->branches->count() }} sedes)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Right: Submit Action Button -->
                <div class="shrink-0 flex items-end">
                    <button type="submit" class="w-full sm:w-auto btn-touch py-3 px-5 bg-slate-900 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-900 text-xs font-semibold shadow-xs rounded-xl flex items-center justify-center gap-2 transition-all active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                        </svg>
                        <span>GENERAR RESPALDO AHORA</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Assurance Banner -->
    <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-850 border border-slate-200 dark:border-slate-800 flex items-start gap-3">
        <svg class="w-5 h-5 text-slate-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
        <div class="text-xs text-slate-600 dark:text-slate-300">
            <strong class="text-slate-900 dark:text-white font-bold block mb-0.5">Seguridad y Portabilidad Total:</strong>
            Las copias generales preservan toda la base de datos lista para restauración completa. Las copias de negocio en específico generan un archivo JSON estructurado e independiente que contiene todos los productos, ventas, clientes, compras y turnos de caja de la licorera seleccionada.
        </div>
    </div>

    <!-- Backups History Table -->
    <div class="card-night p-5 rounded-2xl flex-1 flex flex-col space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
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
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        GENERAL (TODOS LOS NEGOCIOS)
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        {{ $b['target_label'] }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-3 px-3 font-mono font-bold text-slate-900 dark:text-white">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                    <span class="truncate max-w-[260px]" title="{{ $b['filename'] }}">{{ $b['filename'] }}</span>
                                </div>
                            </td>
                            <td class="py-3 px-3 font-mono text-slate-600 dark:text-slate-300">{{ $b['size'] }}</td>
                            <td class="py-3 px-3 font-mono text-slate-500 dark:text-slate-400">{{ $b['created_at'] }}</td>
                            <!-- Sticky Actions Column -->
                            <td class="py-3 px-3 text-right sticky right-0 bg-white dark:bg-slate-900 z-10 border-l border-slate-100 dark:border-slate-800 shadow-[-6px_0_10px_-2px_rgba(0,0,0,0.06)] dark:shadow-[-6px_0_10px_-2px_rgba(0,0,0,0.5)]">
                                <div class="inline-flex items-center gap-1.5 whitespace-nowrap">
                                    <a href="{{ route('superadmin.backups.download', $b['filename']) }}"
                                       class="px-2.5 py-1.5 rounded-lg bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 font-semibold text-[11px] border border-slate-200 dark:border-slate-700 transition-colors inline-flex items-center gap-1 shadow-xs">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                        <span>Descargar</span>
                                    </a>
                                    <form action="{{ route('superadmin.backups.destroy', $b['filename']) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar permanentemente esta copia de seguridad?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors" title="Eliminar">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
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
