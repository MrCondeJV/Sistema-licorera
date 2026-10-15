<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="application-name" content="Licorera POS">
    <meta name="apple-mobile-web-app-title" content="Licorera POS">
    <title>Registrar Licorera — LICORERA POS SaaS</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/manifest.json?v=3">
    <script>
        if (localStorage.getItem('pos_theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full py-10 px-4 flex flex-col items-center justify-center bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors">
    <div class="w-full max-w-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-10 shadow-xl">
        <!-- Brand Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-emerald-600 text-white shadow-xl shadow-emerald-600/20 mb-3">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900 dark:text-white">Alta de Nueva Licorera</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">Configuración rápida de tu punto de venta en segundos</p>
        </div>

        @if($errors->any())
            <div class="mb-6 p-4 bg-rose-50 dark:bg-rose-950/80 border border-rose-200 dark:border-rose-500/30 rounded-2xl text-rose-700 dark:text-rose-300 text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('business.register.submit') }}" method="POST" class="space-y-4">
            @csrf

            <!-- Business Details -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Nombre Comercial de la Licorera *</label>
                <input type="text" name="business_name" value="{{ old('business_name') }}" required
                       placeholder="Ej: Licorera y Estanco Central"
                       class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-white rounded-xl px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">NIT / Identificación Tributaria</label>
                    <input type="text" name="tax_id" value="{{ old('tax_id') }}"
                           placeholder="Ej: 901.234.567-8"
                           class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-white rounded-xl px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Teléfono / WhatsApp</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                           placeholder="Ej: 310 123 4567"
                           class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-white rounded-xl px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Dirección del Establecimiento</label>
                <input type="text" name="address" value="{{ old('address') }}"
                       placeholder="Ej: Carrera 70 # 44 - 12"
                       class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-white rounded-xl px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
            </div>

            <!-- Plan Selector -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Plan a Probar (Gratis por 14 días)</label>
                <select name="plan_slug" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-white rounded-xl px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none font-semibold">
                    @foreach($plans as $p)
                        <option value="{{ $p->slug }}" {{ (old('plan_slug') ?? 'pro') === $p->slug ? 'selected' : '' }}>
                            {{ $p->name }} — ${{ number_format($p->price_monthly, 0, ',', '.') }} COP/mes
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Admin User Details -->
            <div class="pt-4 border-t border-slate-200 dark:border-slate-800">
                <h3 class="text-sm font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-3">Datos del Administrador</h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Nombre Completo *</label>
                        <input type="text" name="admin_name" value="{{ old('admin_name') }}" required
                               placeholder="Ej: Carlos Gómez"
                               class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-white rounded-xl px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Correo Electrónico *</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               placeholder="admin@licorerapos.com"
                               class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-white rounded-xl px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">Contraseña de Acceso *</label>
                        <input type="password" name="password" minlength="6" required
                               placeholder="Mínimo 6 caracteres"
                               class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-white rounded-xl px-4 py-3 text-sm focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">PIN Rápido de Pantalla (4 dígitos)</label>
                        <input type="password" name="pin" maxlength="4" pattern="\d{4}" inputmode="numeric"
                               placeholder="Ej: 1234 (Opcional)"
                               class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-white rounded-xl px-4 py-3 text-center text-sm font-mono tracking-widest focus:border-emerald-500 focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-6">
                <button type="submit" class="w-full py-3 px-4 bg-slate-900 hover:bg-slate-800 text-white dark:bg-slate-100 dark:hover:bg-white dark:text-slate-900 font-bold rounded-xl shadow-xs transition-all text-sm tracking-wide flex items-center justify-center gap-2">
                    <span>ACTIVAR PRUEBA GRATUITA (14 DÍAS)</span>
                </button>
                <p class="text-[11px] text-center text-slate-400 dark:text-slate-500 mt-2">
                    Sin compromisos · Sin ingresar tarjeta de crédito · Acceso inmediato
                </p>
            </div>
        </form>

        <div class="text-center mt-6 pt-4 border-t border-slate-200 dark:border-slate-800">
            <a href="{{ route('login') }}" class="text-xs text-slate-600 dark:text-slate-400 hover:text-emerald-600 dark:hover:text-emerald-400 font-semibold transition-colors">
                ¿Ya tienes una cuenta registrada? Iniciar Sesión &rarr;
            </a>
        </div>
    </div>
</body>
</html>
