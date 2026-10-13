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
<body class="flex items-center justify-center min-h-full p-4 bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-100 select-none transition-colors duration-200">
    
    <!-- Theme Toggle Floating Button -->
    <div class="fixed top-4 right-4 z-50"
         x-data="{
             isDark: document.documentElement.classList.contains('dark'),
             toggle() {
                 this.isDark = !this.isDark;
                 if (this.isDark) {
                     document.documentElement.classList.add('dark');
                     localStorage.setItem('pos_theme', 'dark');
                 } else {
                     document.documentElement.classList.remove('dark');
                     localStorage.setItem('pos_theme', 'light');
                 }
             }
         }">
        <button type="button" @click="toggle()"
                class="p-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 shadow-lg hover:bg-slate-50 dark:hover:bg-slate-800 transition-all flex items-center gap-2 text-xs font-bold"
                :title="isDark ? 'Cambiar a modo Claro' : 'Cambiar a modo Oscuro'">
            <template x-if="isDark">
                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </template>
            <template x-if="!isDark">
                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                </svg>
            </template>
            <span class="hidden sm:inline" x-text="isDark ? 'Tema Claro' : 'Tema Oscuro'"></span>
        </button>
    </div>

    <div class="w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl relative transition-all duration-200"
         x-data="{
             mode: 'credentials', // 'credentials' or 'pin'
             showPassword: false,
             email: '{{ old('email', '') }}',
             password: '',
             pin: '',
             tenantId: '{{ $selectedTenant->id ?? '' }}',
             fillDemo(e, p) {
                 this.email = e;
                 this.password = p;
             },
             addDigit(num) {
                 if (this.pin.length < 4) {
                     this.pin += num;
                     if (window.PosSound) window.PosSound.beep('add');
                     if (this.pin.length === 4) {
                         this.$nextTick(() => {
                             this.$refs.pinForm.submit();
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
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">LICORERA POS</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 uppercase tracking-wider font-semibold">Sistema de Ventas e Inventario</p>
        </div>

        <!-- Mode Switch Tabs -->
        <div class="flex items-center p-1 bg-slate-100 dark:bg-slate-800 rounded-2xl mb-6">
            <button type="button" @click="mode = 'credentials'"
                    class="flex-1 py-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5"
                    :class="mode === 'credentials' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                </svg>
                <span>Correo / Clave</span>
            </button>
            <button type="button" @click="mode = 'pin'"
                    class="flex-1 py-2 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-1.5"
                    :class="mode === 'pin' ? 'bg-white dark:bg-slate-900 text-slate-900 dark:text-white shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                <span>PIN Terminal</span>
            </button>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3.5 bg-rose-50 border border-rose-200 dark:bg-rose-950/80 dark:border-rose-500/30 rounded-2xl text-rose-700 dark:text-rose-300 text-xs font-semibold text-center flex items-center justify-center gap-2">
                <svg class="w-4 h-4 shrink-0 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <!-- TAB 1: STANDARD CREDENTIALS LOGIN (EMAIL + PASSWORD) -->
        <div x-show="mode === 'credentials'" x-cloak>
            <form action="{{ route('login.submit') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                            Correo Electrónico
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                </svg>
                            </span>
                            <input type="email" name="email" x-model="email" required autofocus
                                   placeholder="ejemplo@licorerapos.com"
                                   class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-white rounded-2xl pl-11 pr-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400">
                                Contraseña
                            </label>
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </span>
                            <input :type="showPassword ? 'text' : 'password'" name="password" x-model="password" required
                                   placeholder="••••••••••••"
                                   class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-white rounded-2xl pl-11 pr-11 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                            <button type="button" @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                                <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between text-xs">
                        <label class="flex items-center gap-2 cursor-pointer text-slate-600 dark:text-slate-400">
                            <input type="checkbox" name="remember" class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300 dark:border-slate-700">
                            <span>Recordar mi sesión</span>
                        </label>
                    </div>

                    <button type="submit"
                            class="w-full py-3.5 px-4 rounded-2xl bg-emerald-600 hover:bg-emerald-500 active:scale-98 text-white font-bold text-sm shadow-lg shadow-emerald-600/25 transition-all flex items-center justify-center gap-2">
                        <span>Ingresar al Sistema</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </div>
            </form>

            <!-- Quick Auto-Fill Demo Credentials -->
            <div class="mt-6 pt-4 border-t border-slate-200 dark:border-slate-800">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 text-center mb-2.5">Accesos de demostración:</p>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <button type="button" @click="fillDemo('superadmin@licorerapos.com', 'Admin1234*')"
                            class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 hover:border-emerald-300 dark:hover:border-emerald-700 text-left transition-all">
                        <span class="block font-bold text-slate-800 dark:text-slate-200">👑 Superadmin</span>
                        <span class="text-[10px] text-slate-400 truncate block">Panel Multi-empresa</span>
                    </button>
                    <button type="button" @click="fillDemo('admin@licorerapos.com', 'Admin1234*')"
                            class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/50 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 hover:border-emerald-300 dark:hover:border-emerald-700 text-left transition-all">
                        <span class="block font-bold text-slate-800 dark:text-slate-200">🏪 Administrador</span>
                        <span class="text-[10px] text-slate-400 truncate block">Dueño Licorera</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- TAB 2: TOUCH PIN KEYPAD LOGIN -->
        <div x-show="mode === 'pin'" x-cloak style="display:none;">
            <form action="{{ route('login.pin') }}" method="POST" x-ref="pinForm">
                @csrf
                <input type="hidden" name="pin" :value="pin">

                <!-- Business / Branch Selector -->
                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1 text-center">
                        Sucursal / Establecimiento
                    </label>
                    <select name="tenant_id" x-model="tenantId" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-white rounded-xl px-3 py-2.5 text-xs font-semibold focus:outline-none focus:border-emerald-500">
                        <option value="">-- Detección Automática / Superadmin --</option>
                        @foreach($tenants as $t)
                            <option value="{{ $t->id }}" {{ (isset($selectedTenant) && $selectedTenant->id === $t->id) ? 'selected' : '' }}>
                                {{ $t->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- PIN Dots -->
                <div class="flex items-center justify-center space-x-3 my-5">
                    <template x-for="i in 4" :key="i">
                        <div class="w-4 h-4 rounded-full border-2 transition-all duration-150 flex items-center justify-center"
                             :class="pin.length >= i ? 'bg-emerald-500 border-emerald-500 shadow-md shadow-emerald-500/40 scale-110' : 'border-slate-300 dark:border-slate-700 bg-slate-100 dark:bg-slate-800'">
                        </div>
                    </template>
                </div>

                <!-- Touch Keypad (0-9) -->
                <div class="grid grid-cols-3 gap-2 mb-4">
                    <template x-for="num in ['1','2','3','4','5','6','7','8','9']" :key="num">
                        <button type="button" @click="addDigit(num)"
                                class="h-14 rounded-2xl bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100 dark:hover:bg-slate-750 active:scale-95 text-xl font-black text-slate-800 dark:text-white border border-slate-200 dark:border-slate-700/60 transition-all flex items-center justify-center shadow-sm">
                            <span x-text="num"></span>
                        </button>
                    </template>
                    <button type="button" @click="clearPin()"
                            class="h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 active:scale-95 text-[11px] font-bold text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700/60 transition-all flex items-center justify-center uppercase">
                        Limpiar
                    </button>
                    <button type="button" @click="addDigit('0')"
                            class="h-14 rounded-2xl bg-slate-50 dark:bg-slate-800/80 hover:bg-slate-100 dark:hover:bg-slate-750 active:scale-95 text-xl font-black text-slate-800 dark:text-white border border-slate-200 dark:border-slate-700/60 transition-all flex items-center justify-center shadow-sm">
                        0
                    </button>
                    <button type="button" @click="backspace()"
                            class="h-14 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 active:scale-95 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700/60 transition-all flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M3 12l7-7h11a2 2 0 012 2v10a2 2 0 01-2 2H10l-7-7z"></path>
                        </svg>
                    </button>
                </div>

                <div class="text-[11px] text-center text-slate-400 bg-slate-50 dark:bg-slate-800/50 p-2 rounded-xl border border-slate-200 dark:border-slate-800">
                    PINs demo: Superadmin: <strong class="text-emerald-600 dark:text-emerald-400 font-mono">9999</strong> &bull; Admin: <strong class="font-mono">1234</strong> &bull; Cajero: <strong class="font-mono">2222</strong>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="text-center pt-5 mt-5 border-t border-slate-200 dark:border-slate-800">
            <a href="{{ route('business.register') }}" class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline font-bold inline-flex items-center gap-1">
                <span>¿Nuevo comercio? Registrar mi licorera &rarr;</span>
            </a>
        </div>
    </div>
</body>
</html>
