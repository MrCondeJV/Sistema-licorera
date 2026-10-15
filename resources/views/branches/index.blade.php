@extends('layouts.app', ['title' => 'Gestión de Sucursales y Sedes'])

@section('content')
<div class="h-full flex flex-col space-y-6 max-w-6xl mx-auto" x-data="branchesManager()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-black text-slate-900 dark:text-white">Sucursales & Sedes Multi-Sucursal</h2>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                    {{ $currentCount }} de {{ $maxBranches === null ? '∞ Ilimitadas' : $maxBranches }} Sedes
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Administra las sedes de {{ $tenant->name }}, su inventario independiente y transferencias entre locales</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="openTransferModal()" class="btn-touch py-2.5 px-4 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-750 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700 text-xs font-semibold rounded-xl shadow-xs flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" /></svg>
                <span>Transferir Mercancía</span>
            </button>

            @if($canCreate)
                <button type="button" @click="openCreateModal()" class="btn-touch py-2.5 px-4 bg-slate-900 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-900 text-xs font-semibold rounded-xl shadow-xs flex items-center gap-2">
                    <span>+ Nueva Sucursal</span>
                </button>
            @else
                <a href="{{ route('billing.index') }}" class="btn-touch py-2.5 px-4 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-semibold rounded-xl shadow-xs flex items-center gap-2" title="Límite de sucursales alcanzado">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    <span>Ampliar Plan</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Plan Limit Banner if at capacity -->
    @if(!$canCreate)
        <div class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/20 border border-amber-300 dark:border-amber-700/50 flex items-center justify-between text-xs text-amber-800 dark:text-amber-300 shadow-xs">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                <div>
                    <span class="font-bold block">Has alcanzado el límite de sucursales de tu plan actual</span>
                    <span>Tu plan permite <strong>{{ $maxBranches }} sucursal(es)</strong> y actualmente tienes <strong>{{ $currentCount }} registradas</strong>. Actualiza a un plan superior para habilitar más sedes.</span>
                </div>
            </div>
            <a href="{{ route('billing.index') }}" class="px-3.5 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-semibold whitespace-nowrap">
                Ver Planes Wompi
            </a>
        </div>
    @endif

    <!-- Branches Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($branches as $b)
            @php
                $isCurrent = ($currentBranch->id ?? 0) === $b->id;
            @endphp
            <div class="card-night p-5 flex flex-col justify-between relative {{ $isCurrent ? 'ring-2 ring-slate-900 dark:ring-white shadow-xs' : '' }}">
                <div class="space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ $b->name }}</h3>
                                @if($b->is_main)
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold uppercase tracking-wider bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">
                                        Principal
                                    </span>
                                @endif
                            </div>
                            <span class="font-mono text-[11px] text-slate-400 font-semibold">Código: {{ $b->code ?? 'N/A' }}</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold uppercase tracking-wider {{ $b->is_active ? 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border border-slate-200 dark:border-slate-700' : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400 border border-rose-200 dark:border-rose-900' }}">
                            {{ $b->is_active ? 'Activa' : 'Inactiva' }}
                        </span>
                    </div>

                    <div class="text-xs text-slate-500 dark:text-slate-400 space-y-1">
                        <p class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                            <span class="truncate">{{ $b->address ?? 'Sin dirección' }}</span>
                        </p>
                        <p class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                            <span>{{ $b->phone ?? 'Sin teléfono' }}</span>
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-2 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 text-center text-xs">
                        <div>
                            <span class="block font-bold text-slate-900 dark:text-white">{{ $b->cash_registers_count }}</span>
                            <span class="text-[10px] text-slate-400 uppercase font-semibold">Cajas</span>
                        </div>
                        <div>
                            <span class="block font-bold text-slate-900 dark:text-white">{{ $b->tables_count }}</span>
                            <span class="text-[10px] text-slate-400 uppercase font-semibold">Mesas</span>
                        </div>
                        <div>
                            <span class="block font-bold text-slate-900 dark:text-white">{{ $b->users_count }}</span>
                            <span class="text-[10px] text-slate-400 uppercase font-semibold">Personal</span>
                        </div>
                    </div>
                </div>

                <div class="pt-4 mt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                    @if($isCurrent)
                        <span class="text-xs font-semibold text-slate-900 dark:text-white flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Sede Activa
                        </span>
                    @else
                        @if($b->is_active)
                            <form action="{{ route('branch.switch', $b) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn-touch py-1.5 px-3 rounded-lg bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold transition-all">
                                    Operar aquí
                                </button>
                            </form>
                        @else
                            <span class="text-xs text-slate-400 italic">Desactivada</span>
                        @endif
                    @endif

                    <div class="flex items-center gap-1">
                        <button type="button" @click="openEditModal({{ json_encode($b) }})" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800" title="Editar sucursal">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                        </button>
                        @if(!$b->is_main)
                            <form action="{{ route('branches.toggle', $b) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas cambiar el estado de esta sucursal?');">
                                @csrf
                                <button type="submit" class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40" title="{{ $b->is_active ? 'Desactivar' : 'Activar' }}">
                                    @if($b->is_active)
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    @endif
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Recent Stock Transfers -->
    <div class="card-night p-5 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">Historial de Transferencias entre Sedes</h3>
                <p class="text-xs text-slate-400">Trazabilidad de movimientos de inventario transferido</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs min-w-[700px]">
                <thead class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-2">Fecha</th>
                        <th class="py-3 px-2">Comprobante</th>
                        <th class="py-3 px-2">Origen</th>
                        <th class="py-3 px-2">Destino</th>
                        <th class="py-3 px-2">Productos Transferidos</th>
                        <th class="py-3 px-2">Responsable</th>
                        <th class="py-3 px-2 text-right">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($transfers as $trf)
                        <tr>
                            <td class="py-3 px-2 font-mono text-slate-600 dark:text-slate-300">{{ $trf->transferred_at->format('d/m/Y h:i A') }}</td>
                            <td class="py-3 px-2 font-mono font-bold text-slate-900 dark:text-white">{{ $trf->transfer_number }}</td>
                            <td class="py-3 px-2 font-bold text-rose-600">{{ $trf->fromBranch->name ?? 'N/A' }}</td>
                            <td class="py-3 px-2 font-bold text-emerald-600">{{ $trf->toBranch->name ?? 'N/A' }}</td>
                            <td class="py-3 px-2">
                                <ul class="space-y-0.5">
                                    @foreach($trf->items as $ti)
                                        <li class="font-mono text-[11px]">
                                            <strong>{{ (float)$ti->quantity }}</strong> {{ $ti->product->name ?? 'Producto' }}
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="py-3 px-2 text-slate-500">{{ $trf->user->name ?? 'Sistema' }}</td>
                            <td class="py-3 px-2 text-right">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">
                                    {{ $trf->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">No se han registrado transferencias de inventario entre sedes aún.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Create Branch -->
    <div x-show="showCreateModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showCreateModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md card-night p-6 shadow-2xl rounded-3xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-base font-black text-slate-900 dark:text-white">Nueva Sucursal / Sede</h3>
                    <button type="button" @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
                </div>
                <form action="{{ route('branches.store') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nombre de la Sucursal *</label>
                        <input type="text" name="name" required placeholder="Ej: Sede Poblado / Barra Norte" class="input-touch text-xs w-full">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Código Identificador</label>
                            <input type="text" name="code" placeholder="POB / NOR" maxlength="10" class="input-touch text-xs w-full uppercase">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Teléfono</label>
                            <input type="text" name="phone" placeholder="300 123 4567" class="input-touch text-xs w-full">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Dirección Física</label>
                        <input type="text" name="address" placeholder="Calle 10 # 40-20" class="input-touch text-xs w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Correo Electrónico (Opcional)</label>
                        <input type="email" name="email" placeholder="sede@licorera.com" class="input-touch text-xs w-full">
                    </div>
                    <div class="pt-2 flex justify-end gap-2">
                        <button type="button" @click="showCreateModal = false" class="btn-touch py-2.5 px-4 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold rounded-xl">Cancelar</button>
                        <button type="submit" class="btn-touch py-2.5 px-4 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black rounded-xl shadow-lg shadow-emerald-600/30">Guardar Sucursal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Branch -->
    <div x-show="showEditModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showEditModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-md card-night p-6 shadow-2xl rounded-3xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-base font-black text-slate-900 dark:text-white">Editar Sucursal</h3>
                    <button type="button" @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
                </div>
                <form :action="'{{ url('/branches') }}/' + editForm.id" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Nombre de la Sucursal *</label>
                        <input type="text" name="name" x-model="editForm.name" required class="input-touch text-xs w-full">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Código</label>
                            <input type="text" name="code" x-model="editForm.code" maxlength="10" class="input-touch text-xs w-full uppercase">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Teléfono</label>
                            <input type="text" name="phone" x-model="editForm.phone" class="input-touch text-xs w-full">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Dirección</label>
                        <input type="text" name="address" x-model="editForm.address" class="input-touch text-xs w-full">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Email</label>
                        <input type="email" name="email" x-model="editForm.email" class="input-touch text-xs w-full">
                    </div>
                    <div class="pt-2 flex justify-end gap-2">
                        <button type="button" @click="showEditModal = false" class="btn-touch py-2.5 px-4 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold rounded-xl">Cancelar</button>
                        <button type="submit" class="btn-touch py-2.5 px-4 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black rounded-xl">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Transfer Inventory -->
    <div x-show="showTransferModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showTransferModal = false"></div>
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-lg card-night p-6 shadow-2xl rounded-3xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                    <h3 class="text-base font-black text-slate-900 dark:text-white">Transferencia de Mercancía entre Sedes</h3>
                    <button type="button" @click="showTransferModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
                </div>
                <form action="{{ route('branches.transfer') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-rose-600 mb-1">Sucursal Origen (Descuenta) *</label>
                            <select name="from_branch_id" required class="input-touch text-xs w-full">
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-emerald-600 mb-1">Sucursal Destino (Incrementa) *</label>
                            <select name="to_branch_id" required class="input-touch text-xs w-full">
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}" {{ $loop->last ? 'selected' : '' }}>{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Productos a Transferir *</label>
                            <button type="button" @click="addTransferItem()" class="text-xs font-bold text-emerald-600 hover:underline">+ Agregar producto</button>
                        </div>
                        <div class="space-y-2 max-h-48 overflow-y-auto p-1">
                            <template x-for="(item, idx) in transferItems" :key="idx">
                                <div class="flex items-center gap-2">
                                    <select :name="'items[' + idx + '][product_id]'" x-model="item.product_id" required class="input-touch text-xs flex-1">
                                        <option value="">Seleccionar Producto...</option>
                                        @foreach($products as $p)
                                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" :name="'items[' + idx + '][quantity]'" x-model="item.quantity" required min="1" step="1" placeholder="Cant" class="input-touch text-xs w-20">
                                    <button type="button" @click="removeTransferItem(idx)" class="p-2 text-rose-500 hover:text-rose-700" :disabled="transferItems.length <= 1">&times;</button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Notas de Envío / Guía</label>
                        <input type="text" name="notes" placeholder="Ej: Traslado de fin de semana para abastecer barra" class="input-touch text-xs w-full">
                    </div>

                    <div class="pt-2 flex justify-end gap-2">
                        <button type="button" @click="showTransferModal = false" class="btn-touch py-2.5 px-4 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-bold rounded-xl">Cancelar</button>
                        <button type="submit" class="btn-touch py-2.5 px-4 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black rounded-xl shadow-lg shadow-emerald-600/30">Ejecutar Transferencia</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function branchesManager() {
    return {
        showCreateModal: false,
        showEditModal: false,
        showTransferModal: false,
        editForm: {
            id: null,
            name: '',
            code: '',
            phone: '',
            address: '',
            email: ''
        },
        transferItems: [
            { product_id: '', quantity: 1 }
        ],
        openCreateModal() {
            this.showCreateModal = true;
        },
        openEditModal(branch) {
            this.editForm = {
                id: branch.id,
                name: branch.name,
                code: branch.code || '',
                phone: branch.phone || '',
                address: branch.address || '',
                email: branch.email || ''
            };
            this.showEditModal = true;
        },
        openTransferModal() {
            this.transferItems = [{ product_id: '', quantity: 1 }];
            this.showTransferModal = true;
        },
        addTransferItem() {
            this.transferItems.push({ product_id: '', quantity: 1 });
        },
        removeTransferItem(idx) {
            if (this.transferItems.length > 1) {
                this.transferItems.splice(idx, 1);
            }
        }
    }
}
</script>
@endsection
