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
                    <span class="px-2.5 py-0.5 rounded-full bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-700 text-xs font-bold">SUPERADMIN</span>
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Modificación de información corporativa, plan asignado y estado operativo</p>
            </div>
        </div>

        <!-- Quick Access Button -->
        <form action="{{ route('superadmin.tenants.enter', $tenant->id) }}" method="POST">
            @csrf
            <button type="submit" class="btn-touch bg-cyan-600 hover:bg-cyan-500 text-white px-4 py-2 text-xs font-black shadow-lg shadow-cyan-600/20 flex items-center gap-2 rounded-xl">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                </svg>
                <span>Acceder a esta Empresa</span>
            </button>
        </form>
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
            <div class="p-4 bg-slate-50 dark:bg-slate-950 rounded-xl border border-slate-200 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400 space-y-1">
                <p><strong class="text-slate-700 dark:text-slate-300">Identificador UUID:</strong> <span class="font-mono text-slate-600 dark:text-slate-400">{{ $tenant->uuid }}</span></p>
                <p><strong class="text-slate-700 dark:text-slate-300">Slug del sistema:</strong> <span class="font-mono text-emerald-600 dark:text-emerald-400 font-bold">{{ $tenant->slug }}</span></p>
                <p><strong class="text-slate-700 dark:text-slate-300">Sedes registradas:</strong> {{ $branches->count() }} ({{ $branches->pluck('name')->join(', ') }})</p>
                <p><strong class="text-slate-700 dark:text-slate-300">Usuarios vinculados:</strong> {{ $users->count() }}</p>
            </div>

            <!-- Actions -->
            <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3">
                <a href="{{ route('superadmin.index') }}" class="btn-touch bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 px-5 text-xs font-bold rounded-xl shadow-sm transition-all">
                    Cancelar
                </a>
                <button type="submit" class="btn-touch bg-emerald-600 hover:bg-emerald-500 text-white px-6 text-xs font-black shadow-lg shadow-emerald-600/30 rounded-xl transition-all active:scale-95">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection