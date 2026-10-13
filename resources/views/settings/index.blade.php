@extends('layouts.app', ['title' => 'Configuración del Negocio'])

@section('content')
<div class="max-w-3xl mx-auto py-2">
    <div class="mb-4">
        <h2 class="text-xl font-black text-white">Configuración del Establecimiento</h2>
        <p class="text-xs text-slate-400 mt-0.5">Parámetros comerciales, tirilla de impresión y seguridad de pantalla</p>
    </div>

    <form action="{{ route('settings.update') }}" method="POST" class="card-night p-6 space-y-4">
        @csrf

        <!-- General Info -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Nombre del Establecimiento *</label>
                <input type="text" name="business_name" value="{{ old('business_name', $tenant->name) }}" required
                       class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3.5 py-2.5 text-sm focus:border-neon-green focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-300 mb-1">NIT / Identificación Tributaria</label>
                <input type="text" name="tax_id" value="{{ old('tax_id', $tenant->tax_id) }}"
                       class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3.5 py-2.5 text-sm focus:border-neon-green focus:outline-none">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Dirección Comercial</label>
                <input type="text" name="address" value="{{ old('address', $tenant->address) }}"
                       class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3.5 py-2.5 text-sm focus:border-neon-green focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Teléfono de Contacto</label>
                <input type="text" name="phone" value="{{ old('phone', $tenant->phone) }}"
                       class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3.5 py-2.5 text-sm focus:border-neon-green focus:outline-none">
            </div>
        </div>

        <!-- Receipt & Hardware Settings -->
        <div class="pt-4 border-t border-night-800 space-y-3">
            <h4 class="text-xs font-bold uppercase tracking-wider text-neon-green">Comprobantes y Parámetros de Impresión</h4>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Ancho de Impresora Térmica *</label>
                    <select name="printer_width" required class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3.5 py-2.5 text-sm focus:border-neon-green focus:outline-none">
                        <option value="80mm" {{ ($settings['printer_width'] ?? '80mm') === '80mm' ? 'selected' : '' }}>80 mm (Estándar POS)</option>
                        <option value="58mm" {{ ($settings['printer_width'] ?? '') === '58mm' ? 'selected' : '' }}>58 mm (Térmica Pequeña)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Tiempo Bloqueo por Inactividad (Minutos) *</label>
                    <input type="number" name="lock_timeout_minutes" min="1" max="120" value="{{ $settings['lock_timeout_minutes'] ?? 15 }}" required
                           class="w-full bg-night-850 border border-night-750 text-white font-mono rounded-xl px-3.5 py-2.5 text-sm focus:border-neon-green focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Mensaje al Pie del Comprobante</label>
                <textarea name="receipt_message" rows="2"
                          class="w-full bg-night-850 border border-night-750 text-white rounded-xl p-3 text-xs focus:border-neon-green focus:outline-none">{{ $settings['receipt_message'] ?? '¡Gracias por visitarnos!' }}</textarea>
            </div>
        </div>

        <!-- Module Toggles -->
        <div class="pt-4 border-t border-night-800">
            <label class="flex items-center gap-3 p-3 bg-night-850 border border-night-750 rounded-xl cursor-pointer">
                <input type="checkbox" name="debts_module_enabled" value="1" {{ ($settings['debts_module_enabled'] ?? '1') === '1' ? 'checked' : '' }} class="w-4 h-4 rounded text-neon-green">
                <div>
                    <span class="text-xs font-bold text-white block">Habilitar Módulo de Fiados y Cartera</span>
                    <span class="text-[11px] text-slate-400">Permite registrar consumos a crédito para clientes seleccionados</span>
                </div>
            </label>
        </div>

        <!-- Submit Button -->
        <div class="pt-4 border-t border-night-800 flex justify-end">
            <button type="submit" class="btn-touch py-2.5 px-6 bg-gradient-to-r from-neon-green to-emerald-400 text-night-950 text-xs font-black shadow-lg shadow-neon-green/20">
                GUARDAR CAMBIOS
            </button>
        </div>
    </form>
</div>
@endsection
