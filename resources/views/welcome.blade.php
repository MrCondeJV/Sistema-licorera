<!DOCTYPE html>
<html lang="es" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="application-name" content="Licorera POS">
    <title>Licorera POS — Sistema POS Especializado para Licoreras, Estancos y Bares</title>
    
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/manifest.json?v=3">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="min-h-full bg-slate-950 text-slate-100 antialiased selection:bg-emerald-500 selection:text-white">

    <!-- Top Announcement Bar -->
    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-xs font-black py-2 px-4 text-center tracking-wide">
        🎁 ¡PROMOCIÓN DE LANZAMIENTO! Comienza hoy y obtén 14 días de prueba 100% gratis sin tarjeta de crédito.
    </div>

    <!-- Navigation Bar -->
    <header class="border-b border-slate-800/80 bg-slate-950/80 backdrop-blur-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl bg-emerald-600 text-white flex items-center justify-center shadow-lg shadow-emerald-500/20 font-black text-xl">
                    🍾
                </div>
                <div>
                    <span class="text-xl font-black tracking-tight text-white block">LICORERA POS</span>
                    <span class="text-[10px] text-emerald-400 font-bold uppercase tracking-widest block -mt-1">Especializado para Bares y Licoreras</span>
                </div>
            </div>

            <nav class="hidden md:flex items-center gap-8 text-sm font-bold text-slate-300">
                <a href="#beneficios" class="hover:text-emerald-400 transition-colors">Beneficios</a>
                <a href="#planes" class="hover:text-emerald-400 transition-colors">Planes y Precios</a>
                <a href="#wompi" class="hover:text-emerald-400 transition-colors">Pagos con Wompi</a>
            </nav>

            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ route('pos.index') }}" class="btn-touch py-2.5 px-5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs shadow-lg shadow-emerald-600/30">
                        Ir al POS &rarr;
                    </a>
                @else
                    <a href="{{ route('login') }}" class="text-xs font-bold text-slate-300 hover:text-white py-2 px-4 rounded-xl border border-slate-800 hover:bg-slate-900 transition-colors">
                        Iniciar Sesión
                    </a>
                    <a href="{{ route('business.register') }}" class="btn-touch py-2.5 px-5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs shadow-lg shadow-emerald-600/30 tracking-wide flex items-center gap-2">
                        <span>Prueba Gratis 14 Días</span>
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative overflow-hidden py-20 lg:py-28">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs font-black uppercase tracking-wider mb-8">
                <span>⚡ El Software POS Diseñado para Licoreras Reales</span>
            </div>

            <h1 class="text-4xl sm:text-6xl lg:text-7xl font-black text-white tracking-tight leading-[1.1] max-w-5xl mx-auto mb-8">
                Controla cada botella, <br class="hidden sm:block" />
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-teal-300">
                    evita el robo hormiga
                </span> y cuida tu dinero.
            </h1>

            <p class="text-base sm:text-lg text-slate-400 max-w-3xl mx-auto font-medium leading-relaxed mb-10">
                El sistema táctil para tablets, computadores y móviles optimizado para la velocidad de la noche: cervezas en caja, six-pack y unidad, botellas cerradas de licor, arqueo a ciegas y tickets por WhatsApp 100% gratuitos.
            </p>

            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 max-w-md mx-auto">
                <a href="{{ route('business.register') }}" class="btn-touch w-full sm:w-auto py-4 px-8 rounded-2xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-base shadow-2xl shadow-emerald-600/40 tracking-wide flex items-center justify-center gap-3">
                    <span>🚀 ACTIVAR 14 DÍAS GRATIS</span>
                </a>
                <a href="{{ route('login') }}" class="w-full sm:w-auto py-4 px-6 rounded-2xl bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white font-bold text-sm border border-slate-800 transition-colors text-center">
                    Ya tengo cuenta
                </a>
            </div>

            <div class="mt-6 text-xs text-slate-500 flex items-center justify-center gap-6">
                <span>✓ Sin tarjeta de crédito</span>
                <span>✓ Listo en 60 segundos</span>
                <span>✓ Soporte en Colombia</span>
            </div>
        </div>
    </section>

    <!-- Key Features Section -->
    <section id="beneficios" class="py-20 bg-slate-900/60 border-y border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-xs font-black uppercase tracking-widest text-emerald-400 mb-2">¿Por qué Licorera POS?</h2>
                <h3 class="text-3xl sm:text-4xl font-black text-white tracking-tight">Construido para los retos reales de una licorera</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="card-night p-8 rounded-3xl bg-slate-900 border border-slate-800 space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-2xl font-bold">
                        🍺
                    </div>
                    <h4 class="text-lg font-black text-white">Reglas Reales de Cervezas</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Compra 10 cajas y vende en unidad, six-pack o canasta. El sistema descuenta con exactitud milimétrica las botellas base del inventario.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="card-night p-8 rounded-3xl bg-slate-900 border border-slate-800 space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-cyan-500/10 text-cyan-400 flex items-center justify-center text-2xl font-bold">
                        🍾
                    </div>
                    <h4 class="text-lg font-black text-white">Licores por Botella Completa</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Cero enredos con mililitros. Los licores (Aguardiente, Ron, Whisky) se controlan estrictamente por botella cerrada.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="card-night p-8 rounded-3xl bg-slate-900 border border-slate-800 space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-2xl font-bold">
                        🔒
                    </div>
                    <h4 class="text-lg font-black text-white">Arqueo a Ciegas Anti-Robo</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        El cajero cuenta su efectivo físico sin ver cuánto debería haber en caja. Los faltantes y sobrantes quedan registrados y auditados al instante.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="card-night p-8 rounded-3xl bg-slate-900 border border-slate-800 space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-green-500/10 text-green-400 flex items-center justify-center text-2xl font-bold">
                        📲
                    </div>
                    <h4 class="text-lg font-black text-white">Tickets por WhatsApp Gratis</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Envía el comprobante digital al WhatsApp del cliente con 1 solo toque. Ahorra en rollos de papel y moderniza la imagen de tu negocio sin costos de API.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="card-night p-8 rounded-3xl bg-slate-900 border border-slate-800 space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-purple-500/10 text-purple-400 flex items-center justify-center text-2xl font-bold">
                        🪑
                    </div>
                    <h4 class="text-lg font-black text-white">Mesas y Cuentas Abiertas</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Abre cuentas en mesas o barra. Los precios se congelan al agregarlos, evitando diferencias cuando cambias precios en el catálogo.
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="card-night p-8 rounded-3xl bg-slate-900 border border-slate-800 space-y-4">
                    <div class="w-12 h-12 rounded-2xl bg-rose-500/10 text-rose-400 flex items-center justify-center text-2xl font-bold">
                        ☁️
                    </div>
                    <h4 class="text-lg font-black text-white">Respaldo Total de Datos</h4>
                    <p class="text-xs text-slate-400 leading-relaxed">
                        Copias de seguridad del sistema para que nunca pierdas tu historial de ventas ni inventario si tu equipo sufre algún daño físico.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="planes" class="py-24">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="px-3.5 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 text-xs font-black uppercase tracking-wider">
                    Planes Transparentes en Pesos Colombianos
                </span>
                <h3 class="text-3xl sm:text-5xl font-black text-white tracking-tight mt-4">
                    Elige el plan ideal para tu licorera
                </h3>
                <p class="text-sm text-slate-400 mt-2">Todos los planes incluyen 14 días de prueba gratuita completa.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                <!-- Starter Plan -->
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-8 flex flex-col justify-between">
                    <div>
                        <h4 class="text-lg font-black text-white">Plan Básico</h4>
                        <p class="text-xs text-slate-400 mt-1">Para licoreras y estancos pequeños</p>
                        <div class="my-6">
                            <span class="text-4xl font-black font-mono text-white">$79.000</span>
                            <span class="text-xs text-slate-400 font-bold">/ mes COP</span>
                        </div>
                        <ul class="space-y-3 text-xs text-slate-300 mb-8">
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> Hasta 3 usuarios y cajeros</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> 1 Sede / Sucursal</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> 500 productos</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> Tickets por WhatsApp gratis</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> Arqueo de caja a ciegas</li>
                        </ul>
                    </div>
                    <a href="{{ route('business.register') }}?plan=starter" class="w-full py-3 px-4 rounded-xl bg-slate-800 hover:bg-slate-750 text-white font-bold text-xs text-center border border-slate-700 transition-colors">
                        Iniciar Prueba Gratis
                    </a>
                </div>

                <!-- Pro Plan (Featured) -->
                <div class="bg-gradient-to-b from-slate-900 to-slate-950 border-2 border-emerald-500 rounded-3xl p-8 flex flex-col justify-between relative shadow-2xl shadow-emerald-500/10">
                    <span class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-emerald-500 text-slate-950 font-black text-[10px] uppercase tracking-wider px-4 py-1 rounded-full shadow-lg">
                        MÁS POPULAR
                    </span>
                    <div>
                        <h4 class="text-lg font-black text-white">Plan Pro</h4>
                        <p class="text-xs text-slate-400 mt-1">Para licoreras estándar y bares con mesas</p>
                        <div class="my-6">
                            <span class="text-4xl font-black font-mono text-emerald-400">$129.000</span>
                            <span class="text-xs text-slate-400 font-bold">/ mes COP</span>
                        </div>
                        <ul class="space-y-3 text-xs text-slate-300 mb-8">
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> Hasta 10 usuarios y cajeros</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> 2 Sedes / Sucursales</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> 2.000 productos</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> Mesas y cuentas abiertas</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> Módulo de Fiados y Cartera</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> Auditoría en tiempo real</li>
                        </ul>
                    </div>
                    <a href="{{ route('business.register') }}?plan=pro" class="w-full py-3.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-black text-xs text-center shadow-lg shadow-emerald-600/30 transition-all">
                        PROBAR 14 DÍAS GRATIS
                    </a>
                </div>

                <!-- Enterprise Plan -->
                <div class="bg-slate-900 border border-slate-800 rounded-3xl p-8 flex flex-col justify-between">
                    <div>
                        <h4 class="text-lg font-black text-white">Plan Enterprise</h4>
                        <p class="text-xs text-slate-400 mt-1">Para cadenas de licoreras multi-sede</p>
                        <div class="my-6">
                            <span class="text-4xl font-black font-mono text-white">$199.000</span>
                            <span class="text-xs text-slate-400 font-bold">/ mes COP</span>
                        </div>
                        <ul class="space-y-3 text-xs text-slate-300 mb-8">
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> Hasta 30 usuarios</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> Hasta 5 Sedes</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> 10.000 productos</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> Soporte prioritario 24/7</li>
                            <li class="flex items-center gap-2"><span class="text-emerald-400 font-bold">✓</span> Exportación contable avanzada</li>
                        </ul>
                    </div>
                    <a href="{{ route('business.register') }}?plan=enterprise" class="w-full py-3 px-4 rounded-xl bg-slate-800 hover:bg-slate-750 text-white font-bold text-xs text-center border border-slate-700 transition-colors">
                        Iniciar Prueba Gratis
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Wompi Payment Methods Banner -->
    <section id="wompi" class="py-16 bg-slate-900/40 border-t border-slate-800">
        <div class="max-w-4xl mx-auto px-4 text-center space-y-6">
            <span class="text-xs font-black uppercase tracking-widest text-emerald-400">Pasarela Oficial</span>
            <h3 class="text-2xl sm:text-3xl font-black text-white">Membresías Automatizadas y Seguras con Wompi</h3>
            <p class="text-xs sm:text-sm text-slate-400 max-w-2xl mx-auto">
                Tu suscripción mensual se renueva sin fricciones mediante la pasarela de pagos oficial de <strong>Bancolombia</strong>: paga con <strong>Nequi</strong>, <strong>Tarjeta de Crédito</strong>, <strong>Botón Bancolombia</strong> o <strong>PSE</strong>.
            </p>
            <div class="inline-flex items-center gap-6 p-4 rounded-2xl bg-slate-900 border border-slate-800 flex-wrap justify-center text-xs font-bold text-slate-300">
                <span>🟢 Nequi</span>
                <span>🔵 Bancolombia</span>
                <span>💳 Tarjetas Visa / Mastercard</span>
                <span>🏦 PSE</span>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 border-t border-slate-800 text-center text-xs text-slate-500">
        <p>&copy; {{ date('Y') }} LICORERA POS. Diseñado por Mambacode para el comercio nocturno de Colombia.</p>
    </footer>

</body>
</html>
