@extends('layouts.app', ['title' => 'Copias de Seguridad'])

@section('content')
<div class="h-full flex flex-col space-y-4 max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="text-xl font-black text-slate-900 dark:text-white">Copias de Seguridad (Backups)</h2>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">
                    Seguridad Blindada
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Genera respaldos completos de tu base de datos e inventario para proteger tu negocio</p>
        </div>
        <div class="flex items-center gap-2">
            <form action="{{ route('backups.create') }}" method="POST">
                @csrf
                <button type="submit" class="btn-touch py-2.5 px-4 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-lg shadow-emerald-600/30 rounded-xl flex items-center gap-2">
                    <span>💾 GENERAR COPIA AHORA</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Assurance Banner -->
    <div class="p-4 rounded-2xl bg-cyan-500/10 border border-cyan-500/20 flex items-start gap-3">
        <span class="text-xl">☁️</span>
        <div class="text-xs text-slate-600 dark:text-slate-300">
            <strong class="text-cyan-700 dark:text-cyan-400 font-bold block mb-0.5">Tranquilidad Total para el Dueño:</strong>
            Si se te apaga la tablet, te roban el equipo o sufres un daño físico en el local, no pierdes nada. Puedes descargar periódicamente tus archivos de respaldo y guardarlos en una memoria USB o Google Drive.
        </div>
    </div>

    <!-- Backups Table -->
    <div class="card-night p-4 flex-1 flex flex-col">
        <div class="overflow-x-auto flex-1">
            <table class="w-full text-left text-xs min-w-[650px]">
                <thead class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-3">Archivo de Respaldo</th>
                        <th class="py-3 px-3">Tamaño</th>
                        <th class="py-3 px-3">Fecha y Hora de Creación</th>
                        <th class="py-3 px-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($backups as $b)
                        <tr>
                            <td class="py-3 px-3 font-mono font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <span class="text-emerald-500 font-bold">📄</span> {{ $b['filename'] }}
                            </td>
                            <td class="py-3 px-3 font-mono text-slate-600 dark:text-slate-300">{{ $b['size'] }}</td>
                            <td class="py-3 px-3 font-mono text-slate-500 dark:text-slate-400">{{ $b['created_at'] }}</td>
                            <td class="py-3 px-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('backups.download', $b['filename']) }}"
                                       class="px-3 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 dark:hover:bg-emerald-900 font-bold text-[11px] border border-emerald-300 dark:border-emerald-700 transition-colors">
                                        ⬇️ Descargar
                                    </a>
                                    <form action="{{ route('backups.destroy', $b['filename']) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este archivo de respaldo?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-rose-50 dark:bg-rose-950 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900 font-bold text-[11px] border border-rose-300 dark:border-rose-700 transition-colors">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-12 text-center text-slate-400">
                                No se ha generado ninguna copia de seguridad todavía. Haz clic en "Generar Copia Ahora".
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
