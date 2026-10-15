<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="application-name" content="Licorera POS">
    <meta name="apple-mobile-web-app-title" content="Licorera POS">
    <title>Iniciar Sesión — LICORERA POS</title>
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
<body class="min-h-screen w-full flex items-center justify-center p-4 sm:p-6 md:p-8 bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-100 select-none transition-colors duration-200">
    
    <!-- Theme Toggle Floating Button -->
    <div class="fixed top-3 right-3 sm:top-5 sm:right-5 z-50"
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
                class="p-2 sm:p-2.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 shadow-md hover:bg-slate-50 dark:hover:bg-slate-800 transition-all flex items-center gap-2 text-xs font-bold"
                :title="isDark ? 'Cambiar a modo Claro' : 'Cambiar a modo Oscuro'">
            <template x-if="isDark">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-amber-400" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </template>
            <template x-if="!isDark">
                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-slate-700 dark:text-slate-300" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                </svg>
            </template>
            <span class="hidden sm:inline text-xs" x-text="isDark ? 'Tema Claro' : 'Tema Oscuro'"></span>
        </button>
    </div>

    <!-- Login Card Container -->
    <div class="w-full max-w-sm sm:max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl shadow-slate-900/5 dark:shadow-black/40 relative transition-all duration-200"
         x-data="{
             showPassword: false,
             email: '{{ old('email', '') }}',
             password: ''
         }">

        <!-- Logo & Branding Header -->
        <div class="text-center mb-6 sm:mb-7">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 shadow-sm mb-3 mx-auto">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">LICORERA POS</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 font-medium">Terminal Punto de Venta & Gestión</p>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 sm:p-3.5 bg-rose-50 border border-rose-200 dark:bg-rose-950/80 dark:border-rose-500/30 rounded-xl text-rose-700 dark:text-rose-300 text-xs font-semibold text-center flex items-center justify-center gap-2">
                <svg class="w-4 h-4 shrink-0 text-rose-500" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        @if(session('status'))
            <div class="mb-4 p-3 sm:p-3.5 bg-emerald-50 border border-emerald-200 dark:bg-emerald-950/80 dark:border-emerald-500/30 rounded-xl text-emerald-700 dark:text-emerald-300 text-xs font-semibold text-center">
                {{ session('status') }}
            </div>
        @endif

        @if(auth()->check())
            <div class="mb-4 p-3 bg-slate-100 border border-slate-200 dark:bg-slate-800 dark:border-slate-700 rounded-xl text-slate-800 dark:text-slate-200 text-xs font-medium flex items-center justify-between gap-2">
                <div class="flex items-center gap-1.5 truncate">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                    <span class="truncate">Sesión iniciada: <strong class="font-bold">{{ auth()->user()->name }}</strong></span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ auth()->user()->is_superadmin && !session('superadmin_impersonating') ? route('superadmin.index') : route('pos.index') }}" class="px-2.5 py-1 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-[11px] font-semibold transition-colors">
                        Ir al Panel &rarr;
                    </a>
                </div>
            </div>
        @endif

        <!-- CREDENTIALS FORM -->
        <form action="{{ route('login.submit') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <!-- Campo: Correo Electrónico -->
                <div>
                    <label class="block text-[11px] sm:text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        Correo Electrónico
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-5 h-5 text-slate-400" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                            </svg>
                        </span>
                        <input type="email" name="email" x-model="email" required autofocus autocomplete="email"
                               placeholder="ejemplo@licorerapos.com"
                               class="w-full bg-slate-50 dark:bg-slate-950/70 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-white rounded-lg pl-10 pr-4 py-2.5 sm:py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-slate-400/20 focus:border-slate-500 transition-all">
                    </div>
                </div>

                <!-- Campo: Contraseña -->
                <div>
                    <label class="block text-[11px] sm:text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                        Contraseña
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <svg class="w-5 h-5 text-slate-400" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </span>
                        <input :type="showPassword ? 'text' : 'password'" name="password" x-model="password" required autocomplete="current-password"
                               placeholder="••••••••••••"
                               class="w-full bg-slate-50 dark:bg-slate-950/70 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-white rounded-lg pl-10 pr-11 py-2.5 sm:py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-slate-400/20 focus:border-slate-500 transition-all">
                        <button type="button" @click="showPassword = !showPassword"
                                tabindex="-1"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                            <svg x-show="!showPassword" class="w-5 h-5" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            <svg x-show="showPassword" class="w-5 h-5" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Recordar sesión -->
                <div class="flex items-center justify-between text-xs pt-0.5">
                    <label class="flex items-center gap-2 cursor-pointer text-slate-600 dark:text-slate-400">
                        <input type="checkbox" name="remember" class="w-4 h-4 rounded text-slate-900 focus:ring-slate-500 border-slate-300 dark:border-slate-700">
                        <span class="text-xs">Recordar mi sesión</span>
                    </label>
                </div>

                <!-- Botón de Envío -->
                <button type="submit"
                        class="w-full py-2.5 sm:py-3 px-4 rounded-lg bg-slate-900 hover:bg-slate-800 dark:bg-slate-100 dark:hover:bg-white text-white dark:text-slate-900 font-semibold text-sm shadow-sm transition-colors flex items-center justify-center gap-2 mt-1">
                    <span>Iniciar Sesión</span>
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </button>
            </div>
        </form>

        <!-- Pie de Página Limpio de Producción -->
        <div class="text-center pt-5 mt-5 border-t border-slate-100 dark:border-slate-800/80 space-y-1">
            <p class="text-[11px] font-semibold text-slate-400 dark:text-slate-500">
                LICORERA POS &copy; {{ date('Y') }} &bull; Todos los derechos reservados
            </p>
            <p class="text-[10px] text-slate-400 dark:text-slate-500 flex items-center justify-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                <span>Acceso seguro cifrado</span>
            </p>
        </div>
    </div>
</body>
</html>
