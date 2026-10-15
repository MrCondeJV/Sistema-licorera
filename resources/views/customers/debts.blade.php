@extends('layouts.app', ['title' => 'Cartera: ' . $customer->name])

@section('content')
<div class="h-full flex flex-col space-y-4" x-data="{
    showNewDebtModal: false,
    showPaymentModal: false,
    showDetailModal: false,
    selectedDebt: null,
    openDetail(debt) {
        this.selectedDebt = debt;
        this.showDetailModal = true;
    },
    // New Fiado state
    debtMode: 'catalog',
    catalogItems: [],
    selectedProductId: '',
    selectedPresentationId: '',
    selectedQty: 1,
    manualAmount: '',
    manualNotes: '',
    deductStock: true,
    allProducts: {{ json_encode($products->map(function($p) {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'sale_price' => (float)$p->sale_price,
            'stock' => (float)$p->stock,
            'presentations' => $p->presentations->map(function($pr) {
                return [
                    'id' => $pr->id,
                    'name' => $pr->name,
                    'sale_price' => (float)$pr->sale_price,
                    'conversion_factor' => (int)$pr->conversion_factor,
                ];
            })->values()->toArray(),
        ];
    })) }},
    availablePresentations: [],
    onProductChange() {
        const p = this.allProducts.find(item => item.id == this.selectedProductId);
        if (p && p.presentations && p.presentations.length > 0) {
            this.availablePresentations = p.presentations;
            this.selectedPresentationId = p.presentations[0].id;
        } else {
            this.availablePresentations = [];
            this.selectedPresentationId = '';
        }
    },
    addItem() {
        const p = this.allProducts.find(item => item.id == this.selectedProductId);
        if (!p) return;
        let unitPrice = parseFloat(p.sale_price) || 0;
        let presName = 'Unidad';
        let presId = null;
        if (this.selectedPresentationId) {
            const pres = this.availablePresentations.find(pr => pr.id == this.selectedPresentationId);
            if (pres) {
                unitPrice = parseFloat(pres.sale_price) || unitPrice;
                presName = pres.name;
                presId = pres.id;
            }
        }
        const qty = parseFloat(this.selectedQty) || 1;
        const subtotal = qty * unitPrice;
        this.catalogItems.push({
            product_id: p.id,
            product_presentation_id: presId,
            name: p.name,
            presentation_name: presName,
            quantity: qty,
            unit_price: unitPrice,
            subtotal: subtotal
        });
        this.selectedQty = 1;
    },
    removeItem(index) {
        this.catalogItems.splice(index, 1);
    },
    get totalDebtAmount() {
        if (this.debtMode === 'catalog') {
            return this.catalogItems.reduce((acc, it) => acc + (parseFloat(it.subtotal) || 0), 0);
        }
        return parseFloat(this.manualAmount) || 0;
    },
    formatMoney(val) {
        return '$' + Math.round(val || 0).toLocaleString('es-CO');
    }
}">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="text-xl font-black text-slate-900 dark:text-white">{{ $customer->name }}</h2>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold {{ $customer->current_debt > 0 ? 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-700' : 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700' }}">
                    {{ $customer->current_debt > 0 ? 'Con Saldo Pendiente' : 'Al Día' }}
                </span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Tel: {{ $customer->phone ?? 'S/N' }} &bull; Cupo límite: ${{ number_format($customer->credit_limit, 0, ',', '.') }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('customers.index') }}" class="btn-touch px-3.5 py-2 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 text-xs font-medium rounded-xl shadow-xs transition-colors">
                &larr; Volver
            </a>
            <button type="button" @click="showNewDebtModal = true" class="btn-touch px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:hover:bg-slate-100 dark:text-slate-900 border border-slate-800 dark:border-white text-xs font-semibold rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>+ NUEVO FIADO</span>
            </button>
            @if($customer->current_debt > 0)
                <button type="button" @click="showPaymentModal = true" class="btn-touch px-3.5 py-2 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 text-xs font-medium rounded-xl shadow-xs transition-colors flex items-center gap-1.5">
                    <span>💵</span>
                    <span>REGISTRAR ABONO</span>
                </button>
            @endif
        </div>
    </div>

    <!-- Summary Balance Banner -->
    <div class="card-night p-4 border-amber-500/30 bg-amber-50/50 dark:bg-amber-950/15 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <span class="text-xs uppercase font-bold text-amber-800 dark:text-amber-300">Saldo Total Pendiente por Cobrar</span>
            <p class="text-2xl sm:text-3xl font-black font-mono text-amber-600 dark:text-amber-400 mt-0.5">${{ number_format($customer->current_debt, 0, ',', '.') }} COP</p>
        </div>
        <div class="text-left sm:text-right text-xs text-slate-500 dark:text-slate-400">
            <p>Cupo disponible: <strong class="text-slate-900 dark:text-white">${{ number_format(max(0, $customer->credit_limit - $customer->current_debt), 0, ',', '.') }}</strong></p>
        </div>
    </div>

    <!-- Debts & Abonos 2-Column Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 flex-1 overflow-hidden">
        <!-- Col 1: Debts / Fiados -->
        <div class="card-night p-4 flex flex-col overflow-hidden min-h-[300px]">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Historial de Fiados / Deudas</h3>
                <span class="text-[11px] text-slate-400">{{ $customer->debts->count() }} registro(s)</span>
            </div>
            <div class="flex-1 overflow-y-auto pr-1">
                <div class="overflow-x-auto min-w-full">
                    <table class="w-full text-left text-xs min-w-[500px]">
                        <thead class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase">
                            <tr>
                                <th class="py-2.5 px-2">Fecha / Hora</th>
                                <th class="py-2.5 px-2">Productos Vendidos / Concepto</th>
                                <th class="py-2.5 px-2 text-right">Total Deuda</th>
                                <th class="py-2.5 px-2 text-right">Pendiente</th>
                                <th class="py-2.5 px-2 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($customer->debts as $d)
                                @php
                                    $breakdown = $d->items_breakdown;
                                @endphp
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-850/50 transition-colors">
                                    <td class="py-2.5 px-2 text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                        <div class="font-mono font-medium">{{ $d->created_at->format('d/m/Y') }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ $d->created_at->format('h:i A') }}</div>
                                        <div class="text-[10px] text-slate-400 font-sans truncate max-w-[110px]" title="{{ $d->user->name ?? 'Sistema' }}">
                                            👤 {{ $d->user->name ?? 'Sistema' }}
                                        </div>
                                    </td>
                                    <td class="py-2.5 px-2 max-w-[240px]">
                                        @if($d->sale)
                                            <div class="flex items-center gap-1.5 mb-1">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 font-mono">
                                                    Venta #{{ $d->sale->sale_number }}
                                                </span>
                                                <a href="{{ route('pos.receipt', $d->sale->id) }}" target="_blank" class="text-[11px] text-slate-500 hover:text-slate-900 dark:hover:text-white font-medium underline" title="Ver comprobante de venta">Ticket 🖨️</a>
                                            </div>
                                        @endif

                                        @if(!empty($breakdown) && count($breakdown) > 0)
                                            <div class="space-y-1">
                                                @foreach(array_slice($breakdown, 0, 3) as $item)
                                                    <div class="text-xs font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-1">
                                                        <span class="text-slate-400">&bull;</span>
                                                        <span class="truncate">{{ (float)$item['quantity'] }}x {{ $item['product_name'] }}</span>
                                                        @if(!empty($item['presentation_name']) && $item['presentation_name'] !== 'Unidad')
                                                            <span class="text-[10px] text-slate-400 font-normal">({{ $item['presentation_name'] }})</span>
                                                        @endif
                                                        <span class="text-slate-500 font-mono text-[11px] shrink-0">(${{ number_format($item['subtotal'] ?? ($item['quantity'] * $item['unit_price']), 0, ',', '.') }})</span>
                                                    </div>
                                                @endforeach
                                                @if(count($breakdown) > 3)
                                                    <div class="text-[10px] text-slate-400 italic font-sans">
                                                        + {{ count($breakdown) - 3 }} producto(s) más...
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="text-xs font-medium text-slate-800 dark:text-slate-200 leading-tight">
                                                {{ $d->notes ?? 'Fiado manual' }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-2 text-right font-mono font-medium text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                        ${{ number_format($d->original_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-2 text-right font-mono font-bold whitespace-nowrap {{ $d->remaining_amount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                        ${{ number_format($d->remaining_amount, 0, ',', '.') }}
                                    </td>
                                    <td class="py-2.5 px-2 text-right whitespace-nowrap">
                                        <button type="button"
                                                @click="openDetail({{ json_encode([
                                                    'id' => $d->id,
                                                    'date' => $d->created_at->format('d/m/Y h:i A'),
                                                    'user' => $d->user->name ?? 'Sistema',
                                                    'sale_id' => $d->sale_id,
                                                    'sale_number' => $d->sale?->sale_number,
                                                    'receipt_url' => $d->sale_id ? route('pos.receipt', $d->sale_id) : null,
                                                    'original_amount' => (float)$d->original_amount,
                                                    'remaining_amount' => (float)$d->remaining_amount,
                                                    'status' => $d->status,
                                                    'notes' => $d->notes,
                                                    'items' => $breakdown
                                                ]) }})"
                                                class="px-2.5 py-1.5 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-lg text-xs font-medium transition-colors shadow-xs"
                                                title="Ver detalle completo de lo vendido">
                                            👁️ Ver Detalle
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-400 dark:text-slate-500 font-sans">No hay deudas registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Col 2: Abonos / Payments -->
        <div class="card-night p-4 flex flex-col overflow-hidden min-h-[300px]">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white">Historial de Abonos Recibidos</h3>
                <span class="text-[11px] text-slate-400">{{ $customer->payments->count() }} registro(s)</span>
            </div>
            <div class="flex-1 overflow-y-auto pr-1">
                <div class="overflow-x-auto min-w-full">
                    <table class="w-full text-left text-xs min-w-[360px]">
                        <thead class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase">
                            <tr>
                                <th class="py-2.5 px-2">Fecha</th>
                                <th class="py-2.5 px-2">Método</th>
                                <th class="py-2.5 px-2">Referencia / Atendido Por</th>
                                <th class="py-2.5 px-2 text-right">Monto Abonado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-mono">
                            @forelse($customer->payments as $p)
                                <tr>
                                    <td class="py-2.5 px-2 text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                        <div>{{ $p->created_at->format('d/m/Y') }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $p->created_at->format('h:i A') }}</div>
                                    </td>
                                    <td class="py-2.5 px-2 font-sans font-semibold text-slate-800 dark:text-slate-200 whitespace-nowrap">
                                        {{ $p->paymentMethod->name }}
                                    </td>
                                    <td class="py-2.5 px-2 font-sans text-slate-600 dark:text-slate-300">
                                        <div>{{ $p->reference ?? 'Sin comprobante' }}</div>
                                        <div class="text-[10px] text-slate-400">Recibió: {{ $p->user->name ?? 'Sistema' }}</div>
                                    </td>
                                    <td class="py-2.5 px-2 text-right font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                        +${{ number_format($p->amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-8 text-center text-slate-400 dark:text-slate-500 font-sans">No hay abonos registrados todavía.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Detalle Completo de Deuda / Fiado -->
    <div x-show="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" style="display: none;" @keydown.escape.window="showDetailModal = false">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800 mb-4">
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <span>Detalle de la Deuda</span>
                        <span class="text-xs font-mono font-normal text-slate-400" x-text="'#' + (selectedDebt?.id || '')"></span>
                    </h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Cliente: <strong class="text-slate-800 dark:text-slate-200">{{ $customer->name }}</strong></p>
                </div>
                <button type="button" @click="showDetailModal = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Meta details -->
            <div class="grid grid-cols-2 gap-3 mb-4 p-3 bg-slate-50 dark:bg-slate-850 rounded-xl text-xs">
                <div>
                    <span class="text-slate-400 block text-[11px]">Fecha y Hora:</span>
                    <strong class="font-mono text-slate-800 dark:text-slate-200" x-text="selectedDebt?.date"></strong>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Registrado por:</span>
                    <strong class="text-slate-800 dark:text-slate-200" x-text="selectedDebt?.user"></strong>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Origen:</span>
                    <template x-if="selectedDebt?.sale_number">
                        <span class="font-mono font-bold text-slate-800 dark:text-white" x-text="'Venta ' + selectedDebt?.sale_number"></span>
                    </template>
                    <template x-if="!selectedDebt?.sale_number">
                        <span class="text-slate-600 dark:text-slate-300">Fiado Directo en Cartera</span>
                    </template>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Estado Actual:</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                          :class="selectedDebt?.remaining_amount > 0 ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'"
                          x-text="selectedDebt?.remaining_amount > 0 ? 'Pendiente' : 'Pagada'"></span>
                </div>
            </div>

            <!-- Items Table Breakdown -->
            <div class="mb-4">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2">
                    Productos Vendidos en este Fiado
                </h4>

                <template x-if="selectedDebt?.items && selectedDebt.items.length > 0">
                    <div class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold">
                                <tr>
                                    <th class="py-2 px-3">Producto</th>
                                    <th class="py-2 px-2 text-center">Cant.</th>
                                    <th class="py-2 px-2 text-right">Precio Unit.</th>
                                    <th class="py-2 px-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                <template x-for="(it, idx) in selectedDebt.items" :key="idx">
                                    <tr>
                                        <td class="py-2 px-3 font-semibold text-slate-900 dark:text-white">
                                            <div x-text="it.product_name"></div>
                                            <div class="text-[10px] text-slate-400 font-normal" x-show="it.presentation_name && it.presentation_name !== 'Unidad'" x-text="'Presentación: ' + it.presentation_name"></div>
                                        </td>
                                        <td class="py-2 px-2 text-center font-mono font-bold text-slate-700 dark:text-slate-300" x-text="it.quantity"></td>
                                        <td class="py-2 px-2 text-right font-mono text-slate-500 dark:text-slate-400" x-text="formatMoney(it.unit_price)"></td>
                                        <td class="py-2 px-3 text-right font-mono font-bold text-slate-900 dark:text-white" x-text="formatMoney(it.subtotal || (it.quantity * it.unit_price))"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </template>

                <template x-if="!selectedDebt?.items || selectedDebt.items.length === 0">
                    <div class="p-3 bg-slate-50 dark:bg-slate-850 rounded-xl text-xs text-slate-600 dark:text-slate-300">
                        <span class="font-bold">Concepto registrado:</span>
                        <p class="mt-0.5" x-text="selectedDebt?.notes || 'Sin detalle de productos especificado'"></p>
                    </div>
                </template>
            </div>

            <!-- Totals & Actions -->
            <div class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs mb-4">
                <div>
                    <span class="text-slate-400 block text-[11px]">Total Original Deuda:</span>
                    <strong class="font-mono text-base text-slate-900 dark:text-white" x-text="formatMoney(selectedDebt?.original_amount)"></strong>
                </div>
                <div class="text-right">
                    <span class="text-slate-400 block text-[11px]">Saldo Pendiente:</span>
                    <strong class="font-mono text-base text-amber-600 dark:text-amber-400" x-text="formatMoney(selectedDebt?.remaining_amount)"></strong>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2">
                <template x-if="selectedDebt?.receipt_url">
                    <a :href="selectedDebt.receipt_url" target="_blank"
                       class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:hover:bg-slate-100 dark:text-slate-900 border border-slate-800 dark:border-white rounded-xl text-xs font-semibold shadow-xs transition-colors flex items-center gap-1.5">
                        <span>🖨️</span>
                        <span>Ver Ticket de Venta</span>
                    </a>
                </template>
                <button type="button" @click="showDetailModal = false" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 rounded-xl text-xs font-medium transition-colors">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal: New Fiado con Selección de Productos -->
    <div x-show="showNewDebtModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" style="display: none;" @keydown.escape.window="showNewDebtModal = false">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-lg w-full shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between pb-3 border-b border-slate-200 dark:border-slate-800 mb-3">
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white">Registrar Nuevo Fiado</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Se agregará a la deuda pendiente de {{ $customer->name }}</p>
                </div>
                <button type="button" @click="showNewDebtModal = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Mode Switcher -->
            <div class="flex items-center gap-2 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl mb-4">
                <button type="button" @click="debtMode = 'catalog'"
                        class="flex-1 py-1.5 text-xs font-semibold rounded-lg transition-colors"
                        :class="debtMode === 'catalog' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'">
                    📦 Con Productos del Catálogo
                </button>
                <button type="button" @click="debtMode = 'manual'"
                        class="flex-1 py-1.5 text-xs font-semibold rounded-lg transition-colors"
                        :class="debtMode === 'manual' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-xs' : 'text-slate-500 hover:text-slate-900 dark:hover:text-white'">
                    ✏️ Monto / Concepto Manual
                </button>
            </div>

            <form action="{{ route('customers.debts.store', $customer->id) }}" method="POST">
                @csrf

                <!-- MODE 1: Catalog Selector -->
                <div x-show="debtMode === 'catalog'" class="space-y-3 mb-4">
                    <div class="p-3 bg-slate-50 dark:bg-slate-850 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-2.5">
                        <div class="text-xs font-bold uppercase text-slate-600 dark:text-slate-400">Agregar Producto al Fiado</div>
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2">
                            <!-- Product select -->
                            <div class="sm:col-span-6">
                                <select x-model="selectedProductId" @change="onProductChange()" class="input-night w-full text-xs">
                                    <option value="">Selecciona bebida o producto...</option>
                                    <template x-for="prod in allProducts" :key="prod.id">
                                        <option :value="prod.id" x-text="prod.name + ' (' + formatMoney(prod.sale_price) + ')'"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Presentation select if any -->
                            <div class="sm:col-span-3" x-show="availablePresentations.length > 0">
                                <select x-model="selectedPresentationId" class="input-night w-full text-xs">
                                    <template x-for="pres in availablePresentations" :key="pres.id">
                                        <option :value="pres.id" x-text="pres.name + ' (' + formatMoney(pres.sale_price) + ')'"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Quantity -->
                            <div :class="availablePresentations.length > 0 ? 'sm:col-span-1' : 'sm:col-span-3'">
                                <input type="number" x-model.number="selectedQty" min="0.1" step="any" placeholder="Cant."
                                       class="input-night w-full text-xs font-mono font-bold text-center">
                            </div>

                            <!-- Add Button -->
                            <div class="sm:col-span-2">
                                <button type="button" @click="addItem()" :disabled="!selectedProductId"
                                        class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:hover:bg-slate-100 dark:text-slate-900 border border-slate-800 dark:border-white rounded-xl text-xs font-semibold shadow-xs transition-colors disabled:opacity-40">
                                    + Agregar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Selected Items List -->
                    <div>
                        <div class="text-xs font-bold text-slate-700 dark:text-slate-300 mb-1 flex items-center justify-between">
                            <span>Productos a Cargar (<span x-text="catalogItems.length"></span>)</span>
                            <span class="font-mono text-slate-900 dark:text-white" x-text="'Total: ' + formatMoney(totalDebtAmount)"></span>
                        </div>

                        <div class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden max-h-48 overflow-y-auto">
                            <table class="w-full text-left text-xs">
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    <template x-for="(it, idx) in catalogItems" :key="idx">
                                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-850">
                                            <td class="py-2 px-3">
                                                <input type="hidden" :name="'items[' + idx + '][product_id]'" :value="it.product_id">
                                                <input type="hidden" :name="'items[' + idx + '][product_presentation_id]'" :value="it.product_presentation_id">
                                                <input type="hidden" :name="'items[' + idx + '][name]'" :value="it.name">
                                                <input type="hidden" :name="'items[' + idx + '][presentation_name]'" :value="it.presentation_name">
                                                <input type="hidden" :name="'items[' + idx + '][quantity]'" :value="it.quantity">
                                                <input type="hidden" :name="'items[' + idx + '][unit_price]'" :value="it.unit_price">
                                                <input type="hidden" :name="'items[' + idx + '][subtotal]'" :value="it.subtotal">

                                                <div class="font-semibold text-slate-800 dark:text-white" x-text="it.name"></div>
                                                <div class="text-[10px] text-slate-400" x-show="it.presentation_name && it.presentation_name !== 'Unidad'" x-text="it.presentation_name"></div>
                                            </td>
                                            <td class="py-2 px-2 font-mono" x-text="it.quantity + ' x ' + formatMoney(it.unit_price)"></td>
                                            <td class="py-2 px-2 font-mono font-bold text-right" x-text="formatMoney(it.subtotal)"></td>
                                            <td class="py-2 px-2 text-right">
                                                <button type="button" @click="removeItem(idx)" class="text-rose-500 hover:text-rose-700 text-xs px-1.5 py-0.5 rounded">
                                                    ✕
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="catalogItems.length === 0">
                                        <td colspan="4" class="py-4 text-center text-slate-400 italic">No has agregado productos al fiado aún.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Deduct stock checkbox -->
                    <label class="flex items-center gap-2 cursor-pointer pt-1">
                        <input type="checkbox" name="deduct_stock" value="1" x-model="deductStock" class="rounded text-slate-900 dark:text-white focus:ring-0">
                        <span class="text-xs text-slate-700 dark:text-slate-300 font-medium">Descontar automáticamente del inventario físico de la sede</span>
                    </label>
                </div>

                <!-- MODE 2: Manual Input -->
                <div x-show="debtMode === 'manual'" class="space-y-3 mb-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Monto del Fiado ($ COP) *</label>
                        <input type="number" name="amount" min="1" step="any" x-model.number="manualAmount" placeholder="Ej: 45000"
                               class="input-night w-full font-mono font-bold text-lg">
                    </div>
                </div>

                <!-- Shared notes & due date -->
                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Concepto / Notas Adicionales</label>
                    <input type="text" name="notes" placeholder="Ej: Consumo de mesa, entrega a domicilio..."
                           class="input-night w-full text-xs">
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Fecha de Vencimiento / Plazo (Opcional)</label>
                    <input type="date" name="due_date"
                           class="input-night w-full text-xs">
                </div>

                <!-- Balance Preview -->
                <div class="p-3 bg-slate-50 dark:bg-slate-850 rounded-xl flex items-center justify-between text-xs mb-4">
                    <div>
                        <span class="text-slate-400 block text-[11px]">Total a cargar:</span>
                        <strong class="font-mono text-base text-slate-900 dark:text-white" x-text="formatMoney(totalDebtAmount)"></strong>
                    </div>
                    <div class="text-right">
                        <span class="text-slate-400 block text-[11px]">Nuevo saldo de deuda:</span>
                        <strong class="font-mono text-base text-amber-600 dark:text-amber-400" x-text="formatMoney({{ $customer->current_debt }} + totalDebtAmount)"></strong>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="showNewDebtModal = false" class="btn-touch flex-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-medium rounded-xl">Cancelar</button>
                    <button type="submit" :disabled="totalDebtAmount <= 0"
                            class="btn-touch flex-1 bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:hover:bg-slate-100 dark:text-slate-900 border border-slate-800 dark:border-white text-xs font-semibold rounded-xl shadow-xs transition-colors disabled:opacity-40">
                        CONFIRMAR FIADO
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Register Abono -->
    <div x-show="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" style="display: none;" @keydown.escape.window="showPaymentModal = false">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 max-w-sm w-full shadow-2xl max-h-[90vh] overflow-y-auto">
            <h3 class="text-base font-black text-slate-900 dark:text-white mb-1">Registrar Abono a Deuda</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-4">Se registra como movimiento financiero en caja</p>

            <form action="{{ route('customers.payments.store', $customer->id) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Valor del Abono ($ COP) *</label>
                    <input type="number" name="amount" max="{{ $customer->current_debt }}" min="1" step="any" value="{{ $customer->current_debt }}" required autofocus
                           class="input-night w-full font-mono font-bold text-lg">
                </div>

                <div class="mb-3">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Medio de Pago *</label>
                    <select name="payment_method_id" required class="input-night w-full text-xs">
                        @foreach($paymentMethods as $pm)
                            <option value="{{ $pm->id }}">{{ $pm->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase text-slate-700 dark:text-slate-300 mb-1">Referencia / Comprobante</label>
                    <input type="text" name="reference" placeholder="Ej: Transf. Nequi #87654"
                           class="input-night w-full text-xs">
                </div>

                <div class="flex gap-2">
                    <button type="button" @click="showPaymentModal = false" class="btn-touch flex-1 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-medium rounded-xl">Cancelar</button>
                    <button type="submit" class="btn-touch flex-1 bg-slate-900 hover:bg-slate-800 text-white dark:bg-white dark:hover:bg-slate-100 dark:text-slate-900 border border-slate-800 dark:border-white text-xs font-semibold rounded-xl shadow-xs transition-colors">
                        APLICAR ABONO
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
