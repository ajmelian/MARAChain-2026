# Architecture

> **Version:** 1.2.0 | **Date:** 2026-07-14 | **Status:** Baseline aceptada

## Overview

MARAChain adopta un estilo **monolito modular** que combina:

- MVC en presentacion (CodeIgniter 4)
- Casos de uso en aplicacion (Controllers + Models)
- Dominio independiente (Entities)
- Arquitectura hexagonal en limites externos
- DDD tactico en modulos criticos (Identity, Encryption, Evidence, Ledger)

```
Presentation → Application → Domain
Infrastructure → Ports
Domain → sin dependencia de framework
```

## Design Decisions (ADR)

| ID | Decision | Justificacion |
|----|----------|---------------|
| ADR-001 | Monolito modular sobre microservicios | Complejidad operativa reducida para MVP; refactorizacion futura posible |
| ADR-002 | PHP 8.5 + CodeIgniter 4 | Stack conocido por el equipo; madurez del ecosistema |
| ADR-003 | UUID v4 como PK en todas las tablas | Evita colisiones en entornos distribuidos; sin dependencia de autoincrement |
| ADR-004 | NIF/NIE cifrado con AEAD + HMAC determinista | Busquedas sin descifrar; conformidad GDPR |
| ADR-005 | SQLite :memory: para tests | Velocidad; aislamiento; sin dependencia de infraestructura |
| ADR-006 | IPFS privado (no publico) | Confidencialidad de documentos; control de replicas |
| ADR-007 | Ledger interno append-only | Trazabilidad criptografica sin dependencia de blockchain externa |
| ADR-008 | SHIELD para autenticacion | Integracion nativa con CI4; soporte TOTP y sesiones |
| ADR-009 | Patron `$datamap` en Entities | Mapeo camelCase (PHP) ↔ snake_case (MySQL) transparente |
| ADR-010 | `SecurityHeaders` como filter global `after` | OWASP compliance sin acoplamiento al controlador |

## Component Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                       MARAChain (monolito)                       │
│                                                                   │
│  ┌─────────────┐  ┌─────────────┐  ┌──────────────────────────┐ │
│  │ Controllers  │  │   Filters   │  │        Entities          │ │
│  │              │  │              │  │                          │ │
│  │ UserController│  │ SecurityHdr │  │ User, Device, Document  │ │
│  │ DeviceCtrl   │  │ forcehttps  │  │ Transfer, SignatureReq  │ │
│  │ DocumentCtrl │  │ pagecache   │  │ Evidence, LedgerBlock   │ │
│  │ TransferCtrl │  │ performance │  │ Contact, Notification   │ │
│  │ SignatureCtrl│  │              │  │                          │ │
│  │ EvidenceCtrl │  └─────────────┘  └──────────┬───────────────┘ │
│  │ LedgerCtrl   │                               │                 │
│  │ ContactCtrl  │                               ▼                 │
│  │ NotifCtrl    │                     ┌──────────────────┐       │
│  │ Home         │                     │     Models        │       │
│  └──────┬───────┘                     │  (Query Builder)  │       │
│         │                             │  CI4 Model Layer  │       │
│         ▼                             └────────┬─────────┘       │
│  ┌──────────────┐                              │                 │
│  │  Validation  │                              ▼                 │
│  │  9 groups    │                     ┌──────────────────┐       │
│  │  CustomRules │                     │   Migrations     │       │
│  └──────────────┘                     │  (CI4 Forge)     │       │
│                                       └────────┬─────────┘       │
│                                                │                 │
│  ┌─────────────────────────────────────────────┼─────────────┐  │
│  │                Infrastructure                │             │  │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  │             │  │
│  │  │  MySQL   │  │  SQLite  │  │  IPFS    │  │             │  │
│  │  │ (prod)   │  │ (tests)  │  │(privado) │  │             │  │
│  │  └──────────┘  └──────────┘  └──────────┘  │             │  │
│  └─────────────────────────────────────────────┘             │  │
└─────────────────────────────────────────────────────────────────┘
```

## Data Flow

```
1. Cliente Web (WebCrypto)
   │  Cifrado extremo a extremo en navegador
   │  (documento NUNCA en claro en backend)
   ▼
2. Nginx → PHP-FPM → CodeIgniter 4
   │  SecurityHeaders filter (after)
   │  forcehttps (before)
   ▼
3. Controller
   │  Validacion (Config\Validation + CustomRules)
   │  camelToSnake() conversion
   ▼
4. Model (Query Builder)
   │  UUID v4 generacion
   │  Prepared statements (sin raw SQL)
   ▼
5. MySQL
   │  Tablas InnoDB con foreign keys
   │  charset utf8mb4
   ▼
6. IPFS (documentos cifrados)
   │  Solo el destinatario puede descifrar
   │  (clave envuelta en sobre criptografico)
   ▼
7. Ledger (evidencias append-only)
   │  Bloques con Merkle tree
   │  Firmas criptograficas por bloque
```

## Directory Tree (`wwwroot/`)

```
wwwroot/
├── app/
│   ├── Config/
│   │   ├── App.php                  # Configuracion general de la aplicacion
│   │   ├── Boot/
│   │   │   ├── development.php      # Entorno desarrollo (E_ALL, CI_DEBUG)
│   │   │   ├── production.php       # Entorno produccion (no errores)
│   │   │   └── testing.php          # Entorno testing
│   │   ├── Database.php             # Conexiones: default (MySQL), tests (SQLite)
│   │   ├── Filters.php              # SecurityHeaders global after
│   │   ├── Routes.php               # 24+ endpoints REST
│   │   ├── Validation.php           # 9 grupos de validacion
│   │   ├── Constants.php            # Constantes del sistema
│   │   ├── Encryption.php           # Configuracion de cifrado
│   │   ├── Session.php              # Sesiones (SHIELD futuro)
│   │   ├── Security.php             # Configuracion CSRF/Honeypot
│   │   └── ...                      # Otros ficheros CI4 estandar
│   ├── Controllers/
│   │   ├── BaseController.php       # camelToSnake(), validateGroup()
│   │   ├── Home.php                 # Ruta raiz
│   │   ├── UserController.php       # CRUD + enableTotp (6 endpoints)
│   │   ├── DeviceController.php     # index, show, register, revoke (4 endpoints)
│   │   ├── DocumentController.php   # CRUD + seal (5 endpoints)
│   │   ├── TransferController.php   # CRUD + inbox, outbox, revoke (6 endpoints)
│   │   ├── SignatureController.php  # request, show (2 endpoints)
│   │   ├── EvidenceController.php   # index, show (2 endpoints)
│   │   ├── LedgerController.php     # index, show, verify (3 endpoints)
│   │   ├── ContactController.php    # CRUD (5 endpoints)
│   │   └── NotificationController.php # index, show (2 endpoints)
│   ├── Database/
│   │   ├── Migrations/
│   │   │   ├── 2026-07-13-100000_CreateUsersTable.php
│   │   │   ├── 2026-07-13-100001_CreateDevicesTable.php
│   │   │   ├── 2026-07-13-100002_CreateDocumentsTable.php
│   │   │   ├── 2026-07-13-100003_CreateDocumentTransfersTable.php
│   │   │   ├── 2026-07-13-100004_CreateSignatureRequestsTable.php
│   │   │   ├── 2026-07-13-100005_CreateEvidencesTable.php
│   │   │   ├── 2026-07-13-100006_CreateLedgerBlocksTable.php
│   │   │   ├── 2026-07-13-100007_CreateContactsTable.php
│   │   │   └── 2026-07-13-100008_CreateNotificationsTable.php
│   │   └── Seeds/
│   │       └── DatabaseSeeder.php
│   ├── Entities/
│   │   ├── User.php                 # identityType, TOTP, taxIdEncrypted
│   │   ├── Device.php               # deviceType, publicKeyFingerprint
│   │   ├── Document.php             # title, mimeType, fileHashSha256
│   │   ├── DocumentTransfer.php     # securityLevel, idempotencyKey, ACL
│   │   ├── SignatureRequest.php     # signatureIntent, manifestHash, nonce
│   │   ├── Evidence.php             # eventId, payloadJson, aggregateType
│   │   ├── LedgerBlock.php          # blockNumber, merkleRoot, blockHash
│   │   ├── Contact.php              # contactType, emailPrimary, taxIdEncrypted
│   │   └── Notification.php         # recipientEmail, notificationType, status
│   ├── Filters/
│   │   └── SecurityHeaders.php      # 7 cabeceras OWASP
│   ├── Models/
│   │   ├── UserModel.php            # CRUD + TOTP management
│   │   ├── DeviceModel.php          # register, revoke, markLost
│   │   ├── DocumentModel.php        # CRUD + seal + version control
│   │   ├── DocumentTransferModel.php # create, revokeTransfer, inbox/outbox
│   │   ├── SignatureRequestModel.php # request, consume, validate
│   │   ├── EvidenceModel.php        # append-only, aggregate queries
│   │   ├── LedgerBlockModel.php     # createBlock, chain integrity
│   │   ├── ContactModel.php         # CRUD + search
│   │   └── NotificationModel.php    # outbox pattern, retry logic
│   └── Validation/
│       └── CustomRules.php          # valid_tax_id, valid_phone_e164, valid_uuid
├── tests/
│   ├── Unit/Models/                 # 9 model test files
│   │   ├── UserModelTest.php
│   │   ├── DeviceModelTest.php
│   │   ├── DocumentModelTest.php
│   │   ├── DocumentTransferModelTest.php
│   │   ├── SignatureRequestModelTest.php
│   │   ├── EvidenceModelTest.php
│   │   ├── LedgerBlockModelTest.php
│   │   ├── ContactModelTest.php
│   │   └── NotificationModelTest.php
│   ├── Unit/Controllers/            # 9 controller test files
│   │   ├── UserControllerTest.php
│   │   ├── DeviceControllerTest.php
│   │   ├── DocumentControllerTest.php
│   │   ├── TransferControllerTest.php
│   │   ├── SignatureControllerTest.php
│   │   ├── EvidenceControllerTest.php
│   │   ├── LedgerControllerTest.php
│   │   ├── ContactControllerTest.php
│   │   └── NotificationControllerTest.php
│   ├── unit/HealthTest.php
│   └── _support/
├── public/                          # Document root (index.php)
├── writable/                        # Logs, cache, sesiones
├── composer.json
├── phpunit.xml.dist
├── env                              # .env template
└── spark                            # CLI entry point
```

## Layer Descriptions

### Entities (`app/Entities/`)

Capa de dominio puro. Extienden `CodeIgniter\Entity\Entity`. Cada entidad define:

- **`$casts`**: tipos nativos PHP (`?string`, `bool`, `int`, `?datetime`)
- **`$datamap`**: mapeo `snake_case` (DB) ↔ `camelCase` (PHP)
- Metodos de dominio (ej: `User::isActive()`, `User::hasTotpEnabled()`)

Sin dependencia de HTTP, base de datos ni framework.

### Migrations (`app/Database/Migrations/`)

Definen el esquema de base de datos usando CI4 Forge:

- `CHAR(36)` para UUID v4 como PK
- `ENUM` para estados con valores semanticos
- `VARCHAR(64)` para hashes SHA-256 en hexadecimal
- `VARCHAR(254)` para emails (RFC 5321)
- `TINYINT(1)` para booleanos
- Foreign keys con `ON DELETE CASCADE` / `ON DELETE RESTRICT`

### Models (`app/Models/`)

Capa de persistencia. Extienden `CodeIgniter\Model`:

- `$returnType = Entity::class` — devuelven entidades tipadas
- `$useAutoIncrement = false` — PKs son UUID v4 generados en PHP
- `$useTimestamps = true` — `created_at` / `updated_at` automaticos
- Metodos de negocio: `create()`, `findByEmail()`, `enableTotp()`, etc.
- Usan **Query Builder** de CI4, nunca raw SQL con concatenacion

### Controllers (`app/Controllers/`)

Capa de presentacion REST. Extienden `BaseController`:

- Usan `ResponseTrait` para JSON consistente
- `$this->respond()`, `$this->respondCreated()`, `$this->failNotFound()`
- Validan entrada con `Config\Validation` mediante `validateGroup()`
- Convierten `camelCase` → `snake_case` via `BaseController::camelToSnake()`
- Cada controlador tiene su propio model inyectado via `model()` helper

### Config (`app/Config/`)

- **Routes.php**: mapeo de URLs a controladores (24+ endpoints)
- **Validation.php**: 9 grupos de reglas de validacion
- **Filters.php**: SecurityHeaders como filtro global `after`
- **Database.php**: conexion `default` (MySQL) + `tests` (SQLite :memory:)
- **Boot/**: configuracion por entorno (development/testing/production)

### Validation (`app/Validation/`)

- **CustomRules.php**: `valid_tax_id` (NIF/NIE/CIF), `valid_phone_e164`, `valid_hex`, `valid_uuid`
- Integrado como `$ruleSets` en `Config\Validation`

### Filters (`app/Filters/`)

- **SecurityHeaders.php**: aplica 7 cabeceras OWASP en cada respuesta HTTP
- Registrado como alias `security` y aplicado globalmente en `after`

## API Design

### REST Endpoints (desde `Routes.php`)

| Metodo | Ruta | Controlador | Descripcion |
|--------|------|-------------|-------------|
| GET | `/` | `Home::index` | Welcome page |
| **Users** | | | |
| GET | `/users` | `UserController::index` | Listar usuarios |
| GET | `/users/{id}` | `UserController::show` | Ver usuario |
| POST | `/users` | `UserController::create` | Crear usuario |
| PUT | `/users/{id}` | `UserController::update` | Actualizar usuario |
| DELETE | `/users/{id}` | `UserController::delete` | Bloquear usuario |
| POST | `/users/{id}/totp` | `UserController::enableTotp` | Activar TOTP |
| **Devices** | | | |
| GET | `/devices` | `DeviceController::index` | Listar dispositivos |
| GET | `/devices/{id}` | `DeviceController::show` | Ver dispositivo |
| POST | `/devices` | `DeviceController::register` | Registrar dispositivo |
| DELETE | `/devices/{id}` | `DeviceController::revoke` | Revocar dispositivo |
| **Documents** | | | |
| GET | `/documents` | `DocumentController::index` | Listar documentos |
| GET | `/documents/{id}` | `DocumentController::show` | Ver documento |
| POST | `/documents` | `DocumentController::create` | Crear documento |
| POST | `/documents/{id}/seal` | `DocumentController::seal` | Sellar documento |
| DELETE | `/documents/{id}` | `DocumentController::delete` | Eliminar documento |
| **Transfers** | | | |
| GET | `/transfers` | `TransferController::index` | Listar transferencias |
| GET | `/transfers/sent` | `TransferController::outbox` | Bandeja de salida |
| GET | `/transfers/received` | `TransferController::inbox` | Bandeja de entrada |
| GET | `/transfers/{id}` | `TransferController::show` | Ver transferencia |
| POST | `/transfers` | `TransferController::create` | Crear transferencia |
| POST | `/transfers/{id}/revoke` | `TransferController::revoke` | Revocar transferencia |
| **Signatures** | | | |
| POST | `/signatures` | `SignatureController::request` | Solicitar firma |
| GET | `/signatures/{id}` | `SignatureController::show` | Ver solicitud de firma |
| **Evidence** | | | |
| GET | `/evidence` | `EvidenceController::index` | Listar evidencias |
| GET | `/evidence/{id}` | `EvidenceController::show` | Ver evidencia |
| **Ledger** | | | |
| GET | `/ledger` | `LedgerController::index` | Listar bloques |
| GET | `/ledger/verify` | `LedgerController::verify` | Verificar integridad |
| GET | `/ledger/{id}` | `LedgerController::show` | Ver bloque |
| **Contacts** | | | |
| GET | `/contacts` | `ContactController::index` | Listar contactos |
| POST | `/contacts` | `ContactController::create` | Crear contacto |
| GET | `/contacts/{id}` | `ContactController::show` | Ver contacto |
| PUT | `/contacts/{id}` | `ContactController::update` | Actualizar contacto |
| DELETE | `/contacts/{id}` | `ContactController::delete` | Eliminar contacto |
| **Notifications** | | | |
| GET | `/notifications` | `NotificationController::index` | Listar notificaciones |
| GET | `/notifications/{id}` | `NotificationController::show` | Ver notificacion |

**Total: 37 rutas registradas (35 endpoints REST + 1 home + 1 ledger/verify)**

## Database

### Tablas

| # | Tabla | Entidad | Migracion |
|---|-------|---------|-----------|
| 1 | `users` | `User` | `2026-07-13-100000` |
| 2 | `devices` | `Device` | `2026-07-13-100001` |
| 3 | `documents` | `Document` | `2026-07-13-100002` |
| 4 | `document_transfers` | `DocumentTransfer` | `2026-07-13-100003` |
| 5 | `signature_requests` | `SignatureRequest` | `2026-07-13-100004` |
| 6 | `evidences` | `Evidence` | `2026-07-13-100005` |
| 7 | `ledger_blocks` | `LedgerBlock` | `2026-07-13-100006` |
| 8 | `contacts` | `Contact` | `2026-07-13-100007` |
| 9 | `notifications` | `Notification` | `2026-07-13-100008` |

### Caracteristicas del esquema

- **PK**: `CHAR(36)` UUID v4 en todas las tablas
- **Timestamps**: `created_at`, `updated_at` en todas las tablas
- **Foreign keys**: con restricciones `ON DELETE CASCADE` / `ON DELETE RESTRICT`
- **Charset**: `utf8mb4` con collation `utf8mb4_general_ci`
- **Engine**: `InnoDB` (soporte transaccional)
- **Unique keys**: `email` (users), `tax_id_hmac` (users), `idempotency_key` (transfers)
- **Indexes**: en columnas de busqueda frecuente (`status`, `identity_type`, `event_type`, `aggregate_id`)

## Security Architecture

- **SHIELD** (planificado): autenticacion, autorizacion, sesiones
- **SecurityHeaders**: filtro global `after` que aplica 7 cabeceras OWASP
- **forcehttps**: filtro global `before` (redireccion HTTP → HTTPS)
- **Cifrado AEAD**: NIF/NIE cifrado en reposo; busqueda via HMAC determinista
- **WebCrypto**: cifrado extremo a extremo de documentos en navegador
- **TOTP**: segundo factor con bloqueo tras 5 fallos (30 min)
- **UUID v4**: evita enumeracion de IDs
- **Query Builder**: previene SQL injection (sin raw SQL)
- **Sin clave maestra**: modelo _only-4-your-eyes_

## Testing

### Configuracion

- **Framework**: PHPUnit 10.x
- **Base de datos**: SQLite `:memory:` (grupo `tests` activado con `CI_ENVIRONMENT=testing`)
- **Bootstrap**: `vendor/codeigniter4/framework/system/Test/bootstrap.php`
- **Cobertura**: `clover.xml` + `html` en `build/logs/`

### Suite actual

- **164 tests** en 18 ficheros de test
- **390 assertions**
- **9 model test files** en `tests/Unit/Models/`
- **9 controller test files** en `tests/Unit/Controllers/`
- **2 health tests** en `tests/unit/`
- **Database tests**: `tests/database/ExampleDatabaseTest.php`

### Ejecucion

```bash
php vendor/bin/phpunit                    # Todos los tests
php vendor/bin/phpunit --testsuite unit   # Solo unit tests
php vendor/bin/phpunit --coverage-text    # Con cobertura
```

## Deployment Architecture

```
┌───────────────────────────────────────────────┐
│              VPS (Ubuntu / Debian)            │
│                                               │
│  ┌─────────┐    ┌─────────────┐              │
│  │  Nginx  │───▶│  PHP-FPM    │              │
│  │  :443   │    │  Unix sock  │              │
│  └─────────┘    └──────┬──────┘              │
│                        │                      │
│  ┌─────────────────────┼──────────────────┐  │
│  │  /var/www/prod/     │                  │  │
│  │  ├── app/           │                  │  │
│  │  ├── public/ (root) │                  │  │
│  │  ├── writable/      │                  │  │
│  │  ├── vendor/        │                  │  │
│  │  └── .env           │                  │  │
│  └─────────────────────┼──────────────────┘  │
│                        │                      │
│  ┌─────────────────────┼──────────────────┐  │
│  │  MySQL 8.x          │                  │  │
│  │  (localhost:3306)   │                  │  │
│  └─────────────────────┘                  │  │
│                                               │
│  ┌─────────────────────────────────────────┐  │
│  │  IPFS (cluster privado)                 │  │
│  └─────────────────────────────────────────┘  │
└───────────────────────────────────────────────┘
```

- **Deploy**: SFTP rsync desde CI/CD (GitLab CI / GitHub Actions)
- **Staging**: `/var/www/staging/` con datos anonimizados
- **Produccion**: `/var/www/prod/` con backup de BD antes de migrar
- **Rollback**: `git checkout` a tag anterior + restore BD
