<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Iniciar Sesión — LICORERA POS</title>
    <script>
        if (localStorage.getItem('pos_theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex items-center justify-center min-h-full p-4 select-none">
    <div class="w-full max-w-md bg-night-900 border border-night-800 rounded-3xl p-6 sm:p-8 shadow-2xl relative"
         x-data="{
             pin: '',
             tenantId: '{{ $selectedTenant->id ?? '' }}',
             addDigit(num) {
                 if (this.pin.length < 4) {
                     this.pin += num;
                     window.PosSound.beep('add');
                     if (this.pin.length === 4) {
                         this.$nextTick(() => {
                             this.$refs.loginForm.submit();
                         });
                     }
                 }
             },
             clearPin() {
                 this.pin = '';
             },
             backspace() {
                 this.pin = this.pin.slice(0, -1);
             }
         }">

        <!-- Logo & Title -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-500 to-cyan-500 shadow-xl shadow-emerald-500/20 mb-3 text-white font-black text-2xl">
                POS
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">SISTEMA POS</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-wider font-semibold">Terminal de Ventas e Inventario</p>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 bg-rose-50 border border-rose-200 dark:bg-rose-950/80 dark:border-rose-500/30 rounded-xl text-rose-700 dark:text-rose-300 text-xs font-semibold text-center">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login.pin') }}" method="POST" x-ref="loginForm">
            @csrf
            <input type="hidden" name="pin" :value="pin">

            <!-- Business / Tenant Selector -->
            <div class="mb-5">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 text-center">
                    Selecciona tu Negocio / Sucursal
                </label>
                <select name="tenant_id" x-model="tenantId" class="w-full bg-slate-50 dark:bg-night-850 border border-slate-300 dark:border-night-750 text-slate-900 dark:text-white rounded-xl px-3.5 py-3 text-sm font-semibold focus:outline-none focus:border-emerald-500">
                    <option value="">-- Ingreso Superadmin / Sin negocio asignado --</option>
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ (isset($selectedTenant) && $selectedTenant->id === $t->id) ? 'selected' : '' }}>
                            {{ $t->name }}
                        </option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-400 text-center mt-1">
                    * Superadministrador puede dejar este campo libre e ingresar con PIN maestro.
                </p>
            </div>

            <!-- PIN Display Dots -->
            <div class="flex items-center justify-center space-x-3 my-6">
                <template x-for="i in 4" :key="i">
                    <div class="w-5 h-5 rounded-full border-2 transition-all duration-150 flex items-center justify-center"
                         :class="pin.length >= i ? 'bg-neon-green border-neon-green shadow-lg shadow-neon-green/40 scale-110' : 'border-night-700 bg-night-800'">
                    </div>
                </template>
            </div>

            <!-- Touch Keypad (0-9) -->
            <div class="grid grid-cols-3 gap-2.5 sm:gap-3 mb-5">
                <template x-for="num in ['1','2','3','4','5','6','7','8','9']" :key="num">
                    <button type="button" @click="addDigit(num)"
                            class="h-16 rounded-2xl bg-white dark:bg-night-800 hover:bg-slate-100 dark:hover:bg-night-750 active:scale-95 text-2xl font-black text-slate-800 dark:text-white border border-slate-200 dark:border-night-750 transition-all flex items-center justify-center shadow-sm">
                        <span x-text="num"></span>
                    </button>
                </template>
                <button type="button" @click="clearPin()"
                        class="h-16 rounded-2xl bg-slate-100 dark:bg-night-850 hover:bg-slate-200 dark:hover:bg-night-800 active:scale-95 text-xs font-bold text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-night-750 transition-all flex items-center justify-center uppercase tracking-wider">
                    Limpiar
                </button>
                <button type="button" @click="addDigit('0')"
                        class="h-16 rounded-2xl bg-white dark:bg-night-800 hover:bg-slate-100 dark:hover:bg-night-750 active:scale-95 text-2xl font-black text-slate-800 dark:text-white border border-slate-200 dark:border-night-750 transition-all flex items-center justify-center shadow-sm">
                    0
                </button>
                <button type="button" @click="backspace()"
                        class="h-16 rounded-2xl bg-slate-100 dark:bg-night-850 hover:bg-slate-200 dark:hover:bg-night-800 active:scale-95 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-night-750 transition-all flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l7-7h11a2 2 0 012 2v10a2 2 0 01-2 2H10l-7-7z"></path>
                    </svg>
                </button>
            </div>
        </form>

        <!-- Footer / Demo Credentials Guide -->
        <div class="text-center pt-4 border-t border-slate-200 dark:border-night-800 space-y-2">
            <div class="text-[11px] text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-night-950/60 p-2.5 rounded-xl border border-slate-200 dark:border-night-800">
                <span class="font-bold text-slate-700 dark:text-slate-300">PINs de acceso:</span><br>
                Superadmin: <strong class="text-emerald-600 dark:text-emerald-400 font-mono">9999</strong> &bull; Admin: <strong class="font-mono text-slate-700 dark:text-slate-300">1234</strong> &bull; Cajero: <strong class="font-mono text-slate-700 dark:text-slate-300">2222</strong>
            </div>

            <div>
                <a href="{{ route('business.register') }}" class="text-xs text-emerald-600 dark:text-neon-cyan hover:underline font-semibold inline-flex items-center gap-1">
                    <span>¿Nuevo comercio? Registrar negocio &rarr;</span>
                </a>
            </div>
        </div>
    </div>
</body>
</html>
