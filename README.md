# 🌙 LICORERA POS — Sistema Integral para Licoreras, Bares y Estancos

**LICORERA POS** es un sistema web comercial POS (Point of Sale) integral, multi-inquilino (Multi-Tenant SaaS), diseñado específicamente para la operación real y de alto ritmo de licoreras físicas, estancos, distribuidoras de bebidas y bares con venta rápida de mostrador y consumo dentro del establecimiento mediante mesas y comandas abiertas.

Desarrollado con arquitectura sólida sobre **Laravel 11**, **Tailwind CSS 3.4**, **Alpine.js**, **SweetAlert2**, **PostgreSQL** (con soporte SQLite para pruebas) y soporte **PWA / Modo Offline**.

> 🐘 **Documentación PostgreSQL y Migración**: Consulta la guía paso a paso en [Guía de Instalación de PostgreSQL en VPS y Migración de Datos](docs/POSTGRESQL_SETUP_AND_MIGRATION.md).

---

## ⚡ Características Principales del Sistema

1. **Multi-Tenant SaaS Nativo con Aislamiento Estricto**:
   - Una única base de datos transaccional con aislamiento lógico por `tenant_id` en cada entidad operativa mediante Eloquent `TenantScope` y `TenantContext`.
   - Aprovisionamiento automático e idempotente por empresa: sucursal principal, 10 mesas iniciales, 14 categorías estándar de licorera y medios de pago.
   - Panel de Superadministrador para control de licoreras, suscripciones y métricas del SaaS.
   - **Blindaje de Superadministrador**: Los administradores de tiendas locales no pueden visualizar, modificar ni desactivar al superadministrador de la plataforma.

2. **Control de Membresías, Vencimientos y Pagos SaaS (Tenants & Superadmin)**:
   - **Para Administradores de Licorera (`/billing`)**:
     - Visualización en tiempo real del estado de cuenta (Activa, Prueba 14 días, Período de Gracia o Suspendida).
     - Tarjetas KPI con la **Fecha Exacta de Vencimiento de la Membresía** y el **Contador de Días Restantes**.
     - Historial completo de pagos y renovaciones con Wompi Bancolombia (fecha, referencia, monto, ID de transacción y estado).
   - **Para el Superadministrador (`/superadmin` y `/superadmin/payments`)**:
     - **Columna de Vencimiento en Empresas**: Monitoreo directo en el listado maestro de cuándo vence cada licorera, días restantes y estado de corte.
     - **Módulo de Histórico Global de Pagos SaaS**: Auditoría de todos los pagos de todas las licoreras de la plataforma, métricas de recaudo total en COP, transacciones aprobadas/pendientes y filtros avanzados por negocio, estado, plan y rango de fechas.

3. **Copias de Seguridad (Backups Generales vs Específicos por Negocio)**:
   - **🌐 Copia General (De Todos los Equipos / Toda la Plataforma)**: Genera una instantánea completa de la base de datos de la plataforma con todas las licoreras, usuarios y ventas.
   - **🏢 Negocio en Específico**: Permite extraer de manera aislada e independiente en un archivo JSON estructurado toda la información de una licorera seleccionada (catálogo, kardex, existencias por sede, compras, proveedores, clientes, deudas, mesas, ventas y arqueos de caja).
   - Registro en auditoría y administración de descargas y eliminación segura.

4. **Navegación Fluida e Impersonación de Superadministrador**:
   - Acceso maestro del Superadministrador a cualquier licorera en modo impersonación.
   - Banner persistente y visible en la barra lateral de escritorio, header móvil y drawer para retornar con un solo clic al panel SaaS (`/superadmin/leave`).
   - Menú de navegación inferior móvil adaptado según el rol (modo Superadmin o modo Licorera) sin bloqueos ni corrupción de sesión.

5. **Autenticación Segura para Producción (Correo y Contraseña)**:
   - Acceso seguro mediante credenciales de correo electrónico y contraseña encriptada con Bcrypt.
   - Protección contra fuerza bruta mediante *Rate Limiting* (5 intentos máximos) y registro de auditoría de intentos de inicio de sesión (`LoginAttempt`).
   - Pantalla de bloqueo rápido de estación (`/unlock`) con desbloqueo inmediato por contraseña.
   - Control de roles granular:
     - `superadmin`: Control global del SaaS, licoreras y suscripciones.
     - `admin`: Administrador de la licorera con control total de catálogo, compras, gastos, proveedores, usuarios y reportes.
     - `cashier`: Venta POS en mostrador, apertura y arqueo ciego de caja, cobranzas y abonos.
     - `waiter`: Gestión de comandas y adición de productos a cuentas de mesa.

6. **Sistema Moderno de Notificaciones y Confirmaciones (SweetAlert2)**:
   - Reemplazo total de alertas nativas del navegador (`alert()` y `confirm()`) por SweetAlert2 adaptado al tema (Modo Claro y Modo Oscuro).
   - Diálogos de confirmación estilizados (`data-confirm`) para acciones críticas (recepción de compras, cancelación de mesas, arqueos, aplicación de inventario físico).
   - Feedback sonoro sintético vía Web Audio API (`PosSound`) con campanadas armónicas para ventas exitosas y alertas de stock.
   - Notificaciones flotantes tipo *Toast* automáticas para mensajes de sesión (`session('success')`, `session('error')`).

7. **Reglas Comerciales Especializadas en Licores**:
   - **Cervezas y Presentaciones Múltiples**: Venta por Unidad (1x), Six-pack (6x) o Canasta/Paca (24x, 30x). Todas las presentaciones descuentan unidades del stock base central garantizando consistencia atómica.
   - **Licores y Botellas Enteras**: Prohibición estricta de venta fraccionada en mostrador para botellas de licor para evitar pérdidas y mermas no auditadas.
   - **Congelamiento de Precios en Mesas**: Al cargar un ítem a la cuenta de una mesa, el precio unitario pactado queda congelado contra alzas posteriores hasta su facturación.
   - **Prevención de Cuentas Duplicadas**: Bloqueo transaccional `lockForUpdate()` para evitar aperturas simultáneas sobre la misma mesa.

8. **Operación POS Ultrarrápida y Gestión de Pagos**:
   - Búsqueda instantánea por código de barras, SKU o nombre, y filtrado por categorías táctiles.
   - Opciones de pago en mostrador: **Efectivo** (con cálculo de vueltas) y **Transacción Bancaria** (Nequi, Daviplata, Bancolombia).
   - **Opción "Agregar a Cuenta del Cliente (Fiado)"**: Al seleccionar un cliente con cupo disponible, se permite cargar la venta a su saldo deudor sin cobro inmediato en caja.
   - Impresión térmica con formato optimizado para impresoras de 58mm y 80mm con comandos ESC/POS de corte de papel.

9. **Módulo Completo de Proveedores y Compras**:
   - Directorio de proveedores con NIT, contacto, teléfono y estado activo/inactivo.
   - Creación rápida de proveedores desde la misma pantalla de compra.
   - Órdenes de compra con cálculo automático de **Costo Promedio Ponderado** al recibir mercancía en kardex.

10. **Caja Diaria y Turnos con Cierre a Ciegas (Arqueo Exacto)**:
    - Apertura de turno con monto base en efectivo.
    - Registro de gastos operativos en efectivo o bancarios.
    - Movimientos manuales de ingreso y egreso con justificación obligatoria.
    - **Consolidación de Abonos a Cartera**: El efectivo recibido por abonos de clientes a sus deudas se suma automáticamente al efectivo físico esperado en la gaveta.
    - **Cierre ciego**: El cajero ingresa el conteo de efectivo físico sin conocer el saldo esperado. La diferencia (sobrante o faltante) se registra auditada para el administrador.

11. **PWA y Sincronización Fuera de Línea (Offline Queue)**:
    - Service Worker y Web App Manifest instalables como aplicación de escritorio o tablet.
    - Almacenamiento local para continuar vendiendo en caídas de internet.
    - Cola de sincronización idempotente con UUID `sync_token` para evitar ventas duplicadas.

12. **Kardex, Reportes y Exportación**:
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
  - Extensiones: `pdo`, `pdo_pgsql`, `pdo_sqlite`, `mbstring`, `openssl`, `bcmath`, `curl`, `gd`
- **Base de Datos**: PostgreSQL `>= 14` (Recomendado PostgreSQL 16 para producción; soporte de SQLite para pruebas locales)
- **Composer**: `>= 2.2`
- **Node.js & npm**: `>= 18.x`
- **Servidor Web**: Nginx, Apache, Laravel Herd o `php artisan serve`

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
Configure los parámetros de PostgreSQL en `.env`:
```env
APP_NAME="LICORERA POS"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=licorera_pos
DB_USERNAME=postgres
DB_PASSWORD=secret
```

*(Si deseas utilizar SQLite para desarrollo local rápido, define `DB_CONNECTION=sqlite`).*

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

### 6. Migrar datos existentes a PostgreSQL (si aplica)
Si vienes de SQLite o MySQL y deseas pasar todos los datos al nuevo PostgreSQL:
```bash
php artisan db:migrate-to-postgres --fresh
```

### 7. Iniciar el servidor
```bash
php artisan serve
```
El sistema estará accesible en `http://localhost:8000/login`.

---

## 🧪 Pruebas Automatizadas

El sistema cuenta con una batería de **63 pruebas unitarias y de integración** con **505 aserciones** que cubren el 100% de los flujos críticos:

```bash
php artisan test
```

### Casos de Prueba Verificados:
- `MembershipExpirationAndBackupManagementTest`: Verificación de fecha de vencimiento de membresías, histórico global de pagos para Superadmin con filtros, histórico propio del tenant, generación de backups generales y específicos por negocio en JSON estructurado, y navegación/impersonación sin bugs.
- `WompiBillingAndSaaSTrialTest`: Ciclo de vida SaaS, prueba gratuita de 14 días, webhook de Wompi Bancolombia, período de gracia de 5 días y bloqueo automático por mora.
- `SuperadminCompanyManagementTest`: CRUD de licoreras, aprovisionamiento automático e impersonación segura.
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
├── Console/
│   └── Commands/          # MigrateToPostgresCommand (db:migrate-to-postgres)
├── Http/
│   ├── Controllers/       # POS, Mesas, Inventario, Caja, Billing, Backups, Superadmin, etc.
│   └── Middleware/        # IdentifyTenant, PinAuthentication, EnsureRole, EnsureSuperadmin
├── Models/
│   ├── Traits/            # BelongsToTenant (Alcance global automático tenant_id)
│   ├── Scopes/            # TenantScope (Filtrado transparente en Eloquent)
│   └── ...                # Modelos de dominio relacionales (Tenant, Plan, Product, Sale, etc.)
├── Services/
│   ├── TenantContext.php         # Almacén de contexto del inquilino activo
│   ├── TenantService.php         # Alta de comercios y aprovisionamiento inicial
│   ├── InventoryService.php      # Deducción de presentaciones, costo promedio y kardex
│   ├── SaleService.php           # Facturación atómica, cobros, fiados y liberación de mesas
│   ├── TableAccountService.php   # Cuentas de mesas, congelamiento de precios y traslados
│   ├── CashRegisterService.php   # Turnos de caja y arqueos con conteo ciego
│   ├── PurchaseService.php       # Compras a distribuidores y costeo ponderado
│   ├── OfflineSyncService.php    # Sincronización idempotente con UUID
│   ├── ReportService.php         # Consolidación financiera y márgenes
│   ├── WompiService.php          # Integración de pagos con Wompi Bancolombia
│   └── AuditService.php          # Trazabilidad y auditoría de eventos
docs/
└── POSTGRESQL_SETUP_AND_MIGRATION.md # Guía detallada de instalación en VPS y migración a Postgres
```

---

## 📄 Licencia

Este software es propiedad comercial para uso en establecimientos de licoreras físicas y bares. Todos los derechos reservados.