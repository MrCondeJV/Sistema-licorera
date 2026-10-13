@extends('layouts.app', ['title' => 'Configuración del Negocio'])

@section('content')
<div class="max-w-3xl mx-auto py-2">
    <div class="mb-4">
        <h2 class="text-xl font-black text-slate-900 dark:text-white">Configuración del Establecimiento</h2>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Parámetros comerciales, tirilla de impresión y seguridad de pantalla</p>
    </div>

    <form action="{{ route('settings.update') }}" method="POST" class="card-night p-6 space-y-4">
        @csrf

        <!-- General Info -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Nombre del Establecimiento *</label>
                <input type="text" name="business_name" value="{{ old('business_name', $tenant->name) }}" required
                       class="input-night w-full text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">NIT / Identificación Tributaria</label>
                <input type="text" name="tax_id" value="{{ old('tax_id', $tenant->tax_id) }}"
                       class="input-night w-full text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Dirección Comercial</label>
                <input type="text" name="address" value="{{ old('address', $tenant->address) }}"
                       class="input-night w-full text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Teléfono de Contacto</label>
                <input type="text" name="phone" value="{{ old('phone', $tenant->phone) }}"
                       class="input-night w-full text-sm">
            </div>
        </div>

        <!-- Receipt & Hardware Settings -->
        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 space-y-3">
            <h4 class="text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Comprobantes y Parámetros de Impresión</h4>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Ancho de Impresora Térmica *</label>
                    <select name="printer_width" required class="input-night w-full text-sm">
                        <option value="80mm" {{ ($settings['printer_width'] ?? '80mm') === '80mm' ? 'selected' : '' }}>80 mm (Estándar POS)</option>
                        <option value="58mm" {{ ($settings['printer_width'] ?? '') === '58mm' ? 'selected' : '' }}>58 mm (Térmica Pequeña)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Tiempo Bloqueo por Inactividad (Minutos) *</label>
                    <input type="number" name="lock_timeout_minutes" min="1" max="120" value="{{ $settings['lock_timeout_minutes'] ?? 15 }}" required
                           class="input-night w-full font-mono text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Mensaje al Pie del Comprobante</label>
                <textarea name="receipt_message" rows="2"
                          class="input-night w-full text-xs">{{ $settings['receipt_message'] ?? '¡Gracias por visitarnos!' }}</textarea>
            </div>
        </div>

        <!-- Module Toggles -->
        <div class="pt-4 border-t border-slate-200 dark:border-slate-800">
            <label class="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800 rounded-xl cursor-pointer hover:border-emerald-500 transition-colors">
                <input type="checkbox" name="debts_module_enabled" value="1" {{ ($settings['debts_module_enabled'] ?? '1') === '1' ? 'checked' : '' }} class="w-4 h-4 rounded text-emerald-600 focus:ring-0">
                <div>
                    <span class="text-xs font-bold text-slate-900 dark:text-white block">Habilitar Módulo de Fiados y Cartera</span>
                    <span class="text-[11px] text-slate-500 dark:text-slate-400">Permite registrar consumos a crédito para clientes seleccionados</span>
                </div>
            </label>
        </div>

        <!-- Submit Button -->
        <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex justify-end">
            <button type="submit" class="btn-touch py-2.5 px-6 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-lg shadow-emerald-600/30 rounded-xl">
                GUARDAR CAMBIOS
            </button>
        </div>
    </form>
</div>
@endsection
