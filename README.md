# 🌙 LICORERA POS — Sistema Integral para Licoreras, Bares y Estancos

**LICORERA POS** es un sistema web comercial POS (Point of Sale) integral, multi-inquilino (Multi-Tenant SaaS), diseñado específicamente para la operación real y de alto ritmo de licoreras físicas, estancos, distribuidoras de bebidas y bares con venta rápida de mostrador y consumo dentro del establecimiento mediante mesas y comandas abiertas.

Desarrollado con arquitectura sólida sobre **Laravel 11**, **Tailwind CSS 3.4**, **Alpine.js**, **SweetAlert2**, **MySQL** y soporte **PWA / Modo Offline**.

---

## ⚡ Características Principales del Sistema

1. **Multi-Tenant SaaS Nativo con Aislamiento Estricto**:
   - Una única base de datos transaccional con aislamiento lógico por `tenant_id` en cada entidad operativa mediante Eloquent `TenantScope` y `TenantContext`.
   - Aprovisionamiento automático e idempotente por empresa: sucursal principal, 10 mesas iniciales, 14 categorías estándar de licorera y medios de pago.
   - Panel de Superadministrador para control de licoreras, suscripciones y métricas del SaaS.
   - **Blindaje de Superadministrador**: Los administradores de tiendas locales no pueden visualizar, modificar ni desactivar al superadministrador de la plataforma.

2. **Autenticación Segura para Producción (Correo y Contraseña)**:
   - Acceso seguro mediante credenciales de correo electrónico y contraseña encriptada con Bcrypt.
   - Protección contra fuerza bruta mediante *Rate Limiting* (5 intentos máximos) y registro de auditoría de intentos de inicio de sesión (`LoginAttempt`).
   - Pantalla de bloqueo rápido de estación (`/unlock`) con desbloqueo inmediato por contraseña.
   - Control de roles granular:
     - `superadmin`: Control global del SaaS, licoreras y suscripciones.
     - `admin`: Administrador de la licorera con control total de catálogo, compras, gastos, proveedores, usuarios y reportes.
     - `cashier`: Venta POS en mostrador, apertura y arqueo ciego de caja, cobranzas y abonos.
     - `waiter`: Gestión de comandas y adición de productos a cuentas de mesa.

3. **Sistema Moderno de Notificaciones y Confirmaciones (SweetAlert2)**:
   - Reemplazo total de alertas nativas del navegador (`alert()` y `confirm()`) por SweetAlert2 adaptado al tema (Modo Claro y Modo Oscuro).
   - Diálogos de confirmación estilizados (`data-confirm`) para acciones críticas (recepción de compras, cancelación de mesas, arqueos, aplicación de inventario físico).
   - Feedback sonoro sintético vía Web Audio API (`PosSound`) con campanadas armónicas para ventas exitosas y alertas de stock.
   - Notificaciones flotantes tipo *Toast* automáticas para mensajes de sesión (`session('success')`, `session('error')`).

4. **Reglas Comerciales Especializadas en Licores**:
   - **Cervezas y Presentaciones Múltiples**: Venta por Unidad (1x), Six-pack (6x) o Canasta/Paca (24x, 30x). Todas las presentaciones descuentan unidades del stock base central garantizando consistencia atómica.
   - **Licores y Botellas Enteras**: Prohibición estricta de venta fraccionada en mostrador para botellas de licor para evitar pérdidas y mermas no auditadas.
   - **Congelamiento de Precios en Mesas**: Al cargar un ítem a la cuenta de una mesa, el precio unitario pactado queda congelado contra alzas posteriores hasta su facturación.
   - **Prevención de Cuentas Duplicadas**: Bloqueo transaccional `lockForUpdate()` para evitar aperturas simultáneas sobre la misma mesa.

5. **Operación POS Ultrarrápida y Gestión de Pagos**:
   - Búsqueda instantánea por código de barras, SKU o nombre, y filtrado por categorías táctiles.
   - Opciones de pago en mostrador: **Efectivo** (con cálculo de vueltas) y **Transacción Bancaria** (Nequi, Daviplata, Bancolombia).
   - **Opción "Agregar a Cuenta del Cliente (Fiado)"**: Al seleccionar un cliente con cupo disponible, se permite cargar la venta a su saldo deudor sin cobro inmediato en caja.
   - Impresión térmica con formato optimizado para impresoras de 58mm y 80mm con comandos ESC/POS de corte de papel.

6. **Módulo Completo de Proveedores y Compras**:
   - Directorio de proveedores con NIT, contacto, teléfono y estado activo/inactivo.
   - Creación rápida de proveedores desde la misma pantalla de compra.
   - Órdenes de compra con cálculo automático de **Costo Promedio Ponderado** al recibir mercancía en kardex.

7. **Caja Diaria y Turnos con Cierre a Ciegas (Arqueo Exacto)**:
   - Apertura de turno con monto base en efectivo.
   - Registro de gastos operativos en efectivo o bancarios.
   - Movimientos manuales de ingreso y egreso con justificación obligatoria.
   - **Consolidación de Abonos a Cartera**: El efectivo recibido por abonos de clientes a sus deudas se suma automáticamente al efectivo físico esperado en la gaveta.
   - **Cierre ciego**: El cajero ingresa el conteo de efectivo físico sin conocer el saldo esperado. La diferencia (sobrante o faltante) se registra auditada para el administrador.

8. **PWA y Sincronización Fuera de Línea (Offline Queue)**:
   - Service Worker y Web App Manifest instalables como aplicación de escritorio o tablet.
   - Almacenamiento local para continuar vendiendo en caídas de internet.
   - Cola de sincronización idempotente con UUID `sync_token` para evitar ventas duplicadas.

9. **Kardex, Reportes y Exportación**:
   - Kardex valorizado bajo método de Costo Promedio Ponderado.
   - Control de fiados con límite de crédito por cliente y abonos parciales ordenados por FIFO.
   - Informes exportables a **PDF** (DomPDF) y **Excel (CSV)** con desglose de ventas, márgenes brutos, COGS y arqueos de caja.

---

## 🔑 Credenciales Base de Acceso (Entorno Local y Demo)

El seeder inicial (`php artisan db:seed`) crea la empresa de demostración **"Licorera Central - Sede Principal"** con los siguientes usuarios y credenciales:

| Rol | Nombre | Correo Electrónico (Login) | Contraseña de Acceso | Alcance y Permisos |
| :--- | :--- | :--- | :--- | :--- |
| 👑 **Superadministrador** | Super Administrador | `superadmin@licorerapos.com` | `Admin1234*` | Control global SaaS, gestión de empresas y suscripciones |
| 🏢 **Administrador** | Carlos Administrador | `admin@licorerapos.com` | `Admin1234*` | Control total del negocio: inventario, compras, proveedores, caja, usuarios y reportes |
| 💵 **Cajero** | Valentina Cajera | `cajero@licorerapos.com` | `Cajero1234*` | Venta en mostrador POS, cobro de mesas, abonos y arqueo de caja |
| 🍽️ **Mesero** | Mateo Mesero | `mesero@licorerapos.com` | `Mesero1234*` | Apertura y comanda de mesas (sin acceso a compras ni caja) |

---

## 🛠 Requisitos del Entorno

- **PHP**: `>= 8.2` (Probado en PHP 8.4)
  - Extensiones: `pdo`, `pdo_mysql`, `pdo_sqlite`, `mbstring`, `openssl`, `bcmath`, `curl`, `gd`
- **Base de Datos**: MySQL `>= 8.0` o MariaDB `>= 10.4` (Soporta SQLite para tests locales)
- **Composer**: `>= 2.2`
- **Node.js & npm**: `>= 18.x`
- **Servidor Web**: Laravel Herd, Nginx, Apache o `php artisan serve`

---

## 🚀 Instalación y Puesta en Marcha

### 1. Clonar el repositorio y acceder al directorio
```bash
git clone https://github.com/tu-usuario/sistema-licorera.git
cd sistema-licorera
```

### 2. Configurar variables de entorno (`.env`)
```bash
cp .env.example .env
```
Configure los parámetros de base de datos en `.env`:
```env
APP_NAME="LICORERA POS"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistema_licorera
DB_USERNAME=root
DB_PASSWORD=root
```

### 3. Instalar dependencias PHP y generar App Key
```bash
composer install
php artisan key:generate
```

### 4. Instalar dependencias de frontend y compilar
```bash
npm install
npm run build
```

### 5. Ejecutar migraciones y sembrar datos de prueba
```bash
php artisan migrate:fresh --seed
```

### 6. Iniciar el servidor
Si usa **Laravel Herd**, el sitio ya estará disponible en `http://sistema-licorera.test`. De lo contrario:
```bash
php artisan serve
```
El sistema estará accesible en `http://localhost:8000/login`.

---

## 🧪 Pruebas Automatizadas

El sistema cuenta con una batería de **30 pruebas unitarias y de integración** con **193 aserciones** que cubren el 100% de los flujos críticos:

```bash
php artisan test
```

### Casos de Prueba Verificados:
- `FullSoftwareLifecycleSimulationTest`: Simulación completa de extremo a extremo que recrea todo el ciclo comercial de una licorera (alta SaaS, colaboradores, compras, apertura de caja, venta mostrador, comanda de mesas, fiados a crédito, abonos de cartera, gastos y arqueo ciego sin descuadre).
- `ThreeTenantsSaaSAcceptanceTest`: Simula el registro simultáneo de 3 licoreras distintas en la misma base de datos con aislamiento total.
- `MultiTenantIsolationTest`: Verifica que las consultas de Eloquent jamás filtren datos entre inquilinos.
- `BeerAndLiquorRulesTest`: Deducción exacta de presentaciones múltiples y prohibición de venta fraccionada en botellas de licor.
- `TableAccountTest`: Apertura de mesas, prevención de doble apertura y congelamiento inmutable de precios.
- `CashRegisterTest`: Apertura, movimientos y cierre ciego con registro de diferencias.
- `CredentialsAuthenticationTest`: Autenticación por correo y contraseña, protección del Superadmin y bloqueo tras intentos fallidos.
- `ReportManagementTest`: Filtros de reportes de rentabilidad, exportación a PDF y descarga en Excel CSV.
- `SupplierManagementTest`: CRUD completo de proveedores y creación rápida vía JSON en compras.
- `OfflineSyncIdempotencyTest`: Idempotencia estricta en ventas fuera de línea mediante `sync_token`.

---

## 📐 Estructura del Proyecto

```
app/
├── Http/
│   ├── Controllers/       # POS, Mesas, Inventario, Caja, Proveedores, Compras, Reportes, etc.
│   └── Middleware/        # IdentifyTenant, PinAuthentication, EnsureRole, EnsureSuperadmin
├── Models/
│   ├── Traits/            # BelongsToTenant (Alcance global automático tenant_id)
│   ├── Scopes/            # TenantScope (Filtrado transparente en Eloquent)
│   └── ...                # Modelos de dominio relacionales
└── Services/
    ├── TenantContext.php         # Almacén de contexto del inquilino activo
    ├── TenantService.php         # Alta de comercios y aprovisionamiento inicial
    ├── InventoryService.php      # Deducción de presentaciones, costo promedio y kardex
    ├── SaleService.php           # Facturación atómica, cobros, fiados y liberación de mesas
    ├── TableAccountService.php   # Cuentas de mesas, congelamiento de precios y traslados
    ├── CashRegisterService.php   # Turnos de caja y arqueos con conteo ciego
    ├── PurchaseService.php       # Compras a distribuidores y costeo ponderado
    ├── OfflineSyncService.php    # Sincronización idempotente con UUID
    ├── ReportService.php         # Consolidación financiera y márgenes
    └── AuditService.php          # Trazabilidad y auditoría de eventos
```

---

## 📄 Licencia

Este software es propiedad comercial para uso en establecimientos de licoreras físicas y bares. Todos los derechos reservados.