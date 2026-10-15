@extends('layouts.app', ['title' => 'Gestión de Planes SaaS'])

@section('content')
<div class="h-full flex flex-col space-y-6" x-data="plansManager()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('superadmin.index') }}" class="text-xs text-purple-600 dark:text-purple-400 font-bold hover:underline">
                    ← Volver al Panel Superadmin
                </a>
            </div>
            <h2 class="text-xl font-black text-slate-900 dark:text-white flex items-center gap-2 mt-1">
                <span>📦 Planes y Precios de Suscripción SaaS</span>
                <span class="px-2.5 py-0.5 rounded-full bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-700 text-xs font-bold">SUPERADMIN</span>
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Crea, edita y ajusta los límites, precios y características de los planes comercializables</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="openCreateModal()" class="btn-touch bg-purple-600 hover:bg-purple-500 text-white px-4 py-2.5 text-xs font-black shadow-lg shadow-purple-600/30 rounded-2xl flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Nuevo Plan SaaS</span>
            </button>
        </div>
    </div>

    <!-- Plans Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @forelse($plans as $plan)
            <div class="card-night p-6 flex flex-col justify-between border-2 {{ $plan->is_active ? 'border-purple-500/30 hover:border-purple-500' : 'border-slate-300 dark:border-slate-700 opacity-60' }} rounded-3xl relative transition-all">
                <div class="space-y-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $plan->is_active ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20' }}">
                                {{ $plan->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                            <h3 class="text-lg font-black text-slate-900 dark:text-white mt-2">{{ $plan->name }}</h3>
                            <p class="text-xs font-mono text-slate-400">Slug: {{ $plan->slug }}</p>
                        </div>
                        <span class="text-xs font-bold text-slate-500 bg-slate-100 dark:bg-slate-800 px-2.5 py-1 rounded-xl">
                            🏢 {{ $plan->tenants_count }} {{ Str::plural('licorera', $plan->tenants_count) }}
                        </span>
                    </div>

                    <div class="py-2 border-y border-slate-200 dark:border-slate-800">
                        <span class="text-3xl font-black font-mono text-slate-900 dark:text-white">${{ number_format($plan->price_monthly, 0, ',', '.') }}</span>
                        <span class="text-xs text-slate-400 font-semibold">/ mes COP</span>
                    </div>

                    <!-- Limits -->
                    <div class="space-y-2 text-xs text-slate-600 dark:text-slate-300">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Usuarios & Cajeros:</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ $plan->max_users }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Sedes / Sucursales:</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ $plan->max_branches }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Límite de Productos:</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ number_format($plan->max_products) }}</span>
                        </div>
                    </div>

                    <!-- Features -->
                    @if(!empty($plan->features))
                        <div class="pt-2">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Características:</p>
                            <ul class="space-y-1 text-xs text-slate-600 dark:text-slate-300">
                                @foreach((array) $plan->features as $feat)
                                    <li class="flex items-center gap-1.5">
                                        <span class="text-purple-500 font-bold">✓</span>
                                        <span>{{ $feat }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <div class="mt-6 pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center gap-2">
                    <button type="button" @click="openEditModal({{ json_encode($plan) }})" class="flex-1 py-2 px-3 rounded-xl bg-purple-50 hover:bg-purple-100 dark:bg-purple-950/60 dark:hover:bg-purple-900/60 text-purple-700 dark:text-purple-300 font-bold text-xs transition-colors text-center">
                        ✏️ Editar
                    </button>
                    <form method="POST" action="{{ route('superadmin.plans.toggle', $plan) }}">
                        @csrf
                        <button type="submit" class="py-2 px-3 rounded-xl {{ $plan->is_active ? 'bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400' : 'bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400' }} font-bold text-xs transition-colors">
                            {{ $plan->is_active ? 'Desactivar' : 'Activar' }}
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-12 text-slate-400">
                No hay planes registrados en el sistema.
            </div>
        @endforelse
    </div>

    <!-- Create / Edit Plan Modal -->
    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div @click.away="showModal = false" class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200 dark:border-slate-800 shadow-2xl w-full max-w-lg p-6 space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800">
                <h3 class="text-lg font-black text-slate-900 dark:text-white" x-text="isEdit ? 'Editar Plan SaaS' : 'Nuevo Plan SaaS'"></h3>
                <button type="button" @click="showModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-xl font-bold">&times;</button>
            </div>

            <form :action="isEdit ? '{{ url('/superadmin/plans') }}/' + form.id : '{{ route('superadmin.plans.store') }}'" method="POST" class="space-y-4">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nombre del Plan *</label>
                    <input type="text" name="name" x-model="form.name" required class="input-touch text-xs w-full" placeholder="Ej: Plan Pro Bar & Licorera">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Slug Identificador</label>
                        <input type="text" name="slug" x-model="form.slug" class="input-touch text-xs w-full" placeholder="ej: pro (auto si se deja vacío)">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Precio Mensual COP *</label>
                        <input type="number" step="1000" name="price_monthly" x-model="form.price_monthly" required class="input-touch text-xs w-full font-mono font-bold" placeholder="129000">
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Máx. Usuarios</label>
                        <input type="number" name="max_users" x-model="form.max_users" required min="1" class="input-touch text-xs w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Máx. Sedes</label>
                        <input type="number" name="max_branches" x-model="form.max_branches" required min="1" class="input-touch text-xs w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Máx. Productos</label>
                        <input type="number" name="max_products" x-model="form.max_products" required min="10" class="input-touch text-xs w-full">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Características (una por línea)</label>
                    <textarea name="features" x-model="form.features_text" rows="4" class="input-touch text-xs w-full font-mono" placeholder="Hasta 10 usuarios&#10;Mesas ilimitadas&#10;WhatsApp gratis&#10;Backups automáticos"></textarea>
                </div>

                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" name="is_active" id="modal_is_active" value="1" x-model="form.is_active" class="rounded text-purple-600 focus:ring-purple-500 w-4 h-4">
                    <label for="modal_is_active" class="text-xs font-bold text-slate-700 dark:text-slate-300">Plan activo para nuevas licoreras</label>
                </div>

                <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex justify-end gap-2">
                    <button type="button" @click="showModal = false" class="btn-touch py-2.5 px-4 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-touch py-2.5 px-5 bg-purple-600 hover:bg-purple-500 text-white text-xs font-black rounded-xl shadow-lg shadow-purple-600/30">
                        Guardar Plan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function plansManager() {
    return {
        showModal: false,
        isEdit: false,
        form: {
            id: null,
            name: '',
            slug: '',
            price_monthly: 99000,
            max_users: 5,
            max_branches: 1,
            max_products: 1000,
            features_text: '',
            is_active: true
        },

        openCreateModal() {
            this.isEdit = false;
            this.form = {
                id: null,
                name: '',
                slug: '',
                price_monthly: 99000,
                max_users: 5,
                max_branches: 1,
                max_products: 1000,
                features_text: "Venta Rápida POS\nControl de Stock y Kardex\nTickets por WhatsApp Gratis\nArqueo a ciegas",
                is_active: true
            };
            this.showModal = true;
        },

        openEditModal(plan) {
            this.isEdit = true;
            this.form = {
                id: plan.id,
                name: plan.name,
                slug: plan.slug,
                price_monthly: plan.price_monthly,
                max_users: plan.max_users,
                max_branches: plan.max_branches,
                max_products: plan.max_products,
                features_text: Array.isArray(plan.features) ? plan.features.join("\n") : '',
                is_active: Boolean(plan.is_active)
            };
            this.showModal = true;
        }
    }
}
</script>
@endsection
