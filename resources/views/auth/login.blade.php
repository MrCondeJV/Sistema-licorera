<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Iniciar Sesión — LA NOCHE</title>
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
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-neon-green to-neon-cyan shadow-xl shadow-neon-green/20 mb-3 text-night-950 font-black text-3xl">
                N
            </div>
            <h1 class="text-2xl font-black tracking-tight text-white">LA NOCHE</h1>
            <p class="text-xs text-slate-400 mt-1 uppercase tracking-wider font-semibold">Sistema POS Integral para Licoreras</p>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 bg-rose-950/80 border border-rose-500/30 rounded-xl text-rose-300 text-xs font-semibold text-center">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('login.pin') }}" method="POST" x-ref="loginForm">
            @csrf
            <input type="hidden" name="pin" :value="pin">

            <!-- Business / Tenant Selector -->
            <div class="mb-5">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-400 mb-1.5 text-center">
                    Selecciona tu Licorera
                </label>
                <select name="tenant_id" x-model="tenantId" class="w-full bg-night-850 border border-night-750 text-white rounded-xl px-3.5 py-3 text-sm font-semibold focus:outline-none focus:border-neon-green">
                    @foreach($tenants as $t)
                        <option value="{{ $t->id }}" {{ (isset($selectedTenant) && $selectedTenant->id === $t->id) ? 'selected' : '' }}>
                            {{ $t->name }}
                        </option>
                    @endforeach
                </select>
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
            <div class="grid grid-cols-3 gap-2.5 sm:gap-3 mb-6">
                <template x-for="num in ['1','2','3','4','5','6','7','8','9']" :key="num">
                    <button type="button" @click="addDigit(num)"
                            class="h-16 rounded-2xl bg-night-800 hover:bg-night-750 active:bg-night-700 active:scale-95 text-2xl font-black text-white border border-night-750 transition-all flex items-center justify-center shadow-md">
                        <span x-text="num"></span>
                    </button>
                </template>
                <button type="button" @click="clearPin()"
                        class="h-16 rounded-2xl bg-night-850 hover:bg-night-800 active:scale-95 text-xs font-bold text-slate-400 border border-night-750 transition-all flex items-center justify-center uppercase tracking-wider">
                    Limpiar
                </button>
                <button type="button" @click="addDigit('0')"
                        class="h-16 rounded-2xl bg-night-800 hover:bg-night-750 active:bg-night-700 active:scale-95 text-2xl font-black text-white border border-night-750 transition-all flex items-center justify-center shadow-md">
                    0
                </button>
                <button type="button" @click="backspace()"
                        class="h-16 rounded-2xl bg-night-850 hover:bg-night-800 active:scale-95 text-slate-400 border border-night-750 transition-all flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l7-7h11a2 2 0 012 2v10a2 2 0 01-2 2H10l-7-7z"></path>
                    </svg>
                </button>
            </div>
        </form>

        <!-- New Business Registration Link -->
        <div class="text-center pt-4 border-t border-night-800">
            <a href="{{ route('business.register') }}" class="text-xs text-neon-cyan hover:underline font-semibold flex items-center justify-center gap-1">
                <span>¿Tienes una licorera?</span>
                <span class="font-bold text-white">Registra tu negocio gratis &rarr;</span>
            </a>
        </div>
    </div>
</body>
</html>
