<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Licorera — LICORERA POS SaaS</title>
    <script>
        if (localStorage.getItem('pos_theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full py-10 px-4 flex flex-col items-center justify-center">
    <div class="w-full max-w-xl bg-night-900 border border-night-800 rounded-3xl p-6 sm:p-10 shadow-2xl">
        <!-- Brand Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-neon-green to-neon-cyan shadow-xl shadow-neon-green/20 mb-3 text-night-950">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">Alta de Nueva Licorera</h1>
            <p class="text-sm text-slate-400 mt-1">Configuración automática de tu punto de venta en segundos</p>
        </div>

        @if($errors->any())
            <div class="mb-6 p-4 bg-rose-950/80 border border-rose-500/30 rounded-2xl text-rose-300 text-sm">
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
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Nombre Comercial de la Licorera *</label>
                <input type="text" name="business_name" value="{{ old('business_name') }}" required
                       placeholder="Ej: Licorera y Estanco La 70"
                       class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-4 py-3 text-sm focus:border-neon-green focus:outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">NIT / Identificación Tributaria</label>
                    <input type="text" name="tax_id" value="{{ old('tax_id') }}"
                           placeholder="Ej: 901.234.567-8"
                           class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-4 py-3 text-sm focus:border-neon-green focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Teléfono / WhatsApp</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                           placeholder="Ej: 310 123 4567"
                           class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-4 py-3 text-sm focus:border-neon-green focus:outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Dirección del Establecimiento</label>
                <input type="text" name="address" value="{{ old('address') }}"
                       placeholder="Ej: Carrera 70 # 44 - 12, Medellín"
                       class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-4 py-3 text-sm focus:border-neon-green focus:outline-none">
            </div>

            <!-- Admin User Details -->
            <div class="pt-4 border-t border-night-800">
                <h3 class="text-sm font-bold text-neon-green uppercase tracking-wider mb-3">Datos del Administrador</h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Nombre Completo *</label>
                        <input type="text" name="admin_name" value="{{ old('admin_name') }}" required
                               placeholder="Ej: Andrés Morales"
                               class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-4 py-3 text-sm focus:border-neon-green focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Correo Electrónico *</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               placeholder="admin@licorera.co"
                               class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-4 py-3 text-sm focus:border-neon-green focus:outline-none">
                    </div>
                </div>

                <!-- 4-Digit Touch PIN Setup -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">PIN de Acceso (4 dígitos) *</label>
                        <input type="password" name="pin" maxlength="4" pattern="\d{4}" inputmode="numeric" required
                               placeholder="••••"
                               class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-4 py-3 text-center text-xl font-mono tracking-widest focus:border-neon-green focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-300 mb-1">Confirmar PIN *</label>
                        <input type="password" name="pin_confirmation" maxlength="4" pattern="\d{4}" inputmode="numeric" required
                               placeholder="••••"
                               class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-4 py-3 text-center text-xl font-mono tracking-widest focus:border-neon-green focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-6">
                <button type="submit" class="w-full btn-touch bg-gradient-to-r from-neon-green to-emerald-500 hover:from-neon-green hover:to-emerald-400 text-night-950 font-black text-base shadow-xl shadow-neon-green/20">
                    CREAR LICORERA Y COMENZAR
                </button>
            </div>
        </form>

        <div class="text-center mt-6 pt-4 border-t border-night-800">
            <a href="{{ route('login') }}" class="text-xs text-slate-400 hover:text-white font-semibold">
                ¿Ya tienes una cuenta registrada? Iniciar Sesión con PIN &rarr;
            </a>
        </div>
    </div>
</body>
</html>
