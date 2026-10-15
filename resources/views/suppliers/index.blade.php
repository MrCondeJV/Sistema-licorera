@extends('layouts.app', ['title' => 'Proveedores'])

@section('content')
<div class="h-full flex flex-col space-y-4"
     x-data="{
         showNewModal: false,
         showEditModal: false,
         editSupplier: {
             id: null,
             name: '',
             business_name: '',
             tax_id: '',
             phone: '',
             email: '',
             address: '',
             contact_person: ''
         },
         openEdit(supplier) {
             this.editSupplier = { ...supplier };
             this.showEditModal = true;
         }
     }">

    <!-- Header & Navigation Tabs -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('purchases.index') }}" class="px-3 py-1 rounded-lg text-xs font-bold transition-all text-slate-500 hover:text-slate-800 dark:hover:text-white bg-slate-100 dark:bg-slate-800">
                    &larr; Compras
                </a>
                <span class="text-xs text-slate-400">/</span>
                <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Directorio de Proveedores</span>
            </div>
            <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Proveedores y Distribuidores</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Gestión de empresas mayoristas, distribuidores y contactos comerciales</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('purchases.index') }}" class="btn-touch py-2.5 px-4 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                <span>Ver Compras</span>
            </a>
            <button type="button" @click="showNewModal = true"
                    class="btn-touch py-2.5 px-4 bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:hover:bg-slate-100 dark:text-slate-900 border border-slate-800 dark:border-white text-xs font-semibold rounded-xl flex items-center gap-2 shadow-xs transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                <span>+ NUEVO PROVEEDOR</span>
            </button>
        </div>
    </div>

    <!-- Quick Stat Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
        <div class="card-night p-3.5 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Total Proveedores</span>
                <span class="text-xl font-black text-slate-900 dark:text-white">{{ $totalSuppliers }}</span>
            </div>
            <div class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-600 dark:text-slate-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            </div>
        </div>

        <div class="card-night p-3.5 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Proveedores Activos</span>
                <span class="text-xl font-black text-emerald-600 dark:text-emerald-400">{{ $activeSuppliers }}</span>
            </div>
            <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            </div>
        </div>

        <div class="card-night p-3.5 col-span-2 sm:col-span-1 flex items-center justify-between">
            <div>
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block">Nueva Factura</span>
                <a href="{{ route('purchases.create') }}" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline inline-flex items-center gap-1 mt-0.5">
                    <span>Registrar Entrada &rarr;</span>
                </a>
            </div>
            <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div class="card-night p-3 flex flex-col sm:flex-row gap-2.5 items-stretch sm:items-center justify-between">
        <form method="GET" action="{{ route('suppliers.index') }}" class="flex-1 flex items-center gap-2">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Buscar por nombre, NIT, teléfono, correo o contacto..."
                       class="input-night w-full pl-9 text-xs py-2">
            </div>
            <button type="submit" class="btn-touch px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-bold">
                Buscar
            </button>
            @if($search !== '')
                <a href="{{ route('suppliers.index') }}" class="px-2 text-xs text-rose-500 font-bold hover:underline">
                    Limpiar
                </a>
            @endif
        </form>
    </div>

    <!-- Suppliers Table -->
    <div class="card-night p-4 flex-1 overflow-hidden flex flex-col">
        <div class="flex-1 overflow-y-auto">
            <div class="overflow-x-auto min-w-full">
                <table class="w-full text-left text-xs min-w-[750px]">
                    <thead class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-3">Proveedor / Razón Social</th>
                            <th class="py-3 px-3">NIT / Identificación</th>
                            <th class="py-3 px-3">Contacto</th>
                            <th class="py-3 px-3">Dirección</th>
                            <th class="py-3 px-3 text-center">Compras</th>
                            <th class="py-3 px-3 text-center">Estado</th>
                            <th class="py-3 px-3 text-right sticky right-0 bg-white dark:bg-slate-900 z-10 border-l border-slate-100 dark:border-slate-800 shadow-[-6px_0_10px_-2px_rgba(0,0,0,0.06)]">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($suppliers as $supplier)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                                <td class="py-3 px-3">
                                    <div class="font-bold text-slate-900 dark:text-white text-sm">{{ $supplier->name }}</div>
                                    @if($supplier->business_name && $supplier->business_name !== $supplier->name)
                                        <div class="text-[11px] text-slate-400 dark:text-slate-500">{{ $supplier->business_name }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-3 font-mono text-slate-700 dark:text-slate-300">
                                    {{ $supplier->tax_id ?? 'Sin NIT' }}
                                </td>
                                <td class="py-3 px-3">
                                    @if($supplier->phone)
                                        <div class="font-mono text-slate-700 dark:text-slate-300">{{ $supplier->phone }}</div>
                                    @endif
                                    @if($supplier->email)
                                        <div class="text-[11px] text-slate-400 dark:text-slate-500 truncate max-w-[160px]">{{ $supplier->email }}</div>
                                    @endif
                                    @if($supplier->contact_person)
                                        <div class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold">{{ $supplier->contact_person }}</div>
                                    @endif
                                    @if(!$supplier->phone && !$supplier->email && !$supplier->contact_person)
                                        <span class="text-slate-400 italic">Sin datos</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-slate-600 dark:text-slate-400 max-w-[180px] truncate">
                                    {{ $supplier->address ?? '—' }}
                                </td>
                                <td class="py-3 px-3 text-center font-bold font-mono text-slate-700 dark:text-slate-300">
                                    {{ $supplier->purchases_count }}
                                </td>
                                <td class="py-3 px-3 text-center">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $supplier->is_active ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-700' : 'bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-700' }}">
                                        {{ $supplier->is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-right sticky right-0 bg-white dark:bg-slate-900 z-10 border-l border-slate-100 dark:border-slate-800 shadow-[-6px_0_10px_-2px_rgba(0,0,0,0.06)]">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button"
                                                @click="openEdit({{ json_encode($supplier) }})"
                                                class="px-2.5 py-1.5 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium transition-colors shadow-xs">
                                            Editar
                                        </button>

                                        <form action="{{ route('suppliers.toggle', $supplier->id) }}" method="POST">
                                            @csrf
                                            <button type="submit"
                                                    class="px-2.5 py-1.5 text-xs font-medium rounded-lg transition-colors border {{ $supplier->is_active ? 'border-slate-200 dark:border-slate-700 text-slate-500 hover:text-rose-600 hover:bg-rose-50/50 dark:text-slate-400 dark:hover:text-rose-300 dark:hover:bg-rose-950/20 shadow-xs' : 'bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:hover:bg-slate-100 dark:text-slate-900 border border-slate-800 dark:border-white shadow-xs' }}">
                                                {{ $supplier->is_active ? 'Desactivar' : 'Activar' }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                    <div class="text-sm font-semibold mb-1">No se encontraron proveedores</div>
                                    <p class="text-xs">Registra tu primer proveedor para asociarlo a tus compras e inventario.</p>
                                    <button type="button" @click="showNewModal = true" class="mt-3 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-md">
                                        + Agregar Proveedor Ahora
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pt-3 border-t border-slate-200 dark:border-slate-800">
            {{ $suppliers->links() }}
        </div>
    </div>

    <!-- Modal: Nuevo Proveedor -->
    <div x-show="showNewModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" style="display: none;">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-black text-slate-900 dark:text-white">Registrar Nuevo Proveedor</h3>
                <button type="button" @click="showNewModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Ingresa la información comercial del proveedor o distribuidor</p>

            <form action="{{ route('suppliers.store') }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Nombre Comercial / Distribuidor *</label>
                    <input type="text" name="name" required placeholder="Ej: Bavaria S.A., Fábrica de Licores..." autofocus
                           class="input-night w-full text-xs font-bold">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Razón Social</label>
                        <input type="text" name="business_name" placeholder="Ej: Bavaria & Cía..."
                               class="input-night w-full text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">NIT / Cédula</label>
                        <input type="text" name="tax_id" placeholder="Ej: 900.123.456-7"
                               class="input-night w-full text-xs font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Teléfono / Celular</label>
                        <input type="text" name="phone" placeholder="Ej: 3101234567"
                               class="input-night w-full text-xs font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Correo Electrónico</label>
                        <input type="email" name="email" placeholder="pedidos@proveedor.com"
                               class="input-night w-full text-xs">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Dirección de Despacho / Bodega</label>
                    <input type="text" name="address" placeholder="Ej: Calle 45 # 20 - 15, Medellín"
                           class="input-night w-full text-xs">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Persona de Contacto / Asesor Comercial</label>
                    <input type="text" name="contact_person" placeholder="Ej: Juan Pérez (Asesor Bavaria)"
                           class="input-night w-full text-xs">
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="button" @click="showNewModal = false" class="btn-touch flex-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl">Cancelar</button>
                    <button type="submit" class="btn-touch flex-1 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-lg shadow-emerald-600/30 rounded-xl">GUARDAR PROVEEDOR</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Editar Proveedor -->
    <div x-show="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" style="display: none;">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-md w-full shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-base font-black text-slate-900 dark:text-white">Editar Proveedor</h3>
                <button type="button" @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Actualizar datos de contacto y facturación</p>

            <form :action="'/suppliers/' + editSupplier.id" method="POST" class="space-y-3">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Nombre Comercial *</label>
                    <input type="text" name="name" x-model="editSupplier.name" required
                           class="input-night w-full text-xs font-bold">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Razón Social</label>
                        <input type="text" name="business_name" x-model="editSupplier.business_name"
                               class="input-night w-full text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">NIT / Cédula</label>
                        <input type="text" name="tax_id" x-model="editSupplier.tax_id"
                               class="input-night w-full text-xs font-mono">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Teléfono</label>
                        <input type="text" name="phone" x-model="editSupplier.phone"
                               class="input-night w-full text-xs font-mono">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Correo</label>
                        <input type="email" name="email" x-model="editSupplier.email"
                               class="input-night w-full text-xs">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Dirección</label>
                    <input type="text" name="address" x-model="editSupplier.address"
                           class="input-night w-full text-xs">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Persona de Contacto</label>
                    <input type="text" name="contact_person" x-model="editSupplier.contact_person"
                           class="input-night w-full text-xs">
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="button" @click="showEditModal = false" class="btn-touch flex-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl">Cancelar</button>
                    <button type="submit" class="btn-touch flex-1 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black shadow-lg shadow-emerald-600/30 rounded-xl">ACTUALIZAR</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
