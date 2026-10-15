@extends('layouts.app', ['title' => 'Suscripción y Pagos Wompi'])

@section('content')
<div class="h-full flex flex-col space-y-6 max-w-6xl mx-auto" x-data="billingManager()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h2 class="text-2xl font-black text-slate-900 dark:text-white">Suscripción SaaS y Facturación</h2>
                @if($wompiSandbox)
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                        Wompi Sandbox / Pruebas
                    </span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                        Wompi Producción
                    </span>
                @endif
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Administra tu membresía mensual, métodos de pago y renovaciones automáticas con Wompi Bancolombia</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('backups.index') }}" class="btn-touch py-2.5 px-4 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl border border-slate-200 dark:border-slate-700 flex items-center gap-2">
                <span>💾 Copias de Seguridad</span>
            </a>
        </div>
    </div>

    <!-- Subscription Status Banner -->
    <div class="p-6 rounded-3xl border {{ $isExpired ? 'bg-rose-500/10 border-rose-500/30' : ($isOnTrial ? 'bg-amber-500/10 border-amber-500/30' : 'bg-emerald-500/10 border-emerald-500/30') }} flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-sm">
        <div class="space-y-1.5">
            <div class="flex items-center gap-2">
                <span class="text-xs font-black uppercase tracking-wider {{ $isExpired ? 'text-rose-600 dark:text-rose-400' : ($isOnTrial ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400') }}">
                    Estado Actual:
                </span>
                <span class="px-3 py-1 rounded-full text-xs font-black {{ $isExpired ? 'bg-rose-600 text-white' : ($isOnTrial ? 'bg-amber-500 text-slate-950 font-bold' : 'bg-emerald-600 text-white') }}">
                    {{ $isExpired ? 'SUSCRIPCIÓN VENCIDA' : ($isOnTrial ? '🎁 PRUEBA GRATUITA (14 DÍAS)' : '✅ SUSCRIPCIÓN ACTIVA') }}
                </span>
            </div>
            <h3 class="text-lg font-black text-slate-900 dark:text-white">
                {{ $tenant->name }} — Plan {{ $currentPlan->name ?? 'Estándar' }}
            </h3>
            <p class="text-xs text-slate-600 dark:text-slate-300">
                @if($isOnTrial)
                    Tu período de prueba finaliza el <strong>{{ $tenant->trial_ends_at?->format('d/m/Y') }}</strong> (te quedan <strong>{{ $daysRemaining }} días</strong>). Paga con Wompi para asegurar la continuidad de tu servicio sin interrupciones.
                @elseif($hasActiveSubscription = $tenant->hasActiveSubscription())
                    Tu suscripción se encuentra activa y vigente hasta el <strong>{{ $tenant->subscription_ends_at?->format('d/m/Y') }}</strong> (<strong>{{ $daysRemaining }} días</strong> restantes).
                @else
                    Tu suscripción ha expirado. Para continuar operando tu punto de venta e inventario, renueva tu plan a continuación mediante Wompi (Nequi, Tarjeta o Bancolombia).
                @endif
            </p>
        </div>
        <div class="text-right shrink-0">
            <span class="block text-2xl font-black font-mono {{ $isExpired ? 'text-rose-600' : ($isOnTrial ? 'text-amber-600' : 'text-emerald-600') }}">
                ${{ number_format($currentPlan->price_monthly ?? 129000, 0, ',', '.') }} COP
            </span>
            <span class="text-[11px] text-slate-400">Facturación mensual</span>
        </div>
    </div>

    <!-- Plans Comparison -->
    <div>
        <h3 class="text-sm font-black uppercase tracking-wider text-slate-900 dark:text-white mb-3">Elige o Renueva tu Plan con Wompi</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($plans as $plan)
                @php
                    $isCurrent = $currentPlan && $currentPlan->id === $plan->id;
                @endphp
                <div class="card-night p-6 flex flex-col justify-between relative {{ $isCurrent ? 'ring-2 ring-emerald-500' : '' }}">
                    @if($isCurrent)
                        <span class="absolute -top-3 right-6 bg-emerald-500 text-white font-black text-[10px] uppercase tracking-wider px-3 py-0.5 rounded-full shadow-md">
                            Plan Actual
                        </span>
                    @endif
                    <div>
                        <h4 class="text-base font-black text-slate-900 dark:text-white">{{ $plan->name }}</h4>
                        <div class="mt-3 mb-4">
                            <span class="text-3xl font-black font-mono text-slate-900 dark:text-white">${{ number_format($plan->price_monthly, 0, ',', '.') }}</span>
                            <span class="text-xs text-slate-400 font-semibold">/ mes COP</span>
                        </div>
                        <ul class="space-y-2 text-xs text-slate-600 dark:text-slate-300 mb-6">
                            <li class="flex items-center gap-2">
                                <span class="text-emerald-500 font-bold">✓</span> Hasta {{ $plan->max_users }} usuarios y cajeros
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-emerald-500 font-bold">✓</span> {{ $plan->max_branches }} sucursal / sede
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-emerald-500 font-bold">✓</span> Hasta {{ number_format($plan->max_products) }} productos en catálogo
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-emerald-500 font-bold">✓</span> Tickets WhatsApp 100% ilimitados y gratis
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-emerald-500 font-bold">✓</span> Control de arqueo a ciegas y kardex
                            </li>
                            <li class="flex items-center gap-2">
                                <span class="text-emerald-500 font-bold">✓</span> Respaldos de base de datos
                            </li>
                        </ul>
                    </div>

                    <button type="button" @click="payWithWompi({{ $plan->id }}, '{{ $plan->name }}', {{ $plan->price_monthly }})"
                            :disabled="loading"
                            class="w-full btn-touch py-3 px-4 rounded-xl text-xs font-black shadow-lg transition-all flex items-center justify-center gap-2 {{ $isCurrent ? 'bg-emerald-600 hover:bg-emerald-500 text-white shadow-emerald-600/30' : 'bg-slate-900 hover:bg-slate-800 dark:bg-white dark:hover:bg-slate-100 text-white dark:text-slate-900' }}">
                        <span>💳 PAGAR CON WOMPI</span>
                    </button>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Payments History Table -->
    <div class="card-night p-5 flex flex-col space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-black text-slate-900 dark:text-white uppercase tracking-wider">Historial de Transacciones Wompi</h3>
            <span class="text-xs text-slate-400">{{ $payments->total() }} pagos registrados</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs min-w-[700px]">
                <thead class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-bold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-2">Fecha</th>
                        <th class="py-3 px-2">Referencia</th>
                        <th class="py-3 px-2">Plan</th>
                        <th class="py-3 px-2">Método</th>
                        <th class="py-3 px-2">ID Wompi</th>
                        <th class="py-3 px-2">Monto</th>
                        <th class="py-3 px-2 text-right">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($payments as $pay)
                        <tr>
                            <td class="py-3 px-2 font-mono text-slate-600 dark:text-slate-300">{{ $pay->created_at->format('d/m/Y h:i A') }}</td>
                            <td class="py-3 px-2 font-mono font-bold text-slate-900 dark:text-white">{{ $pay->reference }}</td>
                            <td class="py-3 px-2 font-bold">{{ $pay->plan->name ?? 'Plan SaaS' }}</td>
                            <td class="py-3 px-2 uppercase text-[11px] font-bold text-slate-500">{{ $pay->payment_method_type ?? 'Wompi Checkout' }}</td>
                            <td class="py-3 px-2 font-mono text-slate-400">{{ $pay->wompi_transaction_id ?? 'N/A' }}</td>
                            <td class="py-3 px-2 font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ $pay->formatted_amount }}</td>
                            <td class="py-3 px-2 text-right">
                                @if($pay->status === 'APPROVED')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700">
                                        APROBADO
                                    </span>
                                @elseif($pay->status === 'PENDING')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border border-amber-300 dark:border-amber-700">
                                        PENDIENTE
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border border-rose-300 dark:border-rose-700">
                                        {{ $pay->status }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">No hay pagos registrados con Wompi todavía.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>
            {{ $payments->links() }}
        </div>
    </div>
</div>

<!-- Wompi Checkout Widget Script -->
<script src="https://checkout.wompi.co/widget.js"></script>

<script>
function billingManager() {
    return {
        loading: false,

        async payWithWompi(planId, planName, amount) {
            this.loading = true;
            try {
                if (window.PosSound) window.PosSound.beep('add');

                const response = await axios.post('{{ route("billing.checkout-data") }}', {
                    plan_id: planId
                });

                if (response.data && response.data.success) {
                    const d = response.data.data;

                    if (typeof WidgetCheckout === 'undefined') {
                        throw new Error('No se pudo cargar el Widget de Wompi. Por favor verifica tu conexión a internet.');
                    }

                    // Open Wompi Official Checkout Widget
                    const checkout = new WidgetCheckout({
                        currency: d.currency,
                        amountInCents: d.amount_in_cents,
                        reference: d.reference,
                        publicKey: d.public_key,
                        signature: {
                            integrity: d.signature
                        },
                        redirectUrl: d.redirect_url,
                        customerData: {
                            email: d.customer_email,
                            fullName: d.customer_name
                        }
                    });

                    checkout.open(function (result) {
                        const transaction = result.transaction;
                        console.log('Wompi Transaction:', transaction);
                        if (transaction && transaction.id) {
                            window.location.href = d.redirect_url + '?id=' + transaction.id;
                        }
                    });
                }
            } catch (err) {
                console.error(err);
                if (window.AppAlert) {
                    window.AppAlert.error('Error al iniciar Wompi', err.response?.data?.message || err.message);
                } else {
                    alert(err.message);
                }
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
