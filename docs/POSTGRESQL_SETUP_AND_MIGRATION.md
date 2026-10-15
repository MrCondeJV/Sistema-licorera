# 🐘 Guía Completa de Instalación de PostgreSQL en VPS y Migración de Datos

Esta guía detalla paso a paso cómo instalar, asegurar y optimizar **PostgreSQL 16** en tu servidor VPS (Ubuntu/Debian), cómo configurar Laravel para conectarse al nuevo motor, y cómo migrar toda la base de datos existente (inquilinos, productos, kardex, ventas, usuarios y configuración) sin perder datos.

---

## 📋 Índice
1. [¿Por qué PostgreSQL en vez de MySQL?](#1-por-qué-postgresql-en-vez-de-mysql)
2. [Requisitos Previos en el Servidor](#2-requisitos-previos-en-el-servidor)
3. [Instalación de PostgreSQL 16 en VPS](#3-instalación-de-postgresql-16-en-vps)
4. [Instalación de la Extensión PHP PostgreSQL](#4-instalación-de-la-extensión-php-postgresql)
5. [Creación de la Base de Datos y Usuario](#5-creación-de-la-base-de-datos-y-usuario)
6. [Configuración de Laravel (`.env`)](#6-configuración-de-laravel-env)
7. [Métodos de Migración de Datos](#7-métodos-de-migración-de-datos)
   - [Método 1: Comando Artisan Automático (Recomendado)](#método-1-comando-artisan-automático-recomendado)
   - [Método 2: Herramienta `pgloader` (Desde MySQL existente)](#método-2-herramienta-pgloader-desde-mysql-existente)
   - [Método 3: Exportación estructurada por Negocio](#método-3-exportación-estructurada-por-negocio)
8. [Optimización de Rendimiento para VPS](#8-optimización-de-rendimiento-para-vps)
9. [Copias de Seguridad Automáticas con `pg_dump`](#9-copias-de-seguridad-automáticas-con-pg_dump)
10. [Solución de Problemas Comunes](#10-solución-de-problemas-comunes)

---

## 1. ¿Por qué PostgreSQL en vez de MySQL?

* **Soporte y Estándar de la Industria**: PostgreSQL es el motor de base de datos relacional de código abierto más avanzado del mundo, completamente independiente y sin riesgo de cambios de licencia o abandono corporativo.
* **Aislamiento Multi-Tenant Superior**: Implementa concurrencia multiversión (**MVCC**) de altísima eficiencia, ideal para cuando múltiples licoreras facturan simultáneamente en horas pico.
* **Transacciones DDL Completas**: En PostgreSQL, las migraciones de base de datos (`php artisan migrate`) se ejecutan dentro de transacciones atómicas. Si una migración falla a mitad de camino, se revierte automáticamente sin dejar la base de datos en estado corrupto (algo que MySQL no permite).
* **Campos JSON y JSONB Nativos**: Rendimiento ultrarrápido para los registros de auditoría (`audit_logs`) y respuestas de pasarelas de pago como Wompi Bancolombia.

---

## 2. Requisitos Previos en el Servidor

* **Sistema Operativo**: Servidor VPS con **Ubuntu 22.04 LTS**, **Ubuntu 24.04 LTS** o **Debian 12**.
* **Acceso**: Conexión SSH con privilegios de `root` o usuario con `sudo`.
* **RAM Mínima**: 1 GB (recomendado 2 GB o superior para producción).

---

## 3. Instalación de PostgreSQL 16 en VPS

Conéctate a tu VPS por SSH y ejecuta los siguientes comandos para agregar el repositorio oficial de PostgreSQL (Pgdg) e instalar la versión 16:

```bash
# 1. Actualizar repositorios del sistema
sudo apt update && sudo apt upgrade -y

# 2. Instalar herramientas de soporte de certificados y claves
sudo apt install -y curl ca-certificates gnupg lsb-release

# 3. Importar la llave GPG oficial de PostgreSQL
sudo install -d /etc/apt/keyrings
curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc | sudo gpg --dearmor -o /etc/apt/keyrings/postgresql.gpg

# 4. Agregar el repositorio oficial de PostgreSQL
echo "deb [signed-by=/etc/apt/keyrings/postgresql.gpg] http://apt.postgresql.org/pub/repos/apt $(lsb_release -cs)-pgdg main" | sudo tee /etc/apt/sources.list.d/pgdg.list

# 5. Actualizar la lista de paquetes e instalar PostgreSQL 16
sudo apt update
sudo apt install -y postgresql-16 postgresql-contrib-16

# 6. Iniciar y habilitar el servicio para que arranque automáticamente
sudo systemctl enable postgresql
sudo systemctl start postgresql

# 7. Verificar que el servicio esté corriendo (debe decir 'active (running)')
sudo systemctl status postgresql
```

---

## 4. Instalación de la Extensión PHP PostgreSQL

Para que Laravel y PHP puedan comunicarse con PostgreSQL, debes instalar el módulo `pdo_pgsql`:

```bash
# Para PHP 8.2 (ajusta según tu versión: php8.3-pgsql o php8.4-pgsql)
sudo apt install -y php8.2-pgsql

# Si usas PHP 8.3:
# sudo apt install -y php8.3-pgsql

# Si usas PHP 8.4:
# sudo apt install -y php8.4-pgsql

# Reiniciar el servicio PHP-FPM y el servidor web (Nginx o Apache)
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
# O si usas Apache: sudo systemctl restart apache2
```

Verifica que la extensión esté activa ejecutando:
```bash
php -m | grep -i pdo_pgsql
```
*(Debe responder `pdo_pgsql`).*

---

## 5. Creación de la Base de Datos y Usuario

Accede a la consola de administración de PostgreSQL mediante el usuario del sistema `postgres`:

```bash
sudo -u postgres psql
```

Dentro de la consola `psql`, ejecuta las siguientes sentencias SQL (reemplaza `TuContrasenaSegura_2026` por una clave robusta):

```sql
-- 1. Crear la base de datos con codificación UTF-8
CREATE DATABASE licorera_pos WITH ENCODING 'UTF8' LC_COLLATE = 'C.UTF-8' LC_CTYPE = 'C.UTF-8';

-- 2. Crear el usuario del sistema POS
CREATE USER licorera_user WITH ENCRYPTED PASSWORD 'TuContrasenaSegura_2026';

-- 3. Otorgar propiedad y todos los privilegios sobre la base de datos
ALTER DATABASE licorera_pos OWNER TO licorera_user;
GRANT ALL PRIVILEGES ON DATABASE licorera_pos TO licorera_user;

-- 4. Conectarse a la base de datos recién creada
\c licorera_pos

-- 5. Otorgar permisos sobre el esquema público
GRANT ALL ON SCHEMA public TO licorera_user;
ALTER SCHEMA public OWNER TO licorera_user;

-- 6. Salir de la consola
\q
```

---

## 6. Configuración de Laravel (`.env`)

En el directorio raíz del proyecto (`/var/www/sistema-licorera` o tu ruta en el VPS), edita el archivo `.env`:

```bash
nano .env
```

Configura la sección de base de datos con los datos de PostgreSQL:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=licorera_pos
DB_USERNAME=licorera_user
DB_PASSWORD=TuContrasenaSegura_2026
```

Limpia la caché de configuración para aplicar los cambios de inmediato:

```bash
php artisan config:clear
php artisan cache:clear
```

---

## 7. Métodos de Migración de Datos

### Método 1: Comando Artisan Automático (Recomendado)

El sistema incluye un comando inteligente en consola desarrollado específicamente para transferir la base de datos completa a PostgreSQL:

```bash
php artisan db:migrate-to-postgres --fresh
```

**¿Qué hace este comando automáticamente?**
1. Comprueba la conexión activa con PostgreSQL en el puerto 5432.
2. Ejecuta `migrate:fresh` sobre PostgreSQL creando todas las tablas con sus índices nativos.
3. Desactiva temporalmente las restricciones de llaves foráneas (`session_replication_role = 'replica'`).
4. Lee todas las tablas de tu base de datos anterior (SQLite o MySQL) y transfiere los registros por bloques de 250 filas con barra de progreso en vivo.
5. Sincroniza y resetea las secuencias de auto-incremento de PostgreSQL (`setval(pg_get_serial_sequence())`) para que los nuevos IDs de ventas, productos y compras no colisionen.
6. Reactiva las restricciones de integridad (`session_replication_role = 'origin'`).

---

### Método 2: Herramienta `pgloader` (Desde MySQL existente)

Si tu base de datos MySQL en producción tiene cientos de miles de registros y deseas migrarla directamente:

```bash
# 1. Instalar pgloader en el VPS
sudo apt install -y pgloader

# 2. Crear un archivo de migración llamado mysql_to_postgres.load
nano mysql_to_postgres.load
```

Pega el siguiente contenido (ajustando tus credenciales de MySQL y PostgreSQL):

```lisp
LOAD DATABASE
     FROM mysql://root:TuClaveMySQL@127.0.0.1:3306/licorera_pos_mysql
     INTO postgresql://licorera_user:TuContrasenaSegura_2026@127.0.0.1:5432/licorera_pos

WITH include drop, create tables, create indexes, reset sequences,
     workers = 4, concurrency = 2,
     multiple readers per thread, rows per range = 50000

CAST type datetime to timestamptz drop default drop not null using zero-dates-to-null,
     type date drop not null drop default using zero-dates-to-null,
     type tinyint to boolean using tinyint-to-boolean;
```

Ejecuta la migración:
```bash
pgloader mysql_to_postgres.load
```

---

### Método 3: Exportación estructurada por Negocio

También puedes utilizar el nuevo módulo de **Copias de Seguridad (Backups)** integrado en el sistema:
1. Ingresa a la interfaz web como Superadministrador en `/superadmin/backups` (o `/backups` como administrador de local).
2. Selecciona **"Negocio en Específico"** y elige tu licorera.
3. Haz clic en **"Generar Respaldo Ahora"** para obtener el archivo JSON estructurado e independiente con todo el catálogo, inventario, ventas y clientes.
4. Puedes restaurar o importar licoreras individuales de forma modular cuando lo requieras.

---

## 8. Optimización de Rendimiento para VPS

Para servidores con 2 GB o 4 GB de RAM, ajusta la configuración de PostgreSQL para exprimir al máximo el rendimiento:

Edita el archivo de configuración:
```bash
sudo nano /etc/postgresql/16/main/postgresql.conf
```

Modifica o agrega los siguientes parámetros (valores recomendados para VPS de 2GB a 4GB RAM):

```ini
# Conexiones
max_connections = 100

# Memoria compartida y búferes
shared_buffers = 512MB
effective_cache_size = 1536MB
maintenance_work_mem = 128MB
work_mem = 16MB
wal_buffers = 16MB

# Checkpoints y escritura en disco
min_wal_size = 1GB
max_wal_size = 4GB
checkpoint_completion_target = 0.9

# Planificador de consultas para discos SSD en VPS
random_page_cost = 1.1
effective_io_concurrency = 200
```

Reinicia PostgreSQL para aplicar la optimización:
```bash
sudo systemctl restart postgresql
```

---

## 9. Copias de Seguridad Automáticas con `pg_dump`

Crea un script de respaldo diario automatizado que guarde un dump comprimido cada noche:

```bash
# 1. Crear directorio para almacenar respaldos
sudo mkdir -p /var/backups/licorerapos
sudo chown -R postgres:postgres /var/backups/licorerapos

# 2. Crear el script de respaldo
sudo nano /usr/local/bin/backup-licorera-postgres.sh
```

Pega el siguiente script bash:

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/licorerapos"
DATE=$(date +%Y_%m_%d_%H%M%S)
FILENAME="${BACKUP_DIR}/licorerapos_backup_${DATE}.sql.gz"

# Generar dump comprimido
sudo -u postgres pg_dump -d licorera_pos | gzip > "$FILENAME"

# Mantener solo los últimos 30 días de respaldos (eliminar más antiguos)
find "$BACKUP_DIR" -type f -name "*.sql.gz" -mtime +30 -delete

echo "Respaldo completado: $FILENAME"
```

Otorga permisos de ejecución:
```bash
sudo chmod +x /usr/local/bin/backup-licorera-postgres.sh
```

Agrégalo al cron para que se ejecute todos los días a las 3:00 AM:
```bash
sudo crontab -e
```
Agrega la línea:
```cron
0 3 * * * /usr/local/bin/backup-licorera-postgres.sh > /dev/null 2>&1
```

---

## 10. Solución de Problemas Comunes

### Error: `could not connect to server: Connection refused`
Verifica que PostgreSQL esté corriendo:
```bash
sudo systemctl status postgresql
sudo netstat -plnt | grep 5432
```

### Error: `driver [pgsql] not found`
Falta la extensión de PHP para PostgreSQL. Instálala y reinicia PHP-FPM:
```bash
sudo apt install php-pgsql
sudo systemctl restart php*-fpm
```

### Error: `password authentication failed for user "licorera_user"`
Verifica que la contraseña en `.env` coincida exactamente con la creada en PostgreSQL. Para cambiar la contraseña del usuario:
```bash
sudo -u postgres psql -c "ALTER USER licorera_user WITH PASSWORD 'TuNuevaContraseña';"
```

### Error: `permission denied for schema public`
Ocurre en PostgreSQL 15+ debido a cambios de permisos por defecto. Se soluciona ejecutando:
```bash
sudo -u postgres psql -d licorera_pos -c "GRANT ALL ON SCHEMA public TO licorera_user;"
```

---

¡Listo! Con esto tu instalación de PostgreSQL en el VPS estará blindada, optimizada para alta velocidad y con respaldos programados.
