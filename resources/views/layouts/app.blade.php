<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#10b981">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>{{ $title ?? 'POS' }} — LA NOCHE</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <script>
        // Default to light theme, allowing persistent toggle
        if (localStorage.getItem('pos_theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex flex-col h-full overflow-hidden select-none"
      x-data="{
          sidebarOpen: false,
          isOnline: navigator.onLine,
          isDarkMode: document.documentElement.classList.contains('dark'),
          soundEnabled: localStorage.getItem('pos_sound') !== 'false',
          toggleTheme() {
              this.isDarkMode = !this.isDarkMode;
              if (this.isDarkMode) {
                  document.documentElement.classList.add('dark');
                  localStorage.setItem('pos_theme', 'dark');
              } else {
                  document.documentElement.classList.remove('dark');
                  localStorage.setItem('pos_theme', 'light');
              }
          },
          toggleSound() {
              this.soundEnabled = window.PosSound.toggle();
          }
      }"
      @online.window="isOnline = true"
      @offline.window="isOnline = false">

    <!-- Top Navigation Header -->
    <header class="bg-night-900 border-b border-night-800 px-4 py-2.5 flex items-center justify-between shrink-0 h-16 z-30">
        <div class="flex items-center space-x-3">
            <button @click="sidebarOpen = true" class="p-2 rounded-xl bg-night-800 text-slate-300 hover:text-white hover:bg-night-750 active:scale-95 transition-all min-w-[48px] min-h-[48px] flex items-center justify-center">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <div class="flex items-center space-x-2">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-neon-green to-neon-cyan flex items-center justify-center shadow-lg shadow-neon-green/20 font-black text-night-950 text-xl tracking-tighter">
                    N
                </div>
                <div>
                    <h1 class="text-base sm:text-lg font-black tracking-tight text-white leading-none flex items-center gap-1.5">
                        LA NOCHE
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-neon-green/10 text-neon-green border border-neon-green/20 font-mono font-bold uppercase tracking-wider">POS</span>
                    </h1>
                    <p class="text-xs text-slate-400 font-medium truncate max-w-[180px] sm:max-w-xs mt-0.5">
                        {{ $currentTenant->name ?? 'Licorera' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Status Badges & Controls -->
        <div class="flex items-center space-x-2 sm:space-x-3">
            @if(session('superadmin_impersonating'))
                <!-- Impersonation indicator -->
                <div class="flex items-center gap-2 px-3 py-1 bg-purple-950/90 border border-purple-500/50 rounded-xl text-xs text-purple-200 shadow-lg shadow-purple-950/40">
                    <span class="w-2 h-2 rounded-full bg-purple-400 animate-ping"></span>
                    <span class="font-bold hidden md:inline">Modo Superadmin (Empresa Activa)</span>
                    <a href="{{ route('superadmin.leave') }}" class="px-2 py-0.5 rounded-lg bg-purple-800 hover:bg-purple-700 text-white font-bold text-[11px] transition-all">
                        Salir al Panel
                    </a>
                </div>
            @endif

            <!-- Connection Status -->
            <div class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-full text-xs font-semibold"
                 :class="isOnline ? 'bg-emerald-950/80 text-emerald-400 border border-emerald-800/60' : 'bg-rose-950/80 text-rose-400 border border-rose-800/60 animate-pulse'">
                <span class="w-2 h-2 rounded-full" :class="isOnline ? 'bg-emerald-400' : 'bg-rose-400'"></span>
                <span class="hidden sm:inline" x-text="isOnline ? 'Online' : 'Offline'"></span>
            </div>

            <!-- Theme Switcher (Light / Dark) -->
            <button @click="toggleTheme()" class="p-2 rounded-xl bg-night-800 text-slate-300 hover:text-white transition-all min-w-[44px] min-h-[44px] flex items-center justify-center" :title="isDarkMode ? 'Cambiar a modo Claro' : 'Cambiar a modo Oscuro'">
                <template x-if="isDarkMode">
                    <!-- Sun icon for switching to light -->
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </template>
                <template x-if="!isDarkMode">
                    <!-- Moon icon for switching to dark -->
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                    </svg>
                </template>
            </button>

            <!-- Audio Feedback Toggle -->
            <button @click="toggleSound()" class="p-2 rounded-xl bg-night-800 text-slate-300 hover:text-white transition-all min-w-[44px] min-h-[44px] flex items-center justify-center" :title="soundEnabled ? 'Sonido activado' : 'Sonido silenciado'">
                <template x-if="soundEnabled">
                    <svg class="w-5 h-5 text-neon-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
                    </svg>
                </template>
                <template x-if="!soundEnabled">
                    <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15zM17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"></path>
                    </svg>
                </template>
            </button>

            <!-- User Info & Lock -->
            <div class="flex items-center pl-1 sm:pl-2 border-l border-night-800 space-x-2">
                <div class="hidden md:block text-right">
                    <p class="text-xs font-bold text-slate-200 leading-tight">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] uppercase font-mono text-neon-cyan font-semibold">{{ $currentUserRole ?? 'Usuario' }}</p>
                </div>
                <a href="{{ route('unlock') }}" class="p-2 rounded-xl bg-night-800 hover:bg-night-750 text-slate-400 hover:text-white transition-all min-w-[44px] min-h-[44px] flex items-center justify-center" title="Bloquear pantalla">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Area with internal scroll -->
    <main class="flex-1 overflow-y-auto relative p-3 sm:p-4 pb-24">
        @if(session('success'))
            <div class="mb-4 p-3.5 bg-emerald-950/80 border border-emerald-500/30 rounded-xl text-emerald-300 flex items-center justify-between text-sm shadow-lg shadow-emerald-950/40">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-slate-400 hover:text-white">&times;</button>
            </div>
        @endif

        @if(session('error') || $errors->any())
            <div class="mb-4 p-3.5 bg-rose-950/80 border border-rose-500/30 rounded-xl text-rose-300 flex items-center justify-between text-sm shadow-lg shadow-rose-950/40">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-rose-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <span>{{ session('error') ?? $errors->first() }}</span>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-slate-400 hover:text-white">&times;</button>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Fixed Bottom 5-Button Touch Navigation for 10" Tablets -->
    <nav class="fixed bottom-0 inset-x-0 bg-night-900/95 backdrop-blur-md border-t border-night-800 z-40 h-20 px-2 sm:px-6 flex items-center justify-around shadow-2xl">
        <!-- 1. Inicio -->
        <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-xs font-semibold transition-all {{ request()->routeIs('dashboard') ? 'text-neon-cyan font-bold scale-105' : 'text-slate-400 hover:text-slate-200' }}">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            <span>Inicio</span>
        </a>

        <!-- 2. Mesas -->
        <a href="{{ route('tables.index') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-xs font-semibold transition-all {{ request()->routeIs('tables.*') ? 'text-neon-amber font-bold scale-105' : 'text-slate-400 hover:text-slate-200' }}">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 14a2 2 0 012-2h4a2 2 0 012 2v2a2 2 0 01-2 2h-4a2 2 0 01-2-2v-2zM4 14a2 2 0 012-2h4a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z"></path>
            </svg>
            <span>Mesas</span>
        </a>

        <!-- 3. Vender (Primary Big Action Button) -->
        <a href="{{ route('pos.index') }}" class="flex flex-col items-center justify-center -mt-5">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-neon-green to-emerald-400 text-night-950 flex items-center justify-center shadow-lg shadow-neon-green/30 border-2 border-night-900 active:scale-95 transition-all">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                </svg>
            </div>
            <span class="text-xs font-black tracking-wide text-neon-green mt-1">VENDER</span>
        </a>

        <!-- 4. Inventario -->
        <a href="{{ route('inventory.index') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-xs font-semibold transition-all {{ request()->routeIs('inventory.*') ? 'text-neon-cyan font-bold scale-105' : 'text-slate-400 hover:text-slate-200' }}">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            <span>Inventario</span>
        </a>

        <!-- 5. Caja -->
        <a href="{{ route('cash.index') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-xs font-semibold transition-all {{ request()->routeIs('cash.*') ? 'text-neon-green font-bold scale-105' : 'text-slate-400 hover:text-slate-200' }}">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <span>Caja</span>
        </a>
    </nav>

    <!-- Slide-over Administrative Menu Drawer -->
    <div x-show="sidebarOpen"
         class="fixed inset-0 z-50 overflow-hidden"
         style="display: none;"
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/75 backdrop-blur-sm" @click="sidebarOpen = false"></div>

        <div class="fixed inset-y-0 left-0 max-w-xs w-full bg-night-900 border-r border-night-800 shadow-2xl flex flex-col z-50">
            <!-- Drawer Header -->
            <div class="p-4 border-b border-night-800 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="w-3 h-3 rounded-full bg-neon-green animate-pulse"></span>
                    <span class="text-sm font-black text-white uppercase tracking-wider">Menú del Sistema</span>
                </div>
                <button @click="sidebarOpen = false" class="p-2 text-slate-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Drawer Links -->
            <div class="flex-1 overflow-y-auto p-3 space-y-1 text-sm font-semibold">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl hover:bg-night-800 text-slate-300 hover:text-white transition-all">
                    <span>📊 Dashboard General</span>
                </a>
                <a href="{{ route('pos.index') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl hover:bg-night-800 text-slate-300 hover:text-white transition-all">
                    <span>⚡ Venta Rápida (POS)</span>
                </a>
                <a href="{{ route('tables.index') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl hover:bg-night-800 text-slate-300 hover:text-white transition-all">
                    <span>🪑 Mesas y Cuentas</span>
                </a>
                <a href="{{ route('inventory.index') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl hover:bg-night-800 text-slate-300 hover:text-white transition-all">
                    <span>📦 Inventario y Kardex</span>
                </a>
                <a href="{{ route('cash.index') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl hover:bg-night-800 text-slate-300 hover:text-white transition-all">
                    <span>💵 Turno y Arqueo de Caja</span>
                </a>
                <a href="{{ route('purchases.index') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl hover:bg-night-800 text-slate-300 hover:text-white transition-all">
                    <span>🚚 Compras y Proveedores</span>
                </a>
                <a href="{{ route('expenses.index') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl hover:bg-night-800 text-slate-300 hover:text-white transition-all">
                    <span>🧾 Gastos Operativos</span>
                </a>
                <a href="{{ route('customers.index') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl hover:bg-night-800 text-slate-300 hover:text-white transition-all">
                    <span>👥 Clientes y Fiados</span>
                </a>
                <a href="{{ route('promotions.index') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl hover:bg-night-800 text-slate-300 hover:text-white transition-all">
                    <span>🎉 Promociones / Happy Hour</span>
                </a>
                <a href="{{ route('reports.index') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl hover:bg-night-800 text-slate-300 hover:text-white transition-all">
                    <span>📈 Reportes y Exportación</span>
                </a>

                @if(auth()->user()->is_superadmin || auth()->user()->hasRoleInTenant($currentTenant->id ?? 0, 'admin'))
                    <div class="pt-3 pb-1 border-t border-night-800 text-[11px] font-bold text-slate-500 uppercase tracking-wider px-3">
                        Administración
                    </div>
                    <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl hover:bg-night-800 text-slate-300 hover:text-white transition-all">
                        <span>🔑 Usuarios y PINs</span>
                    </a>
                    <a href="{{ route('audit.index') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl hover:bg-night-800 text-slate-300 hover:text-white transition-all">
                        <span>🛡️ Auditoría Inmutable</span>
                    </a>
                    <a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl hover:bg-night-800 text-slate-300 hover:text-white transition-all">
                        <span>⚙️ Configuración del Negocio</span>
                    </a>
                @endif

                @if(auth()->user()->is_superadmin)
                    <div class="pt-3 pb-1 border-t border-night-800 text-[11px] font-bold text-neon-green uppercase tracking-wider px-3">
                        Plataforma SaaS
                    </div>
                    <a href="{{ route('superadmin.index') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl bg-neon-green/10 text-neon-green border border-neon-green/30 font-bold transition-all">
                        <span>👑 Superadmin SaaS</span>
                    </a>
                @endif
            </div>

            <!-- Drawer Footer -->
            <div class="p-3 border-t border-night-800">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full btn-touch bg-rose-950/80 hover:bg-rose-900 border border-rose-800/60 text-rose-300 text-sm flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        <span>Cerrar Sesión</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(err => console.log('SW registration error', err));
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
