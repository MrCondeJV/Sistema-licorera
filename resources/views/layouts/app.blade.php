<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#10b981">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="application-name" content="Licorera POS">
    <meta name="apple-mobile-web-app-title" content="Licorera POS">
    <title>{{ $title ?? 'POS' }} — LICORERA POS</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/manifest.json?v=3">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
    <script>
        if (localStorage.getItem('pos_theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        svg { max-width: 100%; display: inline-block; vertical-align: middle; }
    </style>
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
            <a href="{{ auth()->user()->is_superadmin && !session('superadmin_impersonating') ? route('superadmin.index') : route('dashboard') }}" class="flex items-center space-x-2.5 hover:opacity-90 transition-opacity">
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
                        {{ auth()->user()->is_superadmin && !session('superadmin_impersonating') ? 'Plataforma SaaS' : ($currentTenant->name ?? 'Licorera') }}
                    </p>
                </div>
            </a>
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 overflow-y-auto p-3.5 space-y-1.5 text-[13.5px] font-semibold">
            @if(auth()->user()->is_superadmin && !session('superadmin_impersonating'))
                {{-- MODO SUPERADMINISTRADOR: Solo administración SaaS, Tenants, Planes, Backups y Auditoría --}}
                <div class="text-[11px] font-bold text-purple-600 dark:text-purple-400 uppercase tracking-wider px-3 pt-1 pb-1">Administración SaaS</div>

                <a href="{{ route('superadmin.index') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('superadmin.index') || request()->routeIs('superadmin.tenants.*') ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    <span>Empresas & Licoreras</span>
                </a>

                <a href="{{ route('superadmin.plans.index') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('superadmin.plans.*') ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    <span>Planes y Precios SaaS</span>
                </a>

                <a href="{{ route('superadmin.payments') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('superadmin.payments*') ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    <span>Histórico de Pagos SaaS</span>
                </a>

                <a href="{{ route('superadmin.backups') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('superadmin.backups*') ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                    <span>Copias de Seguridad</span>
                </a>

                <a href="{{ route('superadmin.audit') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('superadmin.audit') ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    <span>Auditoría Global SaaS</span>
                </a>
            @else
                {{-- MODO LICORERA / OPERATIVO (Tenants y Superadmin cuando impersona a una empresa) --}}
                @if(session('superadmin_impersonating'))
                    <div class="mb-3 p-3 rounded-2xl bg-gradient-to-br from-purple-700 to-indigo-800 text-white shadow-lg border border-purple-400/30">
                        <div class="flex items-center justify-between gap-1 mb-1">
                            <span class="text-[10px] font-black uppercase tracking-wider text-purple-200 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                Modo Superadmin
                            </span>
                        </div>
                        <p class="text-xs font-bold truncate text-white mb-2">{{ $currentTenant->name ?? 'Licorera' }}</p>
                        <a href="{{ route('superadmin.leave') }}" class="w-full py-1.5 px-3 rounded-xl bg-white hover:bg-purple-50 text-purple-900 font-black text-xs flex items-center justify-center gap-1.5 shadow-md transition-all active:scale-95">
                            <svg class="w-4 h-4 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                            <span>Regresar a Superadmin</span>
                        </a>
                    </div>
                @endif

                <div class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-3 pt-1 pb-1">Operaciones</div>
                
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('dashboard') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    <span>Dashboard General</span>
                </a>

                <a href="{{ route('pos.index') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('pos.*') ? 'bg-emerald-600 text-white font-bold shadow-md shadow-emerald-600/30' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    <span>Venta Rápida (POS)</span>
                </a>

                <a href="{{ route('tables.index') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('tables.*') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    <span>Mesas y Cuentas</span>
                </a>

                <a href="{{ route('inventory.index') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('inventory.*') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                    <span>Inventario y Kardex</span>
                </a>

                <a href="{{ route('cash.index') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('cash.*') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <span>Caja y Turnos</span>
                </a>

                <div class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-wider px-3 pt-3 pb-1">Administración</div>

                <a href="{{ route('purchases.index') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('purchases.*') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <span>Compras de Stock</span>
                </a>

                <a href="{{ route('suppliers.index') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('suppliers.*') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                    <span>Proveedores</span>
                </a>

                <a href="{{ route('expenses.index') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('expenses.*') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path></svg>
                    <span>Gastos Operativos</span>
                </a>

                <a href="{{ route('customers.index') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('customers.*') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <span>Clientes y Deudas</span>
                </a>

                <a href="{{ route('promotions.index') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('promotions.*') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                    <span>Promociones</span>
                </a>

                <a href="{{ route('reports.index') }}"
                   class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('reports.*') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    <span>Reportes</span>
                </a>

                @if(auth()->user()->is_superadmin || auth()->user()->hasRoleInTenant($currentTenant->id ?? 0, 'admin'))
                    <a href="{{ route('users.index') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('users.*') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <span>Usuarios y Accesos</span>
                    </a>

                    <a href="{{ route('audit.index') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('audit.*') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        <span>Auditoría y Trazabilidad</span>
                    </a>

                    <a href="{{ route('branches.index') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('branches.*') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        <span>Sucursales & Sedes</span>
                    </a>

                    <a href="{{ route('settings.index') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('settings.*') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span>Configuración</span>
                    </a>

                    <a href="{{ route('billing.index') }}"
                       class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('billing.*') ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold border border-emerald-500/20' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        <span>Suscripción y Wompi</span>
                    </a>
                @endif
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
    <div class="flex-1 flex flex-col min-w-0 min-h-screen lg:h-screen lg:overflow-hidden">
        
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
                    @if(auth()->user()?->is_superadmin && !session('superadmin_impersonating'))
                        <p class="text-[10px] text-purple-600 dark:text-purple-400 font-black flex items-center gap-1 mt-0.5">
                            ⚡ Panel Superadmin
                        </p>
                    @elseif(isset($accessibleBranches) && count($accessibleBranches) > 1)
                        <form method="POST" class="mt-0.5">
                            @csrf
                            <select onchange="if(this.value){ this.form.action='{{ url('/branch/switch') }}/' + this.value; this.form.submit(); }"
                                    class="text-[10px] font-black py-0.5 px-1.5 rounded-lg border border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 max-w-[140px] truncate focus:outline-none">
                                @foreach($accessibleBranches as $b)
                                    <option value="{{ $b->id }}" {{ ($currentBranch->id ?? 0) == $b->id ? 'selected' : '' }}>
                                        🏪 {{ $b->name }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @else
                        <p class="text-[10px] text-emerald-600 dark:text-emerald-400 font-bold truncate max-w-[150px] mt-0.5">
                            🏪 {{ $currentBranch->name ?? 'Sede Principal' }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="flex items-center space-x-2">
                @if(session('superadmin_impersonating'))
                    <a href="{{ route('superadmin.leave') }}" class="px-2.5 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs flex items-center gap-1 shadow-sm active:scale-95 transition-all">
                        <span>⬅ Superadmin</span>
                    </a>
                @endif
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
            <div class="flex items-center gap-4">
                <div>
                    @if(auth()->user()->is_superadmin && !session('superadmin_impersonating'))
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ $title ?? 'Plataforma SaaS Licorera POS' }}</h2>
                        <p class="text-xs text-purple-600 dark:text-purple-400 font-bold">Panel Maestro de Superadministración</p>
                    @else
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ $title ?? 'Terminal POS' }}</h2>
                        <p class="text-xs text-slate-400">Sucursal: <span class="font-semibold text-slate-600 dark:text-slate-300">{{ $currentBranch->name ?? 'Principal' }}</span></p>
                    @endif
                </div>

                @if(isset($accessibleTenants) && count($accessibleTenants) > 1)
                    <form method="POST" class="flex items-center gap-2 pl-4 border-l border-slate-200 dark:border-slate-800">
                        @csrf
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Empresa:</span>
                        <select onchange="if(this.value){ this.form.action='{{ url('/tenant/switch') }}/' + this.value; this.form.submit(); }"
                                class="text-xs font-bold py-1.5 px-3 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 shadow-sm cursor-pointer">
                            @foreach($accessibleTenants as $t)
                                <option value="{{ $t->id }}" {{ ($currentTenant->id ?? 0) == $t->id ? 'selected' : '' }}>
                                    🏢 {{ $t->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif

                @if(isset($accessibleBranches) && count($accessibleBranches) > 0)
                    <form method="POST" class="flex items-center gap-2 pl-4 border-l border-slate-200 dark:border-slate-800">
                        @csrf
                        <span class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Sucursal:</span>
                        <select onchange="if(this.value){ this.form.action='{{ url('/branch/switch') }}/' + this.value; this.form.submit(); }"
                                class="text-xs font-black py-1.5 px-3 rounded-xl border border-emerald-500/30 bg-emerald-50/50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-500 shadow-sm cursor-pointer">
                            @foreach($accessibleBranches as $b)
                                <option value="{{ $b->id }}" {{ ($currentBranch->id ?? 0) == $b->id ? 'selected' : '' }}>
                                    🏪 {{ $b->name }} {{ $b->is_main ? '(Principal)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif
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
        <main class="flex-1 min-h-0 overflow-y-auto p-3 sm:p-4 lg:p-4 pb-24 lg:pb-4 relative flex flex-col">
            @if(isset($currentTenant) && !auth()->user()->is_superadmin)
                @if($currentTenant->isGracePeriodExceeded())
                    <div class="mb-4 p-4 bg-rose-500/15 border-2 border-rose-500/50 rounded-2xl text-rose-800 dark:text-rose-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs shadow-md">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">🚨</span>
                            <div>
                                <span class="font-black text-sm block">Servicio Suspendido por Falta de Pago</span>
                                <span>Han transcurrido más de 5 días de gracia desde la fecha de corte. Tu punto de venta e inventario se encuentran bloqueados hasta que renueves.</span>
                            </div>
                        </div>
                        <a href="{{ route('billing.index') }}" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-black whitespace-nowrap text-center shadow-lg shadow-rose-600/30 text-xs">
                            💳 Pago Inmediato con Wompi
                        </a>
                    </div>
                @elseif($currentTenant->isPastDue())
                    <div class="mb-4 p-3.5 bg-amber-500/15 border border-amber-500/40 rounded-2xl text-amber-800 dark:text-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs shadow-sm">
                        <div class="flex items-center gap-2.5">
                            <span class="text-xl">⚠️</span>
                            <div>
                                <span class="font-black block">Período de Gracia (Vencido hace {{ $currentTenant->daysPastDue() }} {{ Str::plural('día', $currentTenant->daysPastDue()) }})</span>
                                <span>Te quedan <strong>{{ $currentTenant->graceDaysRemaining() }} días de gracia</strong> para renovar antes del bloqueo total de la plataforma.</span>
                            </div>
                        </div>
                        <a href="{{ route('billing.index') }}" class="px-3.5 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-black whitespace-nowrap text-center shadow-md">
                            💳 Renovar con Wompi
                        </a>
                    </div>
                @elseif($currentTenant->isOnTrial())
                    <div class="mb-4 p-3 bg-emerald-500/10 border border-emerald-500/25 rounded-2xl text-emerald-800 dark:text-emerald-300 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs shadow-sm">
                        <div class="flex items-center gap-2">
                            <span class="text-base">🎁</span>
                            <span>Prueba gratuita de 14 días activa: te quedan <strong>{{ $currentTenant->daysRemaining() }} días</strong> de acceso completo.</span>
                        </div>
                        <a href="{{ route('billing.index') }}" class="px-3 py-1 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black whitespace-nowrap text-center shadow-sm text-[11px]">
                            💳 Activar Plan con Wompi
                        </a>
                    </div>
                @endif
            @endif

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
                <div class="mb-4 p-4 bg-rose-50 border border-rose-200 dark:bg-rose-950/80 dark:border-rose-500/30 rounded-2xl text-rose-800 dark:text-rose-300 text-sm shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                @if(session('error'))
                                    <p class="font-bold text-sm">{{ session('error') }}</p>
                                @endif
                                @if($errors->any())
                                    <p class="font-bold text-sm mb-1.5">No se pudo completar la operación. Por favor revisa los siguientes campos:</p>
                                    <ul class="list-disc list-inside space-y-1 text-xs text-rose-700 dark:text-rose-300 font-medium">
                                        @foreach($errors->all() as $err)
                                            <li>{{ $err }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </div>
                        <button type="button" @click="$el.closest('div.mb-4').remove()" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-lg font-bold">&times;</button>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- ========================================== -->
    <!-- 3. MOBILE BOTTOM NAVIGATION (ONLY < lg)    -->
    <!-- ========================================== -->
    <nav class="lg:hidden fixed bottom-0 inset-x-0 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200 dark:border-slate-800 z-40 h-16 px-2 flex items-center justify-around shadow-lg">
        @if(auth()->user()->is_superadmin && !session('superadmin_impersonating'))
            <!-- Superadmin Mobile Nav -->
            <a href="{{ route('superadmin.index') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-[10px] font-semibold transition-all {{ request()->routeIs('superadmin.index') || request()->routeIs('superadmin.tenants.*') ? 'text-purple-600 dark:text-purple-400 font-bold' : 'text-slate-500 dark:text-slate-400' }}">
                <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                <span>Empresas</span>
            </a>
            <a href="{{ route('superadmin.plans.index') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-[10px] font-semibold transition-all {{ request()->routeIs('superadmin.plans.*') ? 'text-purple-600 dark:text-purple-400 font-bold' : 'text-slate-500 dark:text-slate-400' }}">
                <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                <span>Planes</span>
            </a>
            <a href="{{ route('superadmin.payments') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-[10px] font-semibold transition-all {{ request()->routeIs('superadmin.payments*') ? 'text-purple-600 dark:text-purple-400 font-bold' : 'text-slate-500 dark:text-slate-400' }}">
                <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                <span>Pagos</span>
            </a>
            <a href="{{ route('superadmin.backups') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-[10px] font-semibold transition-all {{ request()->routeIs('superadmin.backups*') ? 'text-purple-600 dark:text-purple-400 font-bold' : 'text-slate-500 dark:text-slate-400' }}">
                <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                <span>Backups</span>
            </a>
            <a href="{{ route('superadmin.audit') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-[10px] font-semibold transition-all {{ request()->routeIs('superadmin.audit') ? 'text-purple-600 dark:text-purple-400 font-bold' : 'text-slate-500 dark:text-slate-400' }}">
                <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                <span>Auditoría</span>
            </a>
        @else
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

            <!-- 5. Caja o Volver Superadmin -->
            @if(session('superadmin_impersonating'))
                <a href="{{ route('superadmin.leave') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-[10px] font-bold text-purple-600 dark:text-purple-400 transition-all">
                    <svg class="w-5 h-5 mb-0.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                    <span>Superadmin</span>
                </a>
            @else
                <a href="{{ route('cash.index') }}" class="flex flex-col items-center justify-center flex-1 h-full py-1 text-[11px] font-semibold transition-all {{ request()->routeIs('cash.*') ? 'text-emerald-600 dark:text-emerald-400 font-bold' : 'text-slate-500 dark:text-slate-400' }}">
                    <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span>Caja</span>
                </a>
            @endif
        @endif
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
            <div class="flex-1 overflow-y-auto p-3.5 space-y-1.5 text-sm font-semibold">
                @if(auth()->user()->is_superadmin && !session('superadmin_impersonating'))
                    <div class="px-3 pb-2 pt-1 flex items-center justify-between">
                        <span class="text-[11px] font-black text-purple-600 dark:text-purple-400 uppercase tracking-wider">Superadmin SaaS</span>
                        <a href="{{ route('superadmin.tenants.create') }}" class="px-2.5 py-1 rounded-lg bg-indigo-600 text-white text-[11px] font-black hover:bg-indigo-500 transition-colors flex items-center gap-1 shadow-sm">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                            <span>Nueva</span>
                        </a>
                    </div>
                    <a href="{{ route('superadmin.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('superadmin.index', 'superadmin.tenants.*') ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        <span>Empresas & Licoreras</span>
                    </a>
                    <a href="{{ route('superadmin.plans.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('superadmin.plans.*') ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        <span>Planes y Precios SaaS</span>
                    </a>
                    <a href="{{ route('superadmin.payments') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('superadmin.payments*') ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        <span>Histórico de Pagos SaaS</span>
                    </a>
                    <a href="{{ route('superadmin.backups') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('superadmin.backups*') ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                        <span>Copias de Seguridad</span>
                    </a>
                    <a href="{{ route('superadmin.audit') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all {{ request()->routeIs('superadmin.audit*') ? 'bg-purple-600 text-white font-bold shadow-md shadow-purple-600/30' : 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        <span>Auditoría Global SaaS</span>
                    </a>
                @else
                    @if(session('superadmin_impersonating'))
                        <div class="mb-3 p-3 rounded-2xl bg-gradient-to-br from-purple-700 to-indigo-800 text-white shadow-lg border border-purple-400/30">
                            <span class="text-[10px] font-black uppercase tracking-wider text-purple-200 block mb-0.5">
                                Modo Superadmin Activo
                            </span>
                            <p class="text-xs font-bold truncate text-white mb-2">{{ $currentTenant->name ?? 'Licorera' }}</p>
                            <a href="{{ route('superadmin.leave') }}" class="w-full py-1.5 px-3 rounded-xl bg-white text-purple-900 font-black text-xs flex items-center justify-center gap-1.5 shadow-md">
                                <span>⬅ Regresar a Superadmin</span>
                            </a>
                        </div>
                    @endif
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        <span>Dashboard General</span>
                    </a>
                    <a href="{{ route('pos.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        <span>Venta Rápida (POS)</span>
                    </a>
                    <a href="{{ route('tables.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        <span>Mesas y Cuentas</span>
                    </a>
                    <a href="{{ route('inventory.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                        <span>Inventario y Kardex</span>
                    </a>
                    <a href="{{ route('cash.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span>Turno y Arqueo de Caja</span>
                    </a>
                    <a href="{{ route('purchases.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span>Compras de Stock</span>
                    </a>
                    <a href="{{ route('suppliers.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        <span>Proveedores</span>
                    </a>
                    <a href="{{ route('expenses.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"></path></svg>
                        <span>Gastos Operativos</span>
                    </a>
                    <a href="{{ route('customers.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span>Clientes y Deudas</span>
                    </a>
                    <a href="{{ route('promotions.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                        <span>Promociones</span>
                    </a>
                    <a href="{{ route('reports.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        <span>Reportes y Exportación</span>
                    </a>

                    @if(auth()->user()->is_superadmin || auth()->user()->hasRoleInTenant($currentTenant->id ?? 0, 'admin'))
                        <div class="pt-3.5 pb-1 border-t border-slate-200 dark:border-slate-800 text-[11px] font-bold text-slate-400 uppercase tracking-wider px-3">
                            Administración
                        </div>
                        <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            <span>Usuarios y Accesos</span>
                        </a>
                        <a href="{{ route('audit.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            <span>Auditoría y Trazabilidad</span>
                        </a>
                        <a href="{{ route('branches.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            <span>Sucursales & Sedes</span>
                        </a>
                        <a href="{{ route('settings.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <span>Configuración del Negocio</span>
                        </a>

                        <a href="{{ route('billing.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                            <span>Suscripción y Wompi</span>
                        </a>
                    @endif
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

    <!-- PWA Service Worker Registration & Cache Invalidation -->
    <script>
        // Immediately purge any stale Service Worker cache keys from browser storage
        if ('caches' in window) {
            caches.keys().then(names => {
                for (let name of names) {
                    if (name.includes('lanoche') || name.includes('pos-v1') || name.includes('pos-v2')) {
                        caches.delete(name);
                    }
                }
            });
        }

        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').then(reg => {
                    reg.update();
                }).catch(err => console.log('SW registration error', err));
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            @if(session('success'))
                if (window.AppAlert) window.AppAlert.toast(@json(session('success')), 'success');
            @endif
            @if(session('error'))
                if (window.AppAlert) window.AppAlert.error('Atención', @json(session('error')));
            @endif
            @if(session('warning'))
                if (window.AppAlert) window.AppAlert.warning('Aviso', @json(session('warning')));
            @endif
            @if(session('info'))
                if (window.AppAlert) window.AppAlert.info('Información', @json(session('info')));
            @endif
        });
    </script>
    @stack('scripts')
</body>
</html>
