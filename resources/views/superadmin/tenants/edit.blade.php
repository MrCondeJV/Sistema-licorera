@extends('layouts.app', ['title' => 'Editar Empresa — Superadmin'])

@section('content')
<div class="max-w-4xl mx-auto space-y-4">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('superadmin.index') }}" class="p-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 transition-all flex items-center justify-center shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <h2 class="text-xl font-black text-slate-900 dark:text-white flex flex-wrap items-center gap-2">
                    <span>Editar Empresa: {{ $tenant->name }}</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 text-[11px] font-semibold">SUPERADMIN</span>
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Modificación de información corporativa, plan asignado y estado operativo</p>
            </div>
        </div>

        <!-- Quick Access Buttons -->
        <div class="flex items-center gap-2">
            <a href="{{ route('superadmin.tenants.users', $tenant->id) }}" class="px-3.5 py-2 text-xs font-medium bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 shadow-xs flex items-center gap-1.5 rounded-xl transition-colors">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                <span>Usuarios ({{ $users->count() }})</span>
            </a>

            <form action="{{ route('superadmin.tenants.enter', $tenant->id) }}" method="POST">
                @csrf
                <button type="submit" class="px-3.5 py-2 text-xs font-semibold bg-slate-900 hover:bg-slate-800 text-white dark:bg-slate-100 dark:hover:bg-white dark:text-slate-900 border border-slate-800 dark:border-white shadow-xs flex items-center gap-1.5 rounded-xl transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                    <span>Acceder</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="card-night p-6 border-slate-200 dark:border-slate-800">
        <form action="{{ route('superadmin.tenants.update', $tenant->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nombre Comercial de la Licorera *</label>
                    <input type="text" name="name" value="{{ old('name', $tenant->name) }}" required class="input-night w-full text-sm">
                    @error('name') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Plan de Suscripción *</label>
                    <select name="plan_id" required class="input-night w-full text-sm">
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" {{ old('plan_id', $tenant->plan_id) == $plan->id ? 'selected' : '' }}>
                                {{ $plan->name }} — ${{ number_format($plan->price_monthly, 0, ',', '.') }}/mes
                            </option>
                        @endforeach
                    </select>
                    @error('plan_id') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Estado de la Empresa *</label>
                    <select name="status" required class="input-night w-full text-sm">
                        <option value="active" {{ old('status', $tenant->status) === 'active' ? 'selected' : '' }}>Activo (Operación normal)</option>
                        <option value="suspended" {{ old('status', $tenant->status) === 'suspended' ? 'selected' : '' }}>Suspendido (Bloqueo de acceso)</option>
                    </select>
                    @error('status') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">NIT / Identificación Fiscal</label>
                    <input type="text" name="tax_id" value="{{ old('tax_id', $tenant->tax_id) }}" class="input-night w-full text-sm">
                    @error('tax_id') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Correo Electrónico Corporativo</label>
                    <input type="email" name="email" value="{{ old('email', $tenant->email) }}" class="input-night w-full text-sm">
                    @error('email') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Teléfono de Contacto</label>
                    <input type="text" name="phone" value="{{ old('phone', $tenant->phone) }}" class="input-night w-full text-sm">
                    @error('phone') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Dirección Física del Establecimiento</label>
                    <input type="text" name="address" value="{{ old('address', $tenant->address) }}" class="input-night w-full text-sm">
                    @error('address') <span class="text-xs text-rose-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Details overview -->
            <div class="p-4 bg-slate-50 dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400 space-y-1.5">
                <p><strong class="text-slate-700 dark:text-slate-300">Identificador UUID:</strong> <span class="font-mono text-slate-600 dark:text-slate-400">{{ $tenant->uuid }}</span></p>
                <p><strong class="text-slate-700 dark:text-slate-300">Slug del sistema:</strong> <span class="font-mono text-slate-800 dark:text-slate-200 font-semibold">{{ $tenant->slug }}</span></p>
                <p><strong class="text-slate-700 dark:text-slate-300">Sedes registradas:</strong> {{ $branches->count() }} ({{ $branches->pluck('name')->join(', ') }})</p>
                <div class="flex items-center justify-between pt-2 border-t border-slate-200/80 dark:border-slate-800/80 mt-2">
                    <span>Usuarios y empleados vinculados: <strong class="text-slate-800 dark:text-slate-200">{{ $users->count() }}</strong></span>
                    <a href="{{ route('superadmin.tenants.users', $tenant->id) }}" class="text-xs font-semibold text-slate-900 dark:text-white hover:underline flex items-center gap-1">
                        <span>Gestionar credenciales y accesos</span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>

            <!-- Actions -->
            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                <a href="{{ route('superadmin.index') }}" class="px-4 py-2 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white dark:bg-slate-100 dark:hover:bg-white dark:text-slate-900 border border-slate-800 dark:border-white rounded-xl text-xs font-semibold shadow-xs transition-colors">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection