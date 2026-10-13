# 🌙 LA NOCHE — Sistema POS Integral para Licoreras

**LA NOCHE** es un sistema web comercial POS (Point of Sale) integral y multi-inquilino (Multi-Tenant SaaS), diseñado específicamente para la operación real y de alto ritmo de licoreras físicas, estancos y bares con venta rápida de mostrador y consumo dentro del establecimiento mediante mesas y cuentas abiertas.

Desarrollado con arquitectura sólida sobre **Laravel 11**, **Tailwind CSS 3.4**, **Alpine.js**, **MySQL** y soporte **PWA / Modo Offline**.

---

## ⚡ Características Principales

1. **Multi-Tenant SaaS Nativo (Aislamiento Total)**:
   - Una única base de datos transaccional con aislamiento lógico estricto por `tenant_id` en cada entidad operativa mediante Eloquent `TenantScope` y `TenantContext`.
   - Soporte para onboarding autoservicio con provisionamiento idempotente de sucursal principal, 10 mesas iniciales, categorías estándar y medios de pago.
   - Panel de Superadministrador para control de suscripciones, límites de mesas, productos y estado de tiendas.

2. **Autenticación Táctil con PIN de 4 Dígitos**:
   - Teclado numérico táctil optimizado para pantallas táctiles y tablets.
   - Algoritmo de bloqueo por intentos fallidos (bloqueo por 60 segundos tras fallos repetidos con rate limiting).
   - Roles diferenciados con control granular de accesos:
     - `superadmin`: Control de la plataforma SaaS y suscripciones.
     - `admin`: Dueño o administrador del negocio con acceso ilimitado.
     - `cashier`: Venta POS en mostrador, apertura y arqueo ciego de caja, cobranzas.
     - `waiter`: Gestión de comandas y adición de productos a cuentas de mesa únicamente.

3. **Reglas Comerciales Especializadas en Licores**:
   - **Cervezas y Presentaciones Múltiples**: Venta por Unidad (1x), Six-pack (6x) o Canasta/Paca (24x, 30x). Todas las ventas descuentan unidades del stock base central garantizando consistencia atómica.
   - **Licores y Botellas Enteras**: Prohibición de venta fraccionada de botellas enteras en mostrador para evitar pérdidas y descuadres de inventario.
   - **Congelamiento de Precios en Mesas**: Al cargar un ítem a la cuenta de una mesa, el precio unitario pactado queda congelado contra alzas posteriores hasta su facturación.
   - **Prevención de Cuentas Duplicadas**: Bloqueo estricto con transacciones `lockForUpdate()` para evitar aperturas simultáneas sobre la misma mesa.

4. **Operación POS Ultrarrápida**:
   - Búsqueda instantánea por código de barras o nombre y filtrado por categorías táctiles.
   - Atajos de teclado físicos (`F2` cobrar, `F4` buscar, `F7` limpiar, `ESC` cerrar modal).
   - Feedback sonoro sintético vía Web Audio API (chime de confirmación y alerta de stock/bloqueo sin dependencias de archivos de audio externos).
   - Pagos mixtos (Efectivo, Datáfono, Transferencias Nequi/Daviplata, Crédito de cliente).
   - Impresión térmica con formato optimizado para impresoras de 58mm y 80mm con comandos ESC/POS de corte de papel.

5. **Caja Diaria y Turnos con Cierre a Ciegas**:
   - Apertura de turno con monto base.
   - Movimientos manuales de ingreso y egreso con justificación obligatoria.
   - **Cierre ciego**: El cajero ingresa el conteo de efectivo físico sin conocer el saldo esperado por sistema. La diferencia (sobrante o faltante) se registra auditada para el administrador.
   - Segregación estricta entre efectivo físico y pagos electrónicos verificados.

6. **PWA y Sincronización Fuera de Línea (Offline Queue)**:
   - Service Worker y Web App Manifest instalables como aplicación de escritorio o tablet.
   - Almacenamiento local mediante IndexedDB / LocalStorage para continuar vendiendo en caídas de internet.
   - Cola de sincronización idempotente con UUID `sync_token` que garantiza que ninguna venta se duplique al recuperar la conectividad.

7. **Kardex, Compras, Créditos (Fiados) y Reportes**:
   - Kardex valorizado bajo método de Costo Promedio Ponderado.
   - Registro de compras a proveedores con actualización automática de existencias.
   - Control de fiados con límite de crédito por cliente y abonos parciales.
   - Informes exportables a PDF (DomPDF) y Excel con detalle de ventas, márgenes brutos, productos más vendidos y arqueos de caja.

---

## 🛠 Requisitos del Entorno

- **PHP**: `>= 8.2` (Probado en PHP 8.4)
  - Extensiones requeridas: `pdo`, `pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `curl`, `gd`
- **Base de Datos**: MySQL `>= 8.0` o MariaDB `>= 10.4`
- **Composer**: `>= 2.2`
- **Node.js & npm**: `>= 18.x`
- **Servidor Web**: Laravel Herd, Nginx, Apache o `php artisan serve`

---

## 🚀 Instalación y Puesta en Marcha

### 1. Clonar el repositorio y acceder a la carpeta
```bash
git clone https://github.com/tu-usuario/sistema-licorera.git
cd sistema-licorera
```

### 2. Configurar variables de entorno (`.env`)
Copie el archivo de ejemplo y configure sus credenciales de base de datos:
```bash
cp .env.example .env
```
Asegúrese de configurar los parámetros de base de datos en `.env`:
```env
APP_NAME="LA NOCHE POS"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lanoche_pos
DB_USERNAME=root
DB_PASSWORD=root
```

### 3. Instalar dependencias PHP y generar clave
```bash
composer install
php artisan key:generate
```

### 4. Compilar assets de frontend (Tailwind CSS 3.4 y Alpine.js)
```bash
npm install
npm run build
```

### 5. Ejecutar migraciones y sembrar datos de prueba
```bash
php artisan migrate:fresh --seed
```

### 6. Iniciar el servidor local
Si usa **Laravel Herd**, el sitio ya estará disponible en `http://sistema-licorera.test`. De lo contrario, ejecute:
```bash
php artisan serve
```
El sistema estará accesible en `http://localhost:8000`.

---

## 🔑 Credenciales de Acceso y Demostración

El seeder inicial crea la licorera de demostración **"Licorera La Noche - Sede Parque Lleras"** junto con los siguientes usuarios con acceso mediante PIN de 4 dígitos:

| Rol | Nombre | Correo Electrónico | PIN Táctil | Permisos Principales |
| :--- | :--- | :--- | :--- | :--- |
| **Superadministrador** | Superadministrador La Noche | `superadmin@lanoche.pos` | **`9999`** | Control global de licoreras, suscripciones y métricas del SaaS |
| **Administrador** | Carlos Administrador | `admin@lanoche.pos` | **`1234`** | Control total del negocio: inventario, precios, compras, reportes, usuarios |
| **Cajero** | Valentina Cajera | `cajero@lanoche.pos` | **`2222`** | Venta en mostrador (POS), cobro de mesas, apertura y arqueo ciego de caja |
| **Mesero** | Mateo Mesero | `mesero@lanoche.pos` | **`3333`** | Apertura y adición de pedidos a cuentas de mesa (sin acceso a caja ni compras) |

---

## 🧪 Pruebas Automatizadas

El sistema cuenta con una suite completa de pruebas unitarias y de integración que verifican la integridad multi-inquilino, las reglas de licorería y la consistencia financiera:

```bash
php artisan test
```

### Casos de Prueba Verificados:
- `ThreeTenantsSaaSAcceptanceTest`: Simula el registro simultáneo de 3 licoreras distintas en la misma base de datos, garantizando aislamiento estricto de catálogos, mesas, cuentas y ventas.
- `MultiTenantIsolationTest`: Verifica que las consultas automáticas de Eloquent jamás filtren datos entre inquilinos.
- `BeerAndLiquorRulesTest`:
  - Deducción exacta de unidades base al vender six-packs (6x) y canastas (24x).
  - Rechazo de ventas fraccionadas para botellas de licor.
  - Bloqueo de ventas que superen el stock disponible (sin stock negativo).
- `TableAccountTest`:
  - Apertura de mesas y prevención de doble apertura sobre la misma mesa.
  - Congelamiento inmutable de precios al agregar ítems a una mesa.
- `CashRegisterTest`:
  - Apertura de turno de caja, movimientos de efectivo y arqueo con conteo ciego y registro de discrepancias.
- `PinAuthenticationTest`:
  - Autenticación táctil por PIN, rechazo de PIN inválido y restricción de roles (mesero bloqueado en configuración administrativa).
- `OfflineSyncIdempotencyTest`:
  - Idempotencia estricta en la sincronización de ventas fuera de línea mediante `sync_token`.

---

## 📐 Arquitectura del Sistema

```
app/
├── Http/
│   ├── Controllers/       # Controladores web (POS, Mesas, Inventario, Caja, Reportes, etc.)
│   └── Middleware/        # IdentifyTenant, PinAuthentication, EnsureRole, EnsureSuperadmin
├── Models/
│   ├── Traits/            # BelongsToTenant (Alcance global automático tenant_id)
│   ├── Scopes/            # TenantScope (Filtrado transparente en Eloquent)
│   └── ...                # 37 Modelos relacionales
└── Services/
    ├── TenantContext.php         # Almacén de contexto del inquilino activo
    ├── TenantService.php         # Alta de comercios y aprovisionamiento inicial
    ├── InventoryService.php      # Deducción de presentaciones, costo promedio y kardex
    ├── SaleService.php           # Facturación atómica, cobros mixtos y liberación de mesas
    ├── TableAccountService.php   # Cuentas de mesas, congelamiento de precios y traslados
    ├── CashRegisterService.php   # Turnos de caja y arqueos con conteo ciego
    ├── PurchaseService.php       # Compras a distribuidores
    ├── OfflineSyncService.php    # Sincronización idempotente con UUID
    ├── ReportService.php         # Consolidación financiera y márgenes
    └── AuditService.php          # Trazabilidad y auditoría de eventos
```

---

## 📱 Modo PWA y Venta Fuera de Línea

1. **Instalación**: Al ingresar desde Google Chrome o Microsoft Edge, presione el ícono de instalación en la barra de direcciones o el aviso en pantalla para instalar "LA NOCHE POS" como app de escritorio independiente.
2. **Operación Offline**: Si la conexión a internet se interrumpe, el sistema almacena las transacciones localmente con un `sync_token` único (UUID).
3. **Sincronización**: Al restablecerse el acceso, el Service Worker sincroniza las ventas en segundo plano contra `/offline/sync` sin intervención del usuario y sin duplicar transacciones.

---

## 📄 Licencia

Este software es propiedad comercial para uso en establecimientos de licoreras físicas. Todos los derechos reservados.