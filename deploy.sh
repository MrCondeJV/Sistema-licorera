#!/usr/bin/env bash
# ==============================================================================
# Script de Despliegue Automatizado y Cero Errores - Licorera POS
# ==============================================================================
set -e

echo "🚀 Iniciando despliegue de Licorera POS en VPS..."

# 1. Poner aplicación en modo mantenimiento para evitar transacciones a medias
echo "🛑 Activando modo mantenimiento..."
php artisan down --retry=60 || true

# 2. Respaldar base de datos antes de cualquier cambio preventivo
echo "💾 Creando backup preventivo antes de actualizar..."
php artisan backup:auto --force || true

# 3. Descargar últimos cambios del repositorio
echo "📥 Descargando últimos cambios de git origin/main..."
git fetch origin main
git reset --hard origin/main

# 4. Instalar y optimizar dependencias de Composer (sin paquetes de desarrollo)
echo "📦 Instalando dependencias de PHP..."
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# 5. Ejecutar migraciones pendientes de forma atómica
echo "🗄️ Ejecutando migraciones de base de datos..."
php artisan migrate --force

# 6. Compilar assets de frontend (Tailwind / Vite)
if command -v npm &> /dev/null; then
    echo "🎨 Compilando assets frontend con Vite..."
    npm ci --prefer-offline 2>/dev/null || npm install
    npm run build
fi

# 7. Limpiar y reconstruir cachés optimizadas de producción
echo "⚡ Optimizando cachés de Laravel..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 8. Garantizar permisos correctos para Nginx/Apache y PHP-FPM
echo "🔒 Ajustando permisos de storage y bootstrap/cache..."
chmod -R 775 storage bootstrap/cache
if id "www-data" &>/dev/null; then
    chown -R www-data:www-data storage bootstrap/cache
fi

# 9. Reactivar aplicación
echo "🟢 Desactivando modo mantenimiento..."
php artisan up

echo "✅ ¡Despliegue finalizado con éxito y sin errores!"
