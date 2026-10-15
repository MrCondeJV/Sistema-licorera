@extends('layouts.app', ['title' => 'Nueva Empresa — Superadmin'])

@section('content')
<div class="max-w-4xl mx-auto space-y-4">
    <!-- Breadcrumb / Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('superadmin.index') }}" class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 transition-all flex items-center justify-center shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <h2 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2">
                    <span>Crear Nueva Empresa / Licorera</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700 text-[10px] font-semibold uppercase tracking-wider">SUPERADMIN</span>
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Aprovisionamiento integral de inquilino, sede principal, catálogo inicial y credenciales</p>
            </div>
        </div>
    </div>

    <!-- Creation Form -->
    <div class="card-night p-6 border-slate-200 dark:border-slate-800">
        <form action="{{ route('superadmin.tenants.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Section 1: Business Details -->
            <div>
                <h3 class="text-sm font-bold uppercase text-slate-900 dark:text-white tracking-wider mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                    <span>Datos Comerciales de la Empresa</span>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nombre Comercial de la Licorera *</label>
                        <input type="text" name="business_name" value="{{ old('business_name') }}" required placeholder="Ej: Licorera El Poblado Express" class="input-night w-full text-sm">
                        @error('business_name') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Plan de Suscripción *</label>
                        <select name="plan_id" required class="input-night w-full text-sm">
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}" {{ old('plan_id') == $plan->id ? 'selected' : '' }}>
                                    {{ $plan->name }} — ${{ number_format($plan->price_monthly, 0, ',', '.') }}/mes (Hasta {{ $plan->max_users }} usuarios, {{ $plan->max_branches }} sedes)
                                </option>
                            @endforeach
                        </select>
                        @error('plan_id') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">NIT / Identificación Fiscal</label>
                        <input type="text" name="tax_id" value="{{ old('tax_id') }}" placeholder="Ej: 901.654.321-8" class="input-night w-full text-sm">
                        @error('tax_id') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Teléfono / WhatsApp</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" placeholder="Ej: 3105556789" class="input-night w-full text-sm">
                        @error('phone') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Dirección Física del Establecimiento</label>
                        <input type="text" name="address" value="{{ old('address') }}" placeholder="Ej: Calle 10 # 40-25, Medellín" class="input-night w-full text-sm">
                        @error('address') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <hr class="border-slate-200 dark:border-slate-800">

            <!-- Section 2: Initial Admin Credentials -->
            <div>
                <h3 class="text-sm font-bold uppercase text-slate-900 dark:text-white tracking-wider mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                    <span>Administrador Inicial del Negocio</span>
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nombre Completo del Administrador *</label>
                        <input type="text" name="admin_name" value="{{ old('admin_name') }}" required placeholder="Ej: Andrea Gómez" class="input-night w-full text-sm">
                        @error('admin_name') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Correo Electrónico (Login) *</label>
                        <input type="email" name="email" value="{{ old('email') }}" required placeholder="andrea@licoreraelpoblado.com" class="input-night w-full text-sm">
                        @error('email') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Contraseña de Acceso para el Administrador *</label>
                        <input type="password" name="password" required minlength="6" placeholder="Mínimo 6 caracteres (ej: Admin2026*)" class="input-night w-full text-sm font-mono font-bold">
                        <p class="text-[10px] text-slate-400 mt-1">Utilizada para el inicio de sesión seguro con correo y contraseña en producción.</p>
                        @error('password') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                <a href="{{ route('superadmin.index') }}" class="btn-touch bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 px-5 text-xs font-semibold rounded-xl shadow-xs transition-all">
                    Cancelar
                </a>
                <button type="submit" class="btn-touch bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:hover:bg-slate-100 dark:text-slate-900 px-6 text-xs font-semibold shadow-xs rounded-xl transition-all active:scale-95">
                    Guardar y Crear Empresa
                </button>
            </div>
        </form>
    </div>
</div>
@endsection