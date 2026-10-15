@extends('layouts.app', ['title' => 'Resultado de Pago Wompi'])

@section('content')
<div class="h-full flex items-center justify-center p-4">
    <div class="card-night p-8 max-w-md w-full text-center space-y-5 rounded-3xl shadow-2xl border border-slate-200 dark:border-slate-800">
        <div class="w-16 h-16 rounded-3xl bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 mx-auto flex items-center justify-center shadow-lg shadow-emerald-500/20">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>

        <div>
            <h2 class="text-xl font-black text-slate-900 dark:text-white">¡Transacción Procesada con Wompi!</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Tu pago está siendo verificado en tiempo real con Bancolombia</p>
        </div>

        @if($transactionId)
            <div class="p-3 bg-slate-50 dark:bg-slate-950 rounded-2xl border border-slate-200 dark:border-slate-800 text-xs font-mono">
                <span class="text-slate-400 block text-[10px] uppercase font-bold">ID de Transacción Wompi:</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $transactionId }}</span>
            </div>
        @endif

        <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
            Una vez confirmada la aprobación por Wompi, tus 30 días de servicio se sumarán de inmediato a tu suscripción activa.
        </p>

        <div class="pt-2 flex flex-col sm:flex-row gap-3">
            <a href="{{ route('billing.index') }}" class="btn-touch flex-1 py-3 px-4 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-750 text-slate-700 dark:text-slate-300 font-bold text-xs rounded-xl">
                Ver Mi Suscripción
            </a>
            <a href="{{ route('pos.index') }}" class="btn-touch flex-1 py-3 px-4 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs rounded-xl shadow-lg shadow-emerald-600/30">
                Ir al POS Mostrador &rarr;
            </a>
        </div>
    </div>
</div>
@endsection
