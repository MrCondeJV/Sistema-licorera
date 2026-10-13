@extends('layouts.app', ['title' => 'Nueva Empresa — Superadmin'])

@section('content')
<div class="max-w-4xl mx-auto space-y-4">
    <!-- Breadcrumb / Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('superadmin.index') }}" class="p-2.5 rounded-xl bg-night-800 hover:bg-night-750 text-slate-400 hover:text-white transition-all">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <h2 class="text-xl font-black text-white flex items-center gap-2">
                    <span>Crear Nueva Empresa / Licorera</span>
                    <span class="badge-neon bg-purple-950 text-purple-300 border border-purple-700 text-xs">SUPERADMIN</span>
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">Aprovisionamiento integral de inquilino, sede principal, catálogo inicial y credenciales</p>
            </div>
        </div>
    </div>

    <!-- Creation Form -->
    <div class="card-night p-6 border-night-750">
        <form action="{{ route('superadmin.tenants.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Section 1: Business Details -->
            <div>
                <h3 class="text-sm font-black uppercase text-neon-green tracking-wider mb-3 flex items-center gap-2">
                    <span>🏢 Datos Comerciales de la Empresa</span>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Nombre Comercial de la Licorera *</label>
                        <input type="text" name="business_name" value="{{ old('business_name') }}" required placeholder="Ej: Licorera El Poblado Express" class="input-night w-full text-sm">
                        @error('business_name') <span class="text-xs text-rose-400">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Plan de Suscripción *</label>
                        <select name="plan_id" required class="input-night w-full text-sm">
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" {{ old('plan_id') == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }} — ${{ number_format($plan->price_monthly, 0, ',', '.') }}/mes (Hasta {{ $plan->max_users }} usuarios, {{ $plan->max_branches }} sedes)
                                </option>
                            @endforeach
                        </select>
                        @error('plan_id') <span class="text-xs text-rose-400">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">NIT / Identificación Fiscal</label>
                        <input type="text" name="tax_id" value="{{ old('tax_id') }}" placeholder="Ej: 901.654.321-8" class="input-night w-full text-sm">
                        @error('tax_id') <span class="text-xs text-rose-400">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Teléfono / WhatsApp</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" placeholder="Ej: 3105556789" class="input-night w-full text-sm">
                        @error('phone') <span class="text-xs text-rose-400">{{ $message }}</span> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-300 mb-1">Dirección Física del Establecimiento</label>
                        <input type="text" name="address" value="{{ old('address') }}" placeholder="Ej: Calle 10 # 40-25, Medellín" class="input-night w-full text-sm">
                        @error('address') <span class="text-xs text-rose-400">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <hr class="border-night-800">

            <!-- Section 2: Initial Admin Credentials -->
            <div>
                <h3 class="text-sm font-black uppercase text-neon-cyan tracking-wider mb-3 flex items-center gap-2">
                    <span>👑 Administrador Inicial del Negocio</span>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Nombre Completo del Administrador *</label>
                        <input type="text" name="admin_name" value="{{ old('admin_name') }}" required placeholder="Ej: Andrea Gómez" class="input-night w-full text-sm">
                        @error('admin_name') <span class="text-xs text-rose-400">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Correo Electrónico (Login) *</label>
                        <input type="email" name="email" value="{{ old('email') }}" required placeholder="andrea@licoreraelpoblado.com" class="input-night w-full text-sm">
                        @error('email') <span class="text-xs text-rose-400">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">PIN de Acceso Táctil (4 Dígitos) *</label>
                        <input type="text" name="pin" value="{{ old('pin') }}" maxlength="4" pattern="\d{4}" required placeholder="1234" class="input-night w-full font-mono text-center tracking-widest text-base">
                        <p class="text-[10px] text-slate-500 mt-1">Utilizado para acceso táctil rápido en caja y comandero.</p>
                        @error('pin') <span class="text-xs text-rose-400">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Contraseña de Respaldo</label>
                        <input type="password" name="password" placeholder="Mínimo 6 caracteres (Opcional)" class="input-night w-full text-sm">
                        <p class="text-[10px] text-slate-500 mt-1">Por defecto: Admin2026* si se deja en blanco.</p>
                        @error('password') <span class="text-xs text-rose-400">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="pt-4 border-t border-night-800 flex items-center justify-end gap-3">
                <a href="{{ route('superadmin.index') }}" class="btn-touch bg-night-800 hover:bg-night-750 text-slate-300 px-5 text-xs font-bold">
                    Cancelar
                </a>
                <button type="submit" class="btn-touch bg-neon-green hover:bg-emerald-400 text-night-950 px-6 text-xs font-black shadow-lg shadow-neon-green/20">
                    Guardar y Crear Empresa
                </button>
            </div>
        </form>
    </div>
</div>
@endsection