<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#10b981">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>{{ $title ?? 'POS' }} — LICORERA POS</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <script>
        if (localStorage.getItem('pos_theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 flex flex-col lg:flex-row antialiased select-none transition-colors duration-200"
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
              if (window.PosSound) {
                  this.soundEnabled = window.PosSound.toggle();
              }
          }
      }"
      @online.window="isOnline = true"
      @offline.window="isOnline = false">

    <!-- ========================================== -->
    <!-- 1. DESKTOP PERMANENT SIDEBAR (>= lg)        -->
    <!-- ========================================== -->
    <aside class="hidden lg:flex flex-col w-64 bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 h-screen sticky top-0 shrink-0 z-30">
        <!-- Brand Header -->
        <div class="h-16 px-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between shrink-0">
            <div class="flex items-center space-x-2.5">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-500 to-cyan-500 flex items-center justify-center shadow-md shadow-emerald-500/20 text-white font-black">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="truncate">
                    <h1 class="text-sm font-black tracking-tight text-slate-900 dark:text-white leading-none flex items-center gap-1">
                        LICORERA
                        <span class="text-[9px] px-1 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 font-mono font-bold">POS</span>
                    </h1>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium truncate max-w-[140px] mt-0.5">
                        {{ $currentTenant->name ?? 'Licorera' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 overflow-y-auto p-3 space-y-1 text-xs font-semibold">
            <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-3 pt-1 pb-1">Operaciones</div>
            
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('dashboard') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span class="text-base">📊</span>
                <span>Dashboard General</span>
            </a>

            <a href="{{ route('pos.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('pos.*') ? 'bg-emerald-500 text-white font-bold shadow-md shadow-emerald-500/30' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span class="text-base">⚡</span>
                <span>Venta Rápida (POS)</span>
            </a>

            <a href="{{ route('tables.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('tables.*') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span class="text-base">🪑</span>
                <span>Mesas y Cuentas</span>
            </a>

            <a href="{{ route('inventory.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('inventory.*') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span class="text-base">📦</span>
                <span>Inventario y Kardex</span>
            </a>

            <a href="{{ route('cash.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('cash.*') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span class="text-base">💵</span>
                <span>Caja y Turnos</span>
            </a>

            <div class="text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-3 pt-3 pb-1">Administración</div>

            <a href="{{ route('purchases.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('purchases.*') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span class="text-base">🛒</span>
                <span>Compras y Proveedores</span>
            </a>

            <a href="{{ route('expenses.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('expenses.*') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span class="text-base">🧾</span>
                <span>Gastos Operativos</span>
            </a>

            <a href="{{ route('customers.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('customers.*') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span class="text-base">👥</span>
                <span>Clientes y Deudas</span>
            </a>

            <a href="{{ route('promotions.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('promotions.*') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span class="text-base">🎉</span>
                <span>Promociones</span>
            </a>

            <a href="{{ route('reports.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('reports.*') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                <span class="text-base">📈</span>
                <span>Reportes</span>
            </a>

            @if(auth()->user()->is_superadmin || auth()->user()->hasRoleInTenant($currentTenant->id ?? 0, 'admin'))
                <a href="{{ route('users.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('users.*') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span class="text-base">👤</span>
                    <span>Usuarios y PINs</span>
                </a>

                <a href="{{ route('settings.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('settings.*') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <span class="text-base">⚙️</span>
                    <span>Configuración</span>
                </a>
            @endif

            @if(auth()->user()->is_superadmin)
                <div class="pt-3 pb-1 border-t border-slate-200 dark:border-slate-800 text-[10px] font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider px-3">
                    Multi-Empresas SaaS
                </div>
                <a href="{{ route('superadmin.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800/60 font-bold transition-all hover:bg-purple-100 dark:hover:bg-purple-900/50">
                    <span class="text-base">👑</span>
                    <span>Panel Superadmin</span>
                </a>
            @endif
        </nav>

        <!-- Sidebar Footer / User Profile & Logout -->
        <div class="p-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-900/60 space-y-2 shrink-0">
            <div class="flex items-center justify-between">
                <div class="truncate mr-2">
                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] uppercase font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ $currentUserRole ?? (auth()->user()->is_superadmin ? 'Superadmin' : 'Usuario') }}</p>
                </div>
                <div class="flex items-center gap-1">
                    <button type="button" @click="toggleTheme()" class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-800 transition-all" :title="isDarkMode ? 'Modo Claro' : 'Modo Oscuro'">
                        <template x-if="isDarkMode">
                            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </template>
                        <template x-if="!isDarkMode">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                        </template>
                    </button>
                    <a href="{{ route('unlock') }}" class="p-1.5 rounded-lg text-slate-500 hover:text-slate-800 dark:hover:text-white hover:bg-slate-200 dark:hover:bg-slate-800 transition-all" title="Bloquear pantalla">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </a>
                </div>
            </div>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="w-full py-1.5 px-3 rounded-xl bg-slate-200/70 hover:bg-rose-50 hover:text-rose-600 dark:bg-slate-800 dark:hover:bg-rose-950/40 dark:hover:text-rose-400 text-slate-600 dark:text-slate-300 text-xs font-bold transition-all flex items-center justify-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span>Cerrar Sesión</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- ========================================== -->
    <!-- 2. MAIN VIEW AREA (DESKTOP + MOBILE)       -->
    <!-- ========================================== -->
    <div class="flex-1 flex flex-col min-w-0 min-h-screen">
        
        <!-- Mobile Top Header (< lg) -->
        <header class="lg:hidden bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 px-4 h-16 flex items-center justify-between shrink-0 sticky top-0 z-30">
            <div class="flex items-center space-x-3">
                <button type="button" @click="sidebarOpen = true" class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 min-w-[44px] min-h-[44px] flex items-center justify-center active:scale-95">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <div>
                    <h1 class="text-sm font-black text-slate-900 dark:text-white leading-none">LICORERA POS</h1>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate max-w-[170px] mt-0.5 font-medium">
                        {{ $currentTenant->name ?? 'Licorera' }}
                    </p>
                </div>
            </div>

            <div class="flex items-center space-x-2">
                <button type="button" @click="toggleTheme()" class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 min-w-[40px] min-h-[40px] flex items-center justify-center">
                    <template x-if="isDarkMode">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </template>
                    <template x-if="!isDarkMode">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path></svg>
                    </template>
                </button>
                <a href="{{ route('unlock') }}" class="p-2 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 min-w-[40px] min-h-[40px] flex items-center justify-center" title="Bloquear">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                </a>
            </div>
        </header>

        <!-- Desktop Topbar (>= lg) -->
        <header class="hidden lg:flex items-center justify-between h-16 px-6 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 shrink-0">
            <div>
                <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ $title ?? 'Terminal POS' }}</h2>
                <p class="text-xs text-slate-400">Sucursal: <span class="font-semibold text-slate-600 dark:text-slate-300">{{ $currentBranch->name ?? 'Principal' }}</span></p>
            </div>

            <div class="flex items-center space-x-3">
                @if(session('superadmin_impersonating'))
                    <div class="flex items-center gap-2 px-3 py-1 bg-purple-50 dark:bg-purple-950/80 border border-purple-300 dark:border-purple-600 rounded-xl text-xs text-purple-700 dark:text-purple-300 font-bold">
                        <span class="w-2 h-2 rounded-full bg-purple-500 animate-ping"></span>
                        <span>Empresa Activa (Superadmin)</span>
                        <a href="{{ route('superadmin.leave') }}" class="ml-1 px-2 py-0.5 rounded-lg bg-purple-600 hover:bg-purple-700 text-white font-bold text-[11px] transition-all">
                            Salir
                        </a>
                    </div>
                @endif

                <!-- Online Status Pill -->
                <div class="flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border"
                     :class="isOnline ? 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800' : 'bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-800 animate-pulse'">
                    <span class="w-2 h-2 rounded-full" :class="isOnline ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                    <span x-text="isOnline ? 'Conectado' : 'Sin conexión'"></span>
                </div>
            </div>
        </header>

        <!-- Main Body Content -->
        <main class="flex-1 overflow-y-auto p-3 sm:p-4 lg:p-6 pb-24 lg:pb-6 relative">
            @if(session('success'))
                <div class="mb-4 p-3.5 bg-emerald-50 border border-emerald-200 dark:bg-emerald-950/80 dark:border-emerald-500/30 rounded-2xl text-emerald-800 dark:text-emerald-300 flex items-center justify-between text-sm shadow-sm">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="font-medium">{{ session('success') }}</span>
                    </div>
                    <button type="button" @click="$el.parentElement.remove()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">&times;</button>
                </div>
            @endif

            @if(session('error') || $errors->any())
                <div class="mb-4 p-3.5 bg-rose-50 border border-rose-200 dark:bg-rose-950/80 dark:border-rose-500/30 rounded-2xl text-rose-800 dark:text-rose-300 flex items-center justify-between text-sm shadow-sm">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="font-medium">{{ session('error') ?? $errors->first() }}</span>
                    </div>
                    <button type="button" @click="$el.parentElement.remove()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">&times;</button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- ========================================== -->
    <!-- 3. MOBILE BOTTOM NAVIGATION (ONLY < lg)    -->
    <!-- ========================================== -->
    <nav class="lg:hidden fixed bottom-0 inset-x-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 z-40 h-16 px-2 flex items-center justify-around shadow-lg">
        <!-- 1. Inicio -->
        <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-[11px] font-semibold transition-all {{ request()->routeIs('dashboard') ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-500 dark:text-slate-400' }}">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            <span>Inicio</span>
        </a>

        <!-- 2. Mesas -->
        <a href="{{ route('tables.index') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-[11px] font-semibold transition-all {{ request()->routeIs('tables.*') ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-500 dark:text-slate-400' }}">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 14a2 2 0 012-2h4a2 2 0 012 2v2a2 2 0 01-2 2h-4a2 2 0 01-2-2v-2zM4 14a2 2 0 012-2h4a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z"></path>
            </svg>
            <span>Mesas</span>
        </a>

        <!-- 3. Vender (Primary Big Action Button) -->
        <a href="{{ route('pos.index') }}" class="flex flex-col items-center justify-center -mt-4">
            <div class="w-13 h-13 p-3 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white flex items-center justify-center shadow-lg shadow-emerald-600/30 active:scale-95 transition-all">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                </svg>
            </div>
            <span class="text-[10px] font-black tracking-wide text-emerald-600 dark:text-emerald-400 mt-0.5">VENDER</span>
        </a>

        <!-- 4. Inventario -->
        <a href="{{ route('inventory.index') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-[11px] font-semibold transition-all {{ request()->routeIs('inventory.*') ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-500 dark:text-slate-400' }}">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            <span>Inventario</span>
        </a>

        <!-- 5. Caja -->
        <a href="{{ route('cash.index') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-[11px] font-semibold transition-all {{ request()->routeIs('cash.*') ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-500 dark:text-slate-400' }}">
            <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
            <span>Caja</span>
        </a>
    </nav>

    <!-- ========================================== -->
    <!-- 4. MOBILE SLIDE-OVER DRAWER (ONLY < lg)    -->
    <!-- ========================================== -->
    <div x-show="sidebarOpen"
         class="lg:hidden fixed inset-0 z-50 overflow-hidden"
         style="display: none;"
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="sidebarOpen = false"></div>

        <div class="fixed inset-y-0 left-0 max-w-xs w-full bg-white dark:bg-slate-900 border-r border-slate-200 dark:border-slate-800 shadow-2xl flex flex-col z-50">
            <!-- Drawer Header -->
            <div class="p-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-wider">Menú del Sistema</span>
                </div>
                <button type="button" @click="sidebarOpen = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Drawer Links -->
            <div class="flex-1 overflow-y-auto p-3 space-y-1 text-sm font-semibold">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                    <span>📊 Dashboard General</span>
                </a>
                <a href="{{ route('pos.index') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                    <span>⚡ Venta Rápida (POS)</span>
                </a>
                <a href="{{ route('tables.index') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                    <span>🪑 Mesas y Cuentas</span>
                </a>
                <a href="{{ route('inventory.index') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                    <span>📦 Inventario y Kardex</span>
                </a>
                <a href="{{ route('cash.index') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                    <span>💵 Turno y Arqueo de Caja</span>
                </a>
                <a href="{{ route('purchases.index') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                    <span>🛒 Compras y Proveedores</span>
                </a>
                <a href="{{ route('expenses.index') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                    <span>🧾 Gastos Operativos</span>
                </a>
                <a href="{{ route('customers.index') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                    <span>👥 Clientes y Deudas</span>
                </a>
                <a href="{{ route('promotions.index') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                    <span>🎉 Promociones</span>
                </a>
                <a href="{{ route('reports.index') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                    <span>📈 Reportes y Exportación</span>
                </a>

                @if(auth()->user()->is_superadmin || auth()->user()->hasRoleInTenant($currentTenant->id ?? 0, 'admin'))
                    <div class="pt-3 pb-1 border-t border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider px-3">
                        Administración
                    </div>
                    <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                        <span>👤 Usuarios y PINs</span>
                    </a>
                    <a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                        <span>⚙️ Configuración del Negocio</span>
                    </a>
                @endif

                @if(auth()->user()->is_superadmin)
                    <div class="pt-3 pb-1 border-t border-slate-200 dark:border-slate-800 text-[11px] font-bold text-purple-600 uppercase tracking-wider px-3">
                        Multi-Empresas SaaS
                    </div>
                    <a href="{{ route('superadmin.index') }}" class="flex items-center gap-3 px-3.5 py-3 rounded-xl bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800 font-bold">
                        <span>👑 Superadmin SaaS</span>
                    </a>
                @endif
            </div>

            <!-- Drawer Footer -->
            <div class="p-3 border-t border-slate-200 dark:border-slate-800">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-slate-100 hover:bg-rose-50 hover:text-rose-600 dark:bg-slate-800 dark:hover:bg-rose-950/40 dark:hover:text-rose-300 text-slate-700 dark:text-slate-300 text-xs font-bold flex items-center justify-center gap-2 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
