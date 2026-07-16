# Architecture

> **Version:** 1.7.0 | **Date:** 2026-07-16 | **Status:** MVP (Pre-alpha)

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
| ADR-008 | SHIELD para autenticacion | Integracion nativa con CI4; soporte TOTP, sesiones, y grupos de permisos |
| ADR-009 | Patron `$datamap` en Entities | Mapeo camelCase (PHP) ↔ snake_case (MySQL) transparente |
| ADR-010 | `SecurityHeaders` como filter global `after` | OWASP compliance sin acoplamiento al controlador |
| ADR-011 | Capa de Servicios con interfaces (Ports & Adapters) | Abstraccion de proveedores externos (FNMT, firma, timestamping, anclaje DLT); permite testing con mocks y futura sustitucion de proveedores |
| ADR-012 | `Throttle` filter basado en token bucket | Rate limiting configurable por grupo de ruta; protege endpoints de auth y API sin dependencia externa (Redis/memcached) |
| ADR-013 | AES-256-GCM para secretos TOTP | Cifrado reversible con autenticacion (AEAD); reemplaza HMAC unidireccional que impedia verificacion recurrente |
| ADR-014 | Controladores Web separados de API REST | Separacion de responsabilidades: API devuelve JSON, Web devuelve HTML con vistas; comparten modelos de persistencia |
| ADR-015 | `StorageService` con envelope `marachain-envelope v1` | Formato estandarizado de ciphertext: `{version, algorithm, iv, ciphertext, tag}`. Permite validacion de integridad AEAD antes de almacenar. Desacopla cifrado (cliente) de almacenamiento (servidor) |
| ADR-016 | `shield_user_id` como FK en tabla `users` | SHIELD gestiona autenticacion (INT PK `shield_users.id`); MARAChain gestiona identidad y negocio (UUID PK `users.id`). Linkage via FK con UNIQUE constraint. `BaseWebController::getAuthenticatedUserId()` resuelve el mapeo en cada peticion autenticada |
| ADR-017 | `EvidenceService` como servicio de dominio | Registro de eventos de negocio (`DocumentSent`, `TransferAccepted`, etc.) centralizado. Cada evento incluye `aggregateType`, `aggregateId`, `eventType` y `payloadJson`. Append-only con verificacion de integridad via LedgerService |
| ADR-018 | `Helpers/Uuid.php` — DRY UUID generation | Reemplaza `generateUuidV4()` duplicada en 10 archivos por una funcion helper centralizada `generate_uuid_v4()`. Cargada via `BaseController::$helpers = ['uuid']` |
| ADR-019 | Sistema de notificaciones multi-canal con Provider Pattern | Notificaciones desacopladas por canal (Email, WhatsApp, Telegram, SMS) mediante `NotificationProviderInterface`. Cada canal es un provider independiente con contrato `send()`/`health()`. Outbox transaccional (`notification_requested`) con idempotencia, reintentos con backoff, circuit breaker, y dead-letter. Cuentas globales corporativas (`global_messaging_accounts`) gestionadas por canal y entorno. Los secretos de proveedores residen fuera de `wwwroot/` (`/var/lib/marachain/integrations/`). Stubs para canales futuros permiten desarrollo incremental sin bloquear el nucleo |
| ADR-020 | Migracion `800000_AddIpfsAndBlockchainIds` — columnas preparatorias para IPFS y DLT | Anade `ipfs_cid` (VARCHAR 128) y `blockchain_anchor_id` (VARCHAR 256) como columnas nullable. Permite desarrollo futuro de almacenamiento IPFS y anclaje blockchain sin nueva migracion de schema. Las columnas son backward-compatible (NULL por defecto) |

## Component Diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         MARAChain (monolito modular)                     │
│                                                                          │
│  ┌─────────────────┐  ┌──────────────┐  ┌──────────────────────────┐   │
│  │ Controllers REST │  │ Controllers  │  │      Commands (CLI)       │   │
│  │                  │  │    Web       │  │                           │   │
│  │ UserController   │  │ AuthCtrl     │  │ ledger:genesis            │   │
│  │ DeviceCtrl       │  │ FnmtCtrl     │  │ ledger:seal               │   │
│  │ DocumentCtrl     │  │ TransfersCtrl│  │ notifications:send        │   │
│  │ DocumentUploadCtrl│ │ ContactsCtrl │  │                           │   │
│  │ TransferCtrl     │  │ ProfileCtrl  │  └───────────┬───────────────┘   │
│  │ SignatureCtrl    │  │ BaseWebCtrl  │              │                   │
│  │ EvidenceCtrl     │  │              │              ▼                   │
│  │ LedgerCtrl       │  └──────┬───────┘    ┌──────────────────┐         │
│  │ ContactCtrl      │         │            │    Models         │         │
│  │ NotifCtrl        │         │            │ (Query Builder)   │         │
│  │ TimestampCtrl    │         │            └────────┬─────────┘         │
│  │ HealthCtrl       │         │            └────────┬─────────┘         │
│  └────────┬─────────┘         │                     │                   │
│           │                   │                     ▼                   │
│           ▼                   │            ┌──────────────────┐         │
│  ┌────────────────┐           │            │   Migrations     │         │
│  │   Validation   │           │            │  (CI4 Forge)     │         │
│  │   9 groups +   │           │            │  17 migrations   │         │
│  │   CustomRules  │           │            └────────┬─────────┘         │
│  └────────────────┘           │                     │                   │
│                               │                     │                   │
│           ┌───────────────────┼─────────────────────┼───────────┐      │
│           │           Services Layer                │           │      │
│           │                                        │           │      │
│           │  IdentityProviderInterface ─── FnmtIdentityProvider │      │
│           │  SignatureProviderInterface              │           │      │
│           │  EncryptionService                       ▼           │      │
│           │  LedgerService              ┌──────────────────┐    │      │
│           │  X509Service                │    Entities      │    │      │
│           │  StorageService             │  9 entities     │    │      │
│           │  EvidenceService            │  (CI4 Entity)   │    │      │
│           │  TimestampService            └──────────────────┘    │      │
│           │  TimestampProviderInterface                          │      │
│           │  LedgerAnchorInterface                               │      │
│           │                                                     │      │
│           │  ┌─ Notification Layer ──────────┐                   │      │
│           │  │ NotificationProviderInterface │                   │      │
│           │  │ ├── EmailProvider (SMTP)      │                   │      │
│           │  │ ├── WhatsAppProvider (*)      │                   │      │
│           │  │ ├── TelegramProvider (*)      │                   │      │
│           │  │ └── SmsProvider (*)           │                   │      │
│           │  │ (*) stubs futuros             │                   │      │
│           │  └───────────────────────────────┘                   │      │
│           └─────────────────────────────┴──────────────────┘    │      │
│                                                                 │      │
│  ┌──────────────────────────────────────────────────────────────┼───┐  │
│  │                Infrastructure                                │   │  │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │   │  │
│  │  │  MySQL   │  │  SQLite  │  │  IPFS    │  │ Session  │   │   │  │
│  │  │ (prod)   │  │ (tests)  │  │(privado) │  │(File/SHLD)│   │   │  │
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────┘   │   │  │
│  └──────────────────────────────────────────────────────────────┘   │  │
└──────────────────────────────────────────────────────────────────────┘
```

## Data Flow

```
1. Cliente Web (WebCrypto + Dropzone)
   │  Cifrado extremo a extremo en navegador:
   │  - MARACrypto.encryptDocument() genera DEK aleatoria
   │  - Cifra documento con AES-256-GCM via WebCrypto
   │  - Construye envelope {version, algorithm, iv, ciphertext, tag}
   │  - DEK se envuelve para el destinatario (sobre criptografico)
   │  (documento NUNCA en claro en backend)
   ▼
2. Nginx → PHP-FPM → CodeIgniter 4
   │  SecurityHeaders filter (after)
   │  forcehttps (before)
   │  mTLS opcional (ssl_verify_client optional)
   │  /auth/fnmt → mTLS obligatorio
   ▼
3. Controller
   │  Validacion (Config\Validation + CustomRules)
   │  StorageService: validacion de envelope marachain-envelope v1
   │  EvidenceService: registro automatico de eventos de negocio
   │  BaseWebController::getAuthenticatedUserId(): SHIELD→MARAChain linkage
   │  camelToSnake() conversion
   ▼
4. Model (Query Builder)
   │  UUID v4 generacion (Helpers/Uuid.php)
   │  Prepared statements (sin raw SQL)
   │  shield_user_id linkage via FK
   ▼
5. MySQL
   │  Tablas InnoDB con foreign keys
   │  charset utf8mb4
   │  Ciphertext almacenado en columna documents.ciphertext
   ▼
6. IPFS (documentos cifrados) — preparado via ipfs_cid column
   │  Solo el destinatario puede descifrar
   │  (clave envuelta en sobre criptografico)
   ▼
7. Ledger (evidencias append-only)
   │  Bloques con Merkle tree
   │  Firmas criptograficas por bloque
   │  Evidencias registradas via EvidenceService → LedgerService
   ▼
8. Notifications (multi-canal, outbox transaccional)
   │  notification_requested → CLI worker
   │  Provider pattern: Email (SMTP), WhatsApp, Telegram, SMS
   │  Cuentas globales corporativas (global_messaging_accounts)
   │  Secretos en /var/lib/marachain/integrations/
```

## Directory Tree (`wwwroot/`)

```
wwwroot/
├── app/
│   ├── Commands/
│   │   ├── LedgerGenesis.php          # ledger:genesis — crear bloque genesis
│   │   ├── LedgerSeal.php             # ledger:seal — sellar evidencias en bloque
│   │   ├── NotificationsCommand.php   # notifications:send — procesar notificaciones multi-canal
│   │   └── NotificationSend.php       # [legacy] notification:send — reemplazado por NotificationsCommand
│   ├── Config/
│   │   ├── App.php                    # Configuracion general de la aplicacion
│   │   ├── Auth.php                   # Configuracion de autenticacion SHIELD
│   │   ├── AuthGroups.php             # Grupos y permisos SHIELD
│   │   ├── AuthToken.php              # Configuracion de tokens SHIELD
│   │   ├── Boot/
│   │   │   ├── development.php        # Entorno desarrollo (E_ALL, CI_DEBUG)
│   │   │   ├── production.php         # Entorno produccion (no errores)
│   │   │   └── testing.php            # Entorno testing
│   │   ├── Database.php               # Conexiones: default (MySQL), tests (SQLite)
│   │   ├── Filters.php                # SecurityHeaders global after, csrf, throttle
│   │   ├── Routes.php                 # 70+ rutas (REST + Web + Health)
│   │   ├── Settings.php               # Configuracion SHIELD Settings
│   │   ├── Validation.php             # 9 grupos de validacion
│   │   ├── Constants.php              # Constantes del sistema
│   │   ├── Encryption.php             # Configuracion de cifrado
│   │   ├── Session.php                # Sesiones (SHIELD)
│   │   ├── Security.php               # Configuracion CSRF/Honeypot
│   │   └── ...                        # Otros ficheros CI4 estandar
│   ├── Controllers/
│   │   ├── BaseController.php         # camelToSnake(), validateGroup()
│   │   ├── HealthController.php       # GET /health — health check endpoint
│   │   ├── Home.php                   # Ruta raiz
│   │   ├── UserController.php         # CRUD + enableTotp (6 endpoints)
│   │   ├── DeviceController.php       # index, show, register, revoke (4 endpoints)
│   │   ├── DocumentController.php     # CRUD + seal (5 endpoints)
│   │   ├── DocumentUploadController.php# POST /documents/upload — envelope + ciphertext
│   │   ├── TransferController.php     # CRUD + inbox, outbox, accept, reject, revoke (8 endpoints)
│   │   ├── SignatureController.php    # request, show (2 endpoints)
│   │   ├── EvidenceController.php     # index, show (2 endpoints)
│   │   ├── LedgerController.php       # index, show, verify (3 endpoints)
│   │   ├── ContactController.php      # CRUD (5 endpoints)
│   │   ├── NotificationController.php # index, show (2 endpoints)
│   │   ├── TimestampController.php    # request, show (2 endpoints)
│   │   └── Web/
│   │       ├── AuthController.php     # login, register, logout (SHIELD)
│   │       ├── BaseWebController.php  # render(), shared layout helpers
│   │       ├── ContactsController.php # CRUD de contactos (HTML)
│   │       ├── FnmtController.php     # mTLS + TOTP (FNMT certificate auth)
│   │       ├── ProfileController.php  # Perfil de usuario (HTML)
│   │       └── TransfersController.php# inbox, outbox, create (HTML)
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
│   │   │   ├── 2026-07-13-100008_CreateNotificationsTable.php
│   │   │   ├── 2026-07-13-200000_create_auth_tables.php
│   │   │   ├── 2026-07-14-300000_create_shield_tables.php
│   │   │   ├── 2026-07-14-400000_add_shield_user_id_to_users.php
│   │   │   ├── 2026-07-14-500000_CreateNotificationRequestedTable.php
│   │   │   ├── 2026-07-14-600000_CreateGlobalMessagingAccountsTable.php
│   │   │   ├── 2026-07-14-700000_CreateSettingsTable.php
│   │   │   ├── 2026-07-14-700001_AddContextColumn.php
│   │   │   └── 2026-07-14-800000_AddIpfsAndBlockchainIds.php
│   │   └── Seeds/
│   │       └── DatabaseSeeder.php
│   ├── Entities/
│   │   ├── User.php                   # identityType, TOTP, taxIdEncrypted
│   │   ├── Device.php                 # deviceType, publicKeyFingerprint
│   │   ├── Document.php               # title, mimeType, fileHashSha256
│   │   ├── DocumentTransfer.php       # securityLevel, idempotencyKey, ACL
│   │   ├── SignatureRequest.php       # signatureIntent, manifestHash, nonce
│   │   ├── Evidence.php               # eventId, payloadJson, aggregateType
│   │   ├── LedgerBlock.php            # blockNumber, merkleRoot, blockHash
│   │   ├── Contact.php                # contactType, emailPrimary, taxIdEncrypted
│   │   └── Notification.php           # recipientEmail, notificationType, status
│   ├── Filters/
│   │   ├── SecurityHeaders.php        # 7 cabeceras OWASP
│   │   └── Throttle.php               # Token bucket rate limiter
│   ├── Helpers/
│   │   └── Uuid.php                   # generate_uuid_v4() — DRY UUID generation
│   ├── Language/
│   │   └── en/
│   │       └── Validation.php         # Mensajes de error en ingles
│   ├── Models/
│   │   ├── UserModel.php              # CRUD + TOTP management + atomic ops
│   │   ├── DeviceModel.php            # register, revoke, markLost
│   │   ├── DocumentModel.php          # CRUD + seal + version control
│   │   ├── DocumentTransferModel.php  # create, revokeTransfer (state machine), inbox/outbox
│   │   ├── SignatureRequestModel.php  # request, consume, validate
│   │   ├── EvidenceModel.php          # append-only, aggregate queries
│   │   ├── LedgerBlockModel.php       # createBlock, chain integrity
│   │   ├── ContactModel.php           # CRUD + search
│   │   ├── NotificationModel.php      # outbox pattern, retry logic (atomic)
│   │   └── NotificationRequestedModel.php # transactional outbox, states, circuit breaker
│   ├── Notifications/
│   │   ├── NotificationChannel.php             # Enum PHP: EMAIL, WHATSAPP, TELEGRAM, SMS
│   │   ├── NotificationProviderInterface.php   # Contrato send()/health()
│   │   ├── NotificationMessage.php             # Value object contenido del mensaje
│   │   ├── NotificationResult.php              # Value object resultado del envio
│   │   ├── RecipientAddress.php                # Value object direccion del destinatario
│   │   └── Providers/
│   │       ├── EmailNotificationProvider.php       # Implementacion real SMTP
│   │       ├── WhatsAppNotificationProvider.php    # Stub para cuenta global WhatsApp
│   │       ├── TelegramNotificationProvider.php    # Stub para integracion Telegram
│   │       └── SmsNotificationProvider.php         # Stub para integracion SMS
│   ├── Services/
│   │   ├── EncryptionService.php       # AES-256-GCM encrypt/decrypt
│   │   ├── EvidenceService.php         # Automatic business event recording
│   │   ├── FnmtIdentityProvider.php    # FNMT certificate identity resolution
│   │   ├── IdentityProviderInterface.php # Identity provider abstraction
│   │   ├── LedgerAnchorInterface.php   # External blockchain anchoring abstraction
│   │   ├── LedgerService.php           # Block creation, Merkle tree, chain verification
│   │   ├── SignatureProviderInterface.php # Signature provider abstraction
│   │   ├── StorageService.php          # Ciphertext storage with envelope validation
│   │   ├── TimestampProviderInterface.php # Trusted timestamping abstraction
│   │   ├── TimestampService.php        # Timestamp service implementation
│   │   └── X509Service.php             # X.509 certificate parsing
│   └── Validation/
│       └── CustomRules.php             # valid_tax_id, valid_phone_e164, valid_uuid, valid_hex
├── tests/
│   ├── Unit/
│   │   ├── Controllers/                # 15 controller test files
│   │   ├── Models/                     # 9 model test files
│   │   └── Services/                   # 6 service test files
│   ├── unit/
│   │   └── HealthTest.php
│   ├── database/
│   ├── session/
│   └── _support/
├── public/                             # Document root (index.php)
├── writable/                           # Logs, cache, sesiones
├── composer.json
├── phpunit.xml.dist
├── env                                 # .env template
└── spark                               # CLI entry point
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
- `VARCHAR(128)` para `ipfs_cid` y `VARCHAR(256)` para `blockchain_anchor_id` (preparatorio)

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

### Services (`app/Services/`)

Capa de abstraccion de proveedores externos (patron Ports & Adapters):

- **IdentityProviderInterface** — contrato de verificacion de identidad
  - `FnmtIdentityProvider` — implementacion con certificados FNMT via mTLS
- **SignatureProviderInterface** — abstraccion de proveedor de firma electronica
- **TimestampProviderInterface** — abstraccion de sellado de tiempo confiable
  - `TimestampService` — implementacion del servicio de timestamping
- **LedgerAnchorInterface** — abstraccion de anclaje en DLT externa
- **EncryptionService** — cifrado/descifrado AES-256-GCM (AEAD) con claves de 32 bytes
- **StorageService** — almacenamiento de ciphertext en BD con validacion de envelope `marachain-envelope v1`
- **EvidenceService** — registro automatico de eventos de negocio
- **LedgerService** — creacion de bloques, arbol Merkle, verificacion de integridad de cadena
- **X509Service** — parseo de certificados X.509, extraccion de DN, resolucion de identidad

Los servicios no dependen de HTTP ni de CI4 Controllers. Reciben dependencias por constructor. Testeables con mocks.

### Commands (`app/Commands/`)

Comandos CLI accesibles via `php spark`:

| Comando | Clase | Funcion |
|---------|-------|---------|
| `ledger:genesis` | `LedgerGenesis` | Crea el bloque genesis (#1) del ledger |
| `ledger:seal` | `LedgerSeal` | Sella evidencias pendientes en un nuevo bloque |
| `notifications:send` | `NotificationsCommand` | Procesa notificaciones multi-canal desde el outbox transaccional |

### Web Controllers (`app/Controllers/Web/`)

Controladores para la interfaz web HTML (Bootstrap 5 + Alpino Admin Dashboard):

- **AuthController** — login, register, logout via SHIELD
- **BaseWebController** — renderizado compartido, helpers de layout
- **ContactsController** — CRUD de contactos con vistas HTML
- **FnmtController** — autenticacion con certificado FNMT (mTLS) + TOTP
- **ProfileController** — pagina de perfil de usuario
- **TransfersController** — bandeja de entrada, salida, creacion de transferencias

## API Design

### REST Endpoints (desde `Routes.php`)

> **Nota:** Todas las rutas API REST estan protegidas con el filtro `api-auth` (requiere sesion SHIELD activa).

| Metodo | Ruta | Controlador | Descripcion |
|--------|------|-------------|-------------|
| GET | `/` | `Home::index` | Welcome page |
| GET | `/health` | `HealthController::index` | Health check (publico) |
| **Auth (Web — rate-limited via throttle:auth)** | | | |
| GET | `/login` | `Web\AuthController::login` | Login form |
| POST | `/login` | `Web\AuthController::login` | Process login |
| GET | `/register` | `Web\AuthController::register` | Register form |
| POST | `/register` | `Web\AuthController::register` | Process registration |
| GET/POST | `/logout` | `Web\AuthController::logout` | Logout |
| **FNMT Auth (Web)** | | | |
| GET | `/auth/fnmt` | `Web\FnmtController::login` | Login con certificado FNMT (mTLS) |
| GET/POST | `/auth/fnmt/totp-setup` | `Web\FnmtController::totpSetup` | Configurar TOTP |
| GET/POST | `/auth/fnmt/totp-verify` | `Web\FnmtController::totpVerify` | Verificar TOTP |
| **Users (api-auth)** | | | |
| GET | `/users` | `UserController::index` | Listar usuarios |
| GET | `/users/{id}` | `UserController::show` | Ver usuario |
| POST | `/users` | `UserController::create` | Crear usuario |
| PUT | `/users/{id}` | `UserController::update` | Actualizar usuario |
| DELETE | `/users/{id}` | `UserController::delete` | Bloquear usuario |
| POST | `/users/{id}/totp` | `UserController::enableTotp` | Activar TOTP |
| **Devices (api-auth)** | | | |
| GET | `/devices` | `DeviceController::index` | Listar dispositivos |
| GET | `/devices/{id}` | `DeviceController::show` | Ver dispositivo |
| POST | `/devices` | `DeviceController::register` | Registrar dispositivo |
| DELETE | `/devices/{id}` | `DeviceController::revoke` | Revocar dispositivo |
| **Documents (api-auth)** | | | |
| GET | `/documents` | `DocumentController::index` | Listar documentos |
| GET | `/documents/{id}` | `DocumentController::show` | Ver documento |
| POST | `/documents` | `DocumentController::create` | Crear documento |
| POST | `/documents/upload` | `DocumentUploadController::upload` | Subir documento cifrado (envelope) |
| POST | `/documents/{id}/seal` | `DocumentController::seal` | Sellar documento |
| DELETE | `/documents/{id}` | `DocumentController::delete` | Eliminar documento |
| **Transfers (api-auth)** | | | |
| GET | `/transfers` | `TransferController::index` | Listar transferencias |
| GET | `/transfers/sent` | `TransferController::outbox` | Bandeja de salida |
| GET | `/transfers/received` | `TransferController::inbox` | Bandeja de entrada |
| GET | `/transfers/{id}` | `TransferController::show` | Ver transferencia |
| POST | `/transfers` | `TransferController::create` | Crear transferencia |
| POST | `/transfers/{id}/accept` | `TransferController::accept` | Aceptar transferencia |
| POST | `/transfers/{id}/reject` | `TransferController::reject` | Rechazar transferencia |
| POST | `/transfers/{id}/revoke` | `TransferController::revoke` | Revocar transferencia |
| **Signatures (api-auth)** | | | |
| POST | `/signatures` | `SignatureController::request` | Solicitar firma |
| GET | `/signatures/{id}` | `SignatureController::show` | Ver solicitud de firma |
| **Evidence (api-auth)** | | | |
| GET | `/evidence` | `EvidenceController::index` | Listar evidencias |
| GET | `/evidence/{id}` | `EvidenceController::show` | Ver evidencia |
| **Ledger (api-auth)** | | | |
| GET | `/ledger` | `LedgerController::index` | Listar bloques |
| GET | `/ledger/verify` | `LedgerController::verify` | Verificar integridad |
| GET | `/ledger/{id}` | `LedgerController::show` | Ver bloque |
| **Contacts (api-auth)** | | | |
| GET | `/contacts` | `ContactController::index` | Listar contactos |
| POST | `/contacts` | `ContactController::create` | Crear contacto |
| GET | `/contacts/{id}` | `ContactController::show` | Ver contacto |
| PUT | `/contacts/{id}` | `ContactController::update` | Actualizar contacto |
| DELETE | `/contacts/{id}` | `ContactController::delete` | Eliminar contacto |
| **Notifications (api-auth)** | | | |
| GET | `/notifications` | `NotificationController::index` | Listar notificaciones |
| GET | `/notifications/{id}` | `NotificationController::show` | Ver notificacion |
| **Timestamp (api-auth)** | | | |
| POST | `/timestamps` | `TimestampController::request` | Solicitar sello de tiempo |
| GET | `/timestamps/{id}` | `TimestampController::show` | Ver sello de tiempo |
| **Transfers (Web — session-protected)** | | | |
| GET | `/inbox` | `Web\TransfersController::inbox` | Bandeja de entrada |
| GET | `/outbox` | `Web\TransfersController::outbox` | Bandeja de salida |
| GET | `/transfers/new` | `Web\TransfersController::new` | Nueva transferencia |
| POST | `/transfers/{id}/accept` | `Web\TransfersController::accept` | Aceptar transferencia |
| POST | `/transfers/{id}/reject` | `Web\TransfersController::reject` | Rechazar transferencia |
| **Contacts (Web — session-protected)** | | | |
| GET | `/web/contacts` | `Web\ContactsController::index` | Listar contactos |
| POST | `/web/contacts` | `Web\ContactsController::store` | Crear contacto |
| GET | `/web/contacts/{id}` | `Web\ContactsController::edit` | Editar contacto |
| PUT | `/web/contacts/{id}` | `Web\ContactsController::update` | Actualizar contacto |
| DELETE | `/web/contacts/{id}` | `Web\ContactsController::delete` | Eliminar contacto |
| **Profile (Web — session-protected)** | | | |
| GET | `/profile` | `Web\ProfileController::index` | Perfil de usuario |
| GET | `/totp/setup` | `Web\AuthController::totpSetup` | Configurar TOTP |

**Total: 70+ rutas registradas (39+ REST API + 1 health + 5 auth + 5 FNMT + 10 web session + 6 web contacts + 2 profile + 2 timestamp + 1 home)**

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
| 10 | `auth_*` (SHIELD) | `UserIdentity`, `UserSecret` | `2026-07-13-200000`, `2026-07-14-300000` |
| 11 | `users.shield_user_id` | FK → `shield_users.id` | `2026-07-14-400000` |
| 12 | `notification_requested` | Outbox transaccional de notificaciones | `2026-07-14-500000` |
| 13 | `global_messaging_accounts` | Cuentas globales por canal | `2026-07-14-600000` |
| 14 | `settings` | Configuracion SHIELD (class/key/value) | `2026-07-14-700000` |
| 15 | `settings.context` | Columna de segregacion staging/prod | `2026-07-14-700001` |
| 16 | `documents.ipfs_cid` | Columna para IPFS CID | `2026-07-14-800000` |
| 17 | `documents.blockchain_anchor_id` | Columna para anclaje DLT | `2026-07-14-800000` |

### Caracteristicas del esquema

- **PK**: `CHAR(36)` UUID v4 en todas las tablas
- **Timestamps**: `created_at`, `updated_at` en todas las tablas
- **Foreign keys**: con restricciones `ON DELETE CASCADE` / `ON DELETE RESTRICT`
- **Charset**: `utf8mb4` con collation `utf8mb4_general_ci`
- **Engine**: `InnoDB` (soporte transaccional)
- **Unique keys**: `email` (users), `tax_id_hmac` (users), `idempotency_key` (transfers)

## Security Architecture

- **SHIELD**: autenticacion session-based, autorizacion por grupos, proteccion CSRF
- **api-auth filter**: filtro aplicado a todas las rutas API REST. Requiere sesion SHIELD activa con permisos de grupo
- **SecurityHeaders**: filtro global `after` que aplica 7 cabeceras OWASP
- **Throttle**: rate limiting configurable (token bucket basado en archivos)
- **forcehttps**: filtro global `before` (redireccion HTTP → HTTPS)
- **Cifrado AEAD**: NIF/NIE cifrado en reposo (AES-256-GCM); busqueda via HMAC determinista
- **AES-256-GCM para TOTP**: secretos TOTP cifrados reversiblemente
- **WebCrypto**: cifrado extremo a extremo de documentos en navegador
- **TOTP**: segundo factor con bloqueo atomico tras 5 fallos (30 min)
- **UUID v4**: evita enumeracion de IDs
- **Query Builder**: previene SQL injection (sin raw SQL)
- **Transacciones BD**: operaciones multi-tabla atomicas

## Testing

### Configuracion

- **Framework**: PHPUnit 10.x
- **Base de datos**: SQLite `:memory:` (grupo `tests` activado con `CI_ENVIRONMENT=testing`)
- **Bootstrap**: `vendor/codeigniter4/framework/system/Test/bootstrap.php`
- **Cobertura**: `clover.xml` + `html` en `build/logs/`

### Suite actual

- **33 archivos de test**: 9 model + 15 controller + 6 service + 3 otros
- **~500 assertions**
- **6 service tests**: LedgerService, StorageService, EvidenceService, X509Service, FnmtIdentityProvider, EncryptionService
- **15 controller tests**: 9 REST + 5 Web + Health
- **9 model tests**: todos los modelos del dominio
- **14 LedgerService tests**: Merkle tree, genesis, sealing, chain verification, tamper detection

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
│  │  mTLS   │    └──────┬──────┘              │
│  └─────────┘           │                      │
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
│  │  IPFS (cluster privado) — futuro        │  │
│  └─────────────────────────────────────────┘  │
└───────────────────────────────────────────────┘
```

- **Deploy**: SFTP rsync desde CI/CD (GitLab CI / GitHub Actions)
- **Staging**: `/var/www/staging/` con datos anonimizados
- **Produccion**: `/var/www/prod/` con backup de BD antes de migrar
- **Rollback**: `git checkout` a tag anterior + restore BD
