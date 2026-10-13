@extends('layouts.app', ['title' => 'Clientes y Cartera'])

@section('content')
<div class="h-full flex flex-col space-y-4" x-data="{ showNewCustomerModal: false }">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-black text-white">Clientes y Cartera (Fiados)</h2>
            <p class="text-xs text-slate-400 mt-0.5">Control de clientes registrados, límites de crédito y saldos pendientes</p>
        </div>
        <button type="button" @click="showNewCustomerModal = true" class="btn-touch py-2 px-4 bg-gradient-to-r from-neon-green to-emerald-400 text-night-950 text-xs font-black shadow-lg shadow-neon-green/20">
            + NUEVO CLIENTE
        </button>
    </div>

    <!-- Table -->
    <div class="card-night p-4 flex-1 overflow-hidden flex flex-col">
        <div class="flex-1 overflow-y-auto">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-night-800 text-slate-400 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-2">Cliente</th>
                        <th class="py-3 px-2">Teléfono</th>
                        <th class="py-3 px-2">Identificación</th>
                        <th class="py-3 px-2 text-right">Cupo de Crédito</th>
                        <th class="py-3 px-2 text-right">Deuda Actual</th>
                        <th class="py-3 px-2 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-night-800">
                    @forelse($customers as $c)
                        <tr>
                            <td class="py-3 px-2 font-bold text-white text-sm">{{ $c->name }}</td>
                            <td class="py-3 px-2 font-mono text-slate-300">{{ $c->phone ?? 'Sin teléfono' }}</td>
                            <td class="py-3 px-2 font-mono text-slate-400">{{ $c->tax_id ?? 'N/A' }}</td>
                            <td class="py-3 px-2 text-right font-mono text-slate-300">${{ number_format($c->credit_limit, 0, ',', '.') }}</td>
                            <td class="py-3 px-2 text-right font-mono font-black {{ $c->current_debt > 0 ? 'text-amber-400' : 'text-emerald-400' }}">
                                ${{ number_format($c->current_debt, 0, ',', '.') }}
                            </td>
                            <td class="py-3 px-2 text-right">
                                <a href="{{ route('customers.debts', $c->id) }}"
                                   class="px-3 py-1.5 bg-night-800 hover:bg-night-750 text-neon-cyan border border-night-750 font-bold text-xs rounded-xl shadow-md">
                                    Ver Fiados y Abonos &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-500">No hay clientes registrados aún.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-3 border-t border-night-800">
            {{ $customers->links() }}
        </div>
    </div>

    <!-- Modal: New Customer -->
    <div x-show="showNewCustomerModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm" style="display: none;">
        <div class="bg-night-900 border border-night-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl">
            <h3 class="text-base font-black text-white mb-1">Registrar Nuevo Cliente</h3>
            <p class="text-xs text-slate-400 mb-4">Información básica para control de cartera</p>

            <form action="{{ route('customers.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Nombre Completo *</label>
                    <input type="text" name="name" required placeholder="Ej: Camilo Torres" autofocus
                           class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3 py-2 text-xs focus:border-neon-green focus:outline-none">
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Teléfono / WhatsApp</label>
                    <input type="text" name="phone" placeholder="Ej: 300 123 4567"
                           class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3 py-2 text-xs focus:border-neon-green focus:outline-none">
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Cédula / NIT</label>
                    <input type="text" name="tax_id" placeholder="Ej: 10203040"
                           class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3 py-2 text-xs focus:border-neon-green focus:outline-none">
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-slate-300 mb-1">Límite de Crédito ($ COP)</label>
                    <input type="number" name="credit_limit" min="0" step="10000" value="0"
                           class="w-full bg-night-850 border border-night-750 text-white font-mono rounded-xl px-3 py-2 text-xs focus:border-neon-green focus:outline-none">
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="showNewCustomerModal = false" class="btn-touch flex-1 bg-night-800 text-slate-300 text-xs font-bold">Cancelar</button>
                    <button type="submit" class="btn-touch flex-1 bg-neon-green text-night-950 text-xs font-black">GUARDAR</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
