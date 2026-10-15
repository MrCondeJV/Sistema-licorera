<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="application-name" content="Licorera POS">
    <meta name="apple-mobile-web-app-title" content="Licorera POS">
    <title>Desbloquear Sesión — LICORERA POS</title>
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
<body class="flex items-center justify-center min-h-full p-4 bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-100 select-none transition-colors duration-200">
    <div class="w-full max-w-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-7 sm:p-8 shadow-2xl relative"
         x-data="{
             showPassword: false,
             password: ''
         }">

        <!-- User Avatar & Lock Header -->
        <div class="text-center mb-6">
            <div class="w-16 h-16 rounded-full bg-emerald-50 dark:bg-emerald-950/40 border-2 border-emerald-500/40 mx-auto flex items-center justify-center mb-3 text-emerald-600 dark:text-emerald-400">
                <svg class="w-8 h-8" width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <h1 class="text-xl font-black text-slate-900 dark:text-white">{{ $user->name }}</h1>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Pantalla bloqueada por inactividad</p>
            <p class="text-[11px] text-slate-400 font-mono mt-0.5">{{ $user->email }}</p>
        </div>

        @if($errors->any())
            <div class="mb-4 p-3 bg-rose-50 border border-rose-200 dark:bg-rose-950/80 dark:border-rose-500/30 rounded-xl text-rose-700 dark:text-rose-300 text-xs font-semibold text-center">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('unlock.submit') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-slate-400 mb-1.5">
                    Contraseña de Acceso
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-5 h-5 text-slate-400" width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </span>
                    <input :type="showPassword ? 'text' : 'password'" name="password" x-model="password" required autofocus
                           placeholder="••••••••••••"
                           class="w-full bg-slate-50 dark:bg-slate-950/70 border border-slate-300 dark:border-slate-800 text-slate-900 dark:text-white rounded-xl pl-10 pr-11 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
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

            <button type="submit"
                    class="w-full py-3 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 active:scale-[0.99] text-white font-bold text-sm shadow-md shadow-emerald-600/25 transition-all flex items-center justify-center gap-2">
                <span>Desbloquear Sesión</span>
                <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                </svg>
            </button>
        </form>

        <div class="text-center pt-5 mt-5 border-t border-slate-100 dark:border-slate-800">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="text-xs text-slate-500 hover:text-slate-800 dark:hover:text-white underline font-semibold">
                    Cerrar sesión / Iniciar con otro usuario
                </button>
            </form>
        </div>
    </div>
</body>
</html>
