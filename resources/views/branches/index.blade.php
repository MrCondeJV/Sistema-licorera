@extends('layouts.app', ['title' => 'Gestión de Sucursales y Sedes'])

@section('content')
<div class="h-full flex flex-col space-y-6 max-w-6xl mx-auto" x-data="branchesManager()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-black text-slate-900 dark:text-white">Sucursales & Sedes Multi-Sucursal</h2>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-black uppercase tracking-wider bg-purple-500/10 text-purple-600 dark:text-purple-400 border border-purple-500/20">
                    {{ $currentCount }} de {{ $maxBranches === null ? '∞ Ilimitadas' : $maxBranches }} Sedes
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Administra las sedes de {{ $tenant->name }}, su inventario independiente y transferencias entre locales</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" @click="openTransferModal()" class="btn-touch py-2.5 px-4 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black rounded-xl shadow-md flex items-center gap-2">
                <span>🔄 Transferir Mercancía</span>
            </button>

            @if($canCreate)
                <button type="button" @click="openCreateModal()" class="btn-touch py-2.5 px-4 bg-slate-900 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-900 text-xs font-black rounded-xl shadow-md flex items-center gap-2">
                    <span>+ Nueva Sucursal</span>
                </button>
            @else
                <a href="{{ route('billing.index') }}" class="btn-touch py-2.5 px-4 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-black rounded-xl shadow-md flex items-center gap-2" title="Límite de sucursales alcanzado">
                    <span>⚡ Ampliar Plan</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Plan Limit Banner if at capacity -->
    @if(!$canCreate)
        <div class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-between text-xs text-amber-800 dark:text-amber-300 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="text-xl">⚠️</span>
                <div>
                    <span class="font-bold block">Has alcanzado el límite de sucursales de tu plan actual</span>
                    <span>Tu plan permite <strong>{{ $maxBranches }} sucursal(es)</strong> y actualmente tienes <strong>{{ $currentCount }} registradas</strong>. Actualiza a un plan superior para habilitar más sedes.</span>
                </div>
            </div>
            <a href="{{ route('billing.index') }}" class="px-3.5 py-1.5 rounded-xl bg-amber-500 text-slate-950 font-black whitespace-nowrap">
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
            <div class="card-night p-5 flex flex-col justify-between relative {{ $isCurrent ? 'ring-2 ring-emerald-500 shadow-emerald-500/10' : '' }}">
                <div class="space-y-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-black text-slate-900 dark:text-white">{{ $b->name }}</h3>
                                @if($b->is_main)
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 border border-purple-300 dark:border-purple-800">
                                        Principal
                                    </span>
                                @endif
                            </div>
                            <span class="font-mono text-[11px] text-slate-400 font-bold">Código: {{ $b->code ?? 'N/A' }}</span>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider {{ $b->is_active ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300' }}">
                            {{ $b->is_active ? 'Activa' : 'Inactiva' }}
                        </span>
                    </div>

                    <div class="text-xs text-slate-500 dark:text-slate-400 space-y-1">
                        <p class="flex items-center gap-1.5">
                            <span>📍</span> <span class="truncate">{{ $b->address ?? 'Sin dirección' }}</span>
                        </p>
                        <p class="flex items-center gap-1.5">
                            <span>📞</span> <span>{{ $b->phone ?? 'Sin teléfono' }}</span>
                        </p>
                    </div>

                    <div class="grid grid-cols-3 gap-2 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/60 text-center text-xs">
                        <div>
                            <span class="block font-black text-slate-900 dark:text-white">{{ $b->cash_registers_count }}</span>
                            <span class="text-[10px] text-slate-400 uppercase font-bold">Cajas</span>
                        </div>
                        <div>
                            <span class="block font-black text-slate-900 dark:text-white">{{ $b->tables_count }}</span>
                            <span class="text-[10px] text-slate-400 uppercase font-bold">Mesas</span>
                        </div>
                        <div>
                            <span class="block font-black text-slate-900 dark:text-white">{{ $b->users_count }}</span>
                            <span class="text-[10px] text-slate-400 uppercase font-bold">Personal</span>
                        </div>
                    </div>
                </div>

                <div class="pt-4 mt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between gap-2">
                    @if($isCurrent)
                        <span class="text-xs font-black text-emerald-600 dark:text-emerald-400 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                            Sede Activa
                        </span>
                    @else
                        @if($b->is_active)
                            <form action="{{ route('branch.switch', $b) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn-touch py-1.5 px-3 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/20 text-xs font-bold transition-all">
                                    Operar aquí
                                </button>
                            </form>
                        @else
                            <span class="text-xs text-slate-400 italic">Desactivada</span>
                        @endif
                    @endif

                    <div class="flex items-center gap-1">
                        <button type="button" @click="openEditModal({{ json_encode($b) }})" class="p-2 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800" title="Editar sucursal">
                            ✏️
                        </button>
                        @if(!$b->is_main)
                            <form action="{{ route('branches.toggle', $b) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas cambiar el estado de esta sucursal?');">
                                @csrf
                                <button type="submit" class="p-2 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40" title="{{ $b->is_active ? 'Desactivar' : 'Activar' }}">
                                    {{ $b->is_active ? '⏸️' : '▶️' }}
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
