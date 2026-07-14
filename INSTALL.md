# Installation Guide

> **Version:** 1.2.0 | **Last Updated:** 2026-07-14

Guia paso a paso para instalar y ejecutar MARAChain en tu entorno local o VPS de produccion.

---

## Prerequisites

### Requisitos minimos

| Componente | Version | Verificacion |
|------------|---------|-------------|
| **PHP** | >= 8.2 (recomendado 8.5) | `php -v` |
| **Composer** | >= 2.x | `composer --version` |
| **MySQL** | >= 8.0 | `mysql --version` |
| **Git** | >= 2.x | `git --version` |

### Extensiones PHP requeridas

```bash
# Verificar extensiones instaladas
php -m | grep -E "openssl|sodium|intl|mbstring|json|curl|pdo_mysql|fileinfo|xml|ctype|dom|simplexml|iconv"
```

**Extensiones obligatorias:**
- `openssl` — criptografia
- `sodium` — cifrado AEAD (libsodium)
- `intl` — internacionalizacion
- `mbstring` — manejo de strings multibyte
- `json` — serializacion JSON
- `curl` — peticiones HTTP
- `pdo_mysql` — conexion MySQL
- `fileinfo` — deteccion de tipos MIME

**Para desarrollo:**
- `xdebug` — cobertura de codigo y depuracion
- `sqlite3` — tests con SQLite :memory:

### Instalar extensiones (Debian/Ubuntu)

```bash
sudo apt update
sudo apt install -y \
    php8.5-cli \
    php8.5-common \
    php8.5-mysql \
    php8.5-sqlite3 \
    php8.5-curl \
    php8.5-mbstring \
    php8.5-xml \
    php8.5-intl \
    php8.5-sodium \
    php8.5-zip \
    php8.5-gd

# Verificar
php -v
php -m
```

---

## Step-by-Step Installation

### 1. Clone Repository

```bash
git clone git@github.com:your-org/marachain.git
cd marachain
```

### 2. Install Dependencies

```bash
cd wwwroot
composer install
```

**Opcion para produccion** (sin dependencias de desarrollo):

```bash
composer install --no-dev --optimize-autoloader
```

### 3. Verify Installation

```bash
# Verificar que composer instalo correctamente
ls -la vendor/

# Verificar que spark funciona
php spark list
```

---

## Database Setup

### 4. Create MySQL Database

```sql
-- Conectar a MySQL como root
mysql -u root -p

-- Crear base de datos y usuario
CREATE DATABASE marachain CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
CREATE USER 'marachain_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON marachain.* TO 'marachain_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 5. Configure Environment

```bash
# Copiar template de entorno
cp env .env

# Editar .env con tu configuracion
nano .env
```

Configuracion minima en `.env`:

```ini
# ── ENVIRONMENT ──
CI_ENVIRONMENT = development

# ── APP ──
app.baseURL = 'http://localhost:8080/'

# ── DATABASE ──
database.default.hostname = localhost
database.default.database = marachain
database.default.username = marachain_user
database.default.password = secure_password_here
database.default.DBDriver = MySQLi
database.default.port = 3306

# ── ENCRYPTION ──
# Generate with: php -r "echo bin2hex(random_bytes(16));"
encryption.key = your64characterhexkeyhere
```

**Generar encryption key:**

```bash
php -r "echo 'encryption.key = ' . bin2hex(random_bytes(16)) . PHP_EOL;"
```

### 6. Run Migrations

```bash
# Ejecutar todas las migraciones
php spark migrate

# Verificar estado
php spark migrate:status
```

**Salida esperada:**

```
+---------------------+---------------------------------------------+------------+
| Namespace           | Filename                                    | Status     |
+---------------------+---------------------------------------------+------------+
| App                 | 2026-07-13-100000_CreateUsersTable          | migrated   |
| App                 | 2026-07-13-100001_CreateDevicesTable        | migrated   |
| App                 | 2026-07-13-100002_CreateDocumentsTable      | migrated   |
| App                 | 2026-07-13-100003_CreateDocumentTransfers.. | migrated   |
| App                 | 2026-07-13-100004_CreateSignatureRequests.. | migrated   |
| App                 | 2026-07-13-100005_CreateEvidencesTable      | migrated   |
| App                 | 2026-07-13-100006_CreateLedgerBlocksTable   | migrated   |
| App                 | 2026-07-13-100007_CreateContactsTable       | migrated   |
| App                 | 2026-07-13-100008_CreateNotificationsTable  | migrated   |
+---------------------+---------------------------------------------+------------+
```

### 7. (Optional) Seed Database

```bash
# Poblar con datos de prueba usando Faker
php spark db:seed DatabaseSeeder
```

### 8. Verify Database Schema

```bash
# Conectar a MySQL y verificar tablas
mysql -u marachain_user -p marachain -e "SHOW TABLES;"
```

**Tablas esperadas:**

```
+-----------------------+
| Tables_in_marachain   |
+-----------------------+
| contacts              |
| devices               |
| document_transfers    |
| documents             |
| evidences             |
| ledger_blocks         |
| migrations            |
| notifications         |
| signature_requests    |
| users                 |
+-----------------------+
```

---

## Verification

### 9. Start Development Server

```bash
# Iniciar servidor de desarrollo CI4
php spark serve

# O con puerto personalizado
php spark serve --port=8080

# Acceder en navegador
# http://localhost:8080
```

### 10. Run Tests

```bash
# Todos los tests (164 tests, 390 assertions)
php vendor/bin/phpunit

# Solo tests unitarios
php vendor/bin/phpunit --testsuite unit

# Con cobertura de codigo
php vendor/bin/phpunit --coverage-text

# Con salida detallada
php vendor/bin/phpunit --verbose --testdox
```

**Salida esperada:**

```
PHPUnit 10.5.x by Sebastian Bergmann and contributors.

.............................................................   61 / 164
.............................................................  122 / 164
..........................................                    164 / 164

Time: 00:XX.XXX, Memory: XX.00 MB

OK (164 tests, 390 assertions)
```

### 11. Test API Endpoints

```bash
# Test endpoint de usuarios (necesita servidor corriendo)
curl -X GET http://localhost:8080/users

# Crear un usuario
curl -X POST http://localhost:8080/users \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","identityType":"physical","firstName":"Test"}'

# Verificar security headers
curl -I http://localhost:8080/ | grep -E "X-Content-Type|X-Frame|X-XSS|Referrer|Strict|Content-Security|Permissions"
```

---

## Troubleshooting

### Error: `Could not connect to database`

**Causa**: Credenciales incorrectas o MySQL no esta corriendo.

**Solucion**:

```bash
# Verificar que MySQL esta corriendo
sudo systemctl status mysql

# Probar conexion manual
mysql -u marachain_user -p -h localhost marachain -e "SELECT 1;"
```

### Error: `Class "App\Models\UserModel" not found`

**Causa**: Autoloader no actualizado.

**Solucion**:

```bash
composer dump-autoload
```

### Error: `SQLSTATE[HY000] [2002] Connection refused`

**Causa**: MySQL no esta escuchando en el puerto configurado.

**Solucion**:

```bash
# Verificar puerto MySQL
sudo netstat -tlnp | grep 3306

# Si usas Docker
docker ps | grep mysql
```

### Error: `encryption.key is not set`

**Causa**: Falta la clave de cifrado en `.env`.

**Solucion**:

```bash
# Generar y anadir al .env
echo "encryption.key = $(php -r 'echo bin2hex(random_bytes(16));')" >> .env
```

### Error: `The framework needs the following extension(s) installed and loaded`

**Causa**: Falta una extension PHP requerida.

**Solucion**:

```bash
# Ver extensiones que faltan
php -m

# Instalar extension que falta (ejemplo para intl)
sudo apt install php8.5-intl
sudo systemctl restart php8.5-fpm  # Si usas FPM
```

### Error: `PHP Parse error: syntax error, unexpected '?'`

**Causa**: Version de PHP inferior a 7.4 (nullable types).

**Solucion**:

```bash
php -v  # Debe mostrar >= 8.2
```

### Tests fallan con `no such table: db_users`

**Causa**: Migraciones no ejecutadas para el entorno de testing.

**Solucion**:

```bash
# Limpiar cache de tests
rm -rf writable/cache/*
php spark migrate --env testing
php vendor/bin/phpunit
```

---

## Production Deployment

### VPS Deployment (Apache)

Para despliegue en produccion con Apache en un VPS:

#### 1. Requisitos de servidor

```bash
# En el VPS
sudo apt update
sudo apt install -y apache2 php8.5 php8.5-fpm php8.5-mysql \
    php8.5-curl php8.5-mbstring php8.5-xml php8.5-intl \
    php8.5-sodium php8.5-zip composer
```

#### 2. Estructura de directorios

```
/var/www/prod/
├── app/
├── public/          ← DocumentRoot de Apache
├── writable/        ← Permisos de escritura para www-data
├── vendor/
├── .env             ← Configuracion de produccion
└── spark
```

#### 3. Permisos

```bash
sudo chown -R www-data:www-data /var/www/prod/
sudo chmod -R 755 /var/www/prod/
sudo chmod -R 775 /var/www/prod/writable/

# Proteger .env
sudo chmod 640 /var/www/prod/.env
```

#### 4. Apache VirtualHost

```apache
# /etc/apache2/sites-available/marachain.conf
<VirtualHost *:80>
    ServerName marachain.example.com
    Redirect permanent / https://marachain.example.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName marachain.example.com
    DocumentRoot /var/www/prod/public

    <Directory /var/www/prod/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Deny access to sensitive files
    <FilesMatch "\.(env|htaccess|git|json|lock|md|yml|yaml|xml|dist)$">
        Require all denied
    </FilesMatch>

    ErrorLog ${APACHE_LOG_DIR}/marachain_error.log
    CustomLog ${APACHE_LOG_DIR}/marachain_access.log combined

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/marachain.crt
    SSLCertificateKeyFile /etc/ssl/private/marachain.key
</VirtualHost>
```

#### 5. .htaccess (en `public/`)

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php/$1 [L]
```

#### 6. Variables de entorno en produccion

```ini
# .env (produccion)
CI_ENVIRONMENT = production
app.baseURL = 'https://marachain.example.com/'
app.forceGlobalSecureRequests = true
encryption.key = [clave segura unica de 64 caracteres hex]

database.default.hostname = localhost
database.default.database = marachain
database.default.username = [usuario_produccion]
database.default.password = [password_segura]
```

#### 7. Comandos post-deploy

```bash
cd /var/www/prod
composer install --no-dev --optimize-autoloader
php spark migrate
php spark cache:clear

# Verificar
curl -I https://marachain.example.com/ | grep -E "HTTP/|X-"
```

### Production Checklist

- [ ] `CI_ENVIRONMENT = production`
- [ ] `encryption.key` configurada y unica
- [ ] HTTPS activo (TLS 1.2+)
- [ ] `forcehttps` filter activo
- [ ] Security headers presentes en todas las respuestas
- [ ] `.env` con permisos `640` (solo root/www-data)
- [ ] `public/` es el DocumentRoot (no `wwwroot/`)
- [ ] `writable/` con permisos correctos para www-data
- [ ] `composer install --no-dev` ejecutado
- [ ] Migraciones ejecutadas sin errores
- [ ] Tests pasados en staging antes del deploy
- [ ] Backup de base de datos realizado
- [ ] Rollback plan documentado
- [ ] Logs funcionando (`writable/logs/`)
- [ ] `display_errors = Off` en php.ini

---

## Quick Reference

```bash
# ── Desarrollo ──
git clone git@github.com:your-org/marachain.git
cd marachain/wwwroot
composer install
cp env .env
nano .env                                    # Configurar MySQL
php spark migrate
php spark serve                              # http://localhost:8080
php vendor/bin/phpunit                       # 164 tests

# ── Comandos utiles ──
php spark list                               # Listar comandos
php spark migrate:status                     # Estado migraciones
php spark make:migration CreateTableName     # Nueva migracion
php spark make:controller NewController      # Nuevo controlador
php spark make:model NewModel                # Nuevo modelo
php spark make:entity NewEntity              # Nueva entidad
php spark cache:clear                        # Limpiar cache
php spark db:seed DatabaseSeeder             # Datos de prueba

# ── Dependencias ──
composer install                             # Instalar todo
composer update                              # Actualizar dependencias
composer audit                               # Auditoria de seguridad
composer dump-autoload                       # Regenerar autoloader

# ── Testing ──
php vendor/bin/phpunit                       # Todos los tests
php vendor/bin/phpunit --testsuite unit      # Unit tests
php vendor/bin/phpunit --filter UserModel    # Tests filtrados
php vendor/bin/phpunit --coverage-text       # Cobertura
```
