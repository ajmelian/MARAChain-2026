# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Planned
- Authentication hardening (P1-1: FNMT login notification, P1-2: TOTP reuse prevention, P1-3: SHIELD session config compliance)
- Document transfer complete flow (P1-4: detail view mock data, P1-5: accept/reject web UI)
- Transactional notification outbox (P1-7: atomic insert with business operations)
- Ed25519 signatures for ledger blocks (P2-5: replace placeholder HMAC)
- CSP hardening: remove `unsafe-inline` (P2-1)
- Additional security headers: Cross-Origin-Opener-Policy, Cross-Origin-Embedder-Policy (P2-2)
- Organization/tenant support (multi-tenancy)
- Playwright E2E test suite (65 scenarios)
- Queue worker for async operations (P2-9)
- CSRF protection on all web forms (AP-1 from audit)
- Session fixation fix in FNMT flow (AP-2 from audit)
- Replace inline PHP template strings with view files (AP-4 from audit)
- ADR documents in `docs/adr/` directory (P3-7)

---

## [1.8.0] - 2026-07-16

### Added
- **OpenAPI 3.1 + Swagger UI**: especificacion `marachain-v1.yaml` (3133 lines, 46 endpoints, 12 tags, 29 schemas) como fuente unica de verdad del contrato API. `Api\DocsController` sirve Swagger UI en `GET /api/docs` (solo desarrollo). Spec publico en `/api.yaml`.
- **IPFS privado (cluster)**: implementacion completa (IP-1 a IP-6). `StorageService` ahora persiste ciphertext en IPFS privado ademas de MySQL. `Commands/IpfsReconcile.php` (`php spark ipfs:reconcile`) sincroniza documentos entre BD e IPFS. Columna `documents.ipfs_cid` activa. Cluster IPFS privado con acceso solo desde nodos autorizados.
- **Merkle proofs** (S-2): `LedgerService::generateProof(eventId)` genera prueba de inclusion criptografica con `siblings[]` y `directions[]`. Un tercero puede verificar inclusion sin descargar el ledger completo.
- **Receipt endpoint** (S-4): `GET /timestamps/{hash}/receipt` devuelve recibo JSON con Merkle proof verificable.
- **Systemd workers** (I-4): 3 service + 3 timer units en `scripts/systemd/`:
  - `marachain-notifications.service` + `.timer` (cada 1 min)
  - `marachain-ledger-seal.service` + `.timer` (cada 15 min)
  - `marachain-transfers-expire.service` + `.timer` (cada 5 min)
- **TransferExpire command**: `php spark transfers:expire` expira transferencias caducadas via systemd timer.
- **Health check ampliado** (I-5): `HealthController` ahora reporta `pending_notifications`, SMTP socket test, IPFS API connectivity, y disk space usage (degraded >90%).
- **Bootstrap 5.3 migracion** (I-2): BS4→BS5.3.3 en las 13 vistas. Clases CSS, data-attributes, y componentes actualizados.
- **PWA support** (I-2): `manifest.json` (standalone, theme #673ab7) + service worker `sw.js` (cache-first, 14 assets estaticos).
- **PHPStan CI4** (dev): `codeigniter/phpstan-codeigniter ^2.1`, `phpstan.neon` level 2, `_ide_helper.php` stubs, `phpstan-bootstrap.php`.
- **SonarQube badges** en README.md: Quality Gate, Reliability, Security, Maintainability, Coverage.
- **Rate limiting en GET /auth/fnmt** (S-6): `throttle:auth` (6 req/min) aplicado a la ruta GET de autenticacion FNMT. Corrige P2-4 del backlog.

### Changed
- **PHP version**: `composer.json` requiere `^8.4` (antes `^8.2`). PHP 8.5 en CI (corrige P2-15).
- **Tests actualizados**: 284 tests, 707 assertions (antes ~500 assertions en 33 archivos).
- **Controladores REST**: 14 (antes 12). Nuevo: `Api\DocsController`.
- **CLI Commands**: 5 (antes 4). Nuevos: `TransferExpire`, `IpfsReconcile`.
- **Recuento de rutas**: 75+ (antes 70+). Nuevas: `/api/docs`, `/api.yaml`, `/timestamps/{hash}/receipt`.
- **Bootstrap 5.3.3** CSS + JS descargados localmente en `public/assets/plugins/bootstrap/`.
- Todas las referencias de documentacion cruzada actualizadas para v1.8.0.

### Fixed
- **P2-4**: Rate limiting en `GET /auth/fnmt` ahora activo (antes solo POST de TOTP tenian throttle).
- **P2-6**: Merkle proofs implementados via `generateProof()`.
- **P2-7**: `sealBlock()` ahora invocable automaticamente via systemd timer (cada 15 min).
- **P2-10**: Archivos systemd creados para workers (notifications, ledger-seal, transfers-expire).
- **P2-15**: PHP 8.5 en composer.json (antes 8.2).
- **P2-16**: SonarQube integrado con badges en README.

### Security
- Rate limiting ampliado a `GET /auth/fnmt` (prevencion de DoS y enumeracion de certificados).
- IPFS privado: documentos solo accesibles desde nodos autorizados del cluster.
- Merkle proofs permiten verificacion de integridad sin exposicion del ledger completo.
- Health check no expone datos sensibles (solo metricas de infraestructura).
- Systemd workers isolados con restart automatico y logging via journald.

---

## [1.7.0] - 2026-07-16

### Added
- **TimestampService** (`app/Services/TimestampService.php`): implementacion del servicio de sellado de tiempo confiable contra `TimestampProviderInterface`.
- **TimestampController** (`app/Controllers/TimestampController.php`): 12th REST controller con endpoints de sellado de tiempo.
- **Migration 800000** (`2026-07-14-800000_AddIpfsAndBlockchainIds.php`): columnas `ipfs_cid` y `blockchain_anchor_id` para almacenamiento distribuido y anclaje DLT futuro.

### Changed
- **OpenSpec `.state.yaml` actualizado**: 64 de 66 tareas marcadas como `completed`. Solo tarea 065 (E2E Tests) permanece `pending` y tarea 066 (Documentacion) como `in_progress`.
- **Fase 4 (Validacion) finalizada**: los 9 grupos de reglas de validacion y 4 CustomRules confirmados como completados en el state tracker.
- **Fase 5 (Controller Tests) finalizada**: 15 archivos de test de controladores confirmados.
- **Fase 6 (Controllers) finalizada**: 14 REST controllers + 6 Web controllers confirmados.
- **Fase 7 (Security) finalizada**: SecurityHeaders filter + Throttle filter + api-auth filter confirmados.
- **Recuento de migraciones actualizado**: 17 migraciones (antes 16). Nueva: `800000_AddIpfsAndBlockchainIds`.
- **Recuento de servicios actualizado**: 11 servicios (antes 10). Nuevo: `TimestampService`.
- **Recuento de controladores REST actualizado**: 12 REST (antes 11). Nuevo: `TimestampController`.
- **Totales de tests verificados**: ~500 assertions en 33 archivos de test (9 model + 15 controller + 6 service + 3 otros).
- Todas las referencias de documentacion cruzada actualizadas y verificadas para consistencia.

### Fixed
- **Documentacion inconsistente** corregida en README.md, ARCHITECTURE.md, INSTALL.md: conteos de migraciones (16→17), controladores REST (11→12), servicios (10→11), tests (→33 archivos).
- **CHANGELOG.md** sincronizado con VERSION.md (ambos a 1.7.0).
- **AUDITORY.md** actualizado con entrada de auditoria de documentacion v1.7.0.
- **CONFIGURATION.md** y **SECURITY.md** version headers actualizados a 1.7.0.
- **INSTALL.md** conteo de tests corregido y comandos actualizados.

### Security
- Documentation-only release; sin cambios en codigo de seguridad.
- Todas las politicas de seguridad en SECURITY.md permanecen vigentes.

---

## [1.6.0] - 2026-07-16

### Added
- **Settings table migration** (`2026-07-14-700000_CreateSettingsTable.php`): tabla `settings` para almacenamiento de configuracion SHIELD en base de datos. Soporta pares `class`/`key`/`value` con tipos (`string`, `int`, `bool`, `array`, `json`).
- **Context column migration** (`2026-07-14-700001_AddContextColumn.php`): anade columna `context` (varchar 255) a la tabla `settings` para segregacion de configuracion por entorno/aplicacion.
- **NotificationRequestedModel** (`app/Models/NotificationRequestedModel.php`): modelo de persistencia para el outbox transaccional `notification_requested`. Gestiona estados `QUEUED`, `PROCESSING`, `SENT`, `FAILED`, `DEAD_LETTER` con idempotencia via `idempotency_key`. Soporte para reintentos con exponential backoff y circuit breaker.
- **`api-auth` filter**: nuevo filtro de autorizacion aplicado a todas las rutas API REST (`/users`, `/devices`, `/documents`, `/transfers`, `/signatures`, `/evidence`, `/ledger`, `/contacts`, `/notifications`). Las rutas API ahora requieren sesion SHIELD activa con permisos de grupo.
- **Rate limiting en rutas FNMT TOTP**: `throttle:auth` aplicado a `auth/fnmt/totp-setup` (POST) y `auth/fnmt/totp-verify` (POST). Corrige AP-3 del audit report (brute-force de codigos TOTP de 6 digitos).
- **Nueva ruta**: `GET /totp/setup` (web, session-protected) para configuracion de TOTP desde la interfaz web.
- **Nuevos tests de servicios**: `StorageServiceTest`, `FnmtIdentityProviderTest`, `EvidenceServiceTest`, `X509ServiceTest`, `EncryptionServiceTest` (5 nuevos archivos de test, ~40 tests adicionales).
- **Nuevos tests de controladores web**: `AuthControllerTest`, `ContactsWebTest`, `TransfersWebTest`, `HealthControllerTest`, `LedgerControllerApiTest` (5 nuevos archivos de test).

### Changed
- **Rutas API protegidas**: todas las rutas REST ahora estan dentro del grupo `api-auth` en lugar de ser publicas. Requiere sesion SHIELD con permisos adecuados (superadmin, admin, developer, o user).
- **`Routes.php` reorganizado**: estructura clara con secciones: Health, Auth (rate-limited), Web (session-protected), API (api-auth).
- **Totales actualizados**: 16 migraciones (antes 14), 10 modelos (antes 9), +10 archivos de test (35 total).
- Migraciones 700000 y 700001 se ejecutan automaticamente tras `php spark shield:setup`.

### Security
- **api-auth filter**: acceso a endpoints REST requiere autenticacion SHIELD. Previene acceso no autenticado a datos de usuarios, documentos, y evidencias.
- **FNMT TOTP rate limiting**: protege contra brute-force de codigos TOTP con limite de 6 req/min (AP-3 corregido).
- **Settings table**: configuracion SHIELD persistida en BD en lugar de archivos, con soporte para `context` (segregacion staging/prod).

---

## [1.5.0] - 2026-07-14

### Added
- **Notification system (`app/Notifications/`)** — multi-channel notification outbox with provider abstraction
  - `NotificationChannel` enum (PHP 8.2+): `EMAIL`, `WHATSAPP`, `TELEGRAM`, `SMS`
  - `NotificationProviderInterface` — contrato `send()` y `health()` para todos los canales
  - `EmailNotificationProvider` — implementacion real SMTP via `EmailNotificationProvider`
  - `WhatsAppNotificationProvider` — stub preparado para integracion futura (cuenta global corporativa)
  - `TelegramNotificationProvider` — stub preparado para integracion futura (Bot API / MTProto)
  - `SmsNotificationProvider` — stub preparado para integracion futura
  - `RecipientAddress` value object — direccion del destinatario por canal
  - `NotificationMessage` value object — contenido del mensaje (titulo, cuerpo, metadata)
  - `NotificationResult` value object — resultado del envio (estado, id del proveedor, error)
- **NotificationsCommand** (`app/Commands/NotificationsCommand.php`): `php spark notifications:send` — CLI worker para procesar el outbox transaccional. Reemplaza al anterior `NotificationSend` con soporte multi-canal y provider resolution.
- **Global messaging accounts** — migracion `2026-07-14-600000_CreateGlobalMessagingAccountsTable.php`: tabla `global_messaging_accounts` para gestionar cuentas globales corporativas por canal y entorno. Estados: `PENDING_CONFIGURATION`, `CONNECTED`, `DEGRADED`, `DISCONNECTED`, `DISABLED`, `ERROR`. Una cuenta activa por canal y entorno.
- **Notification outbox transaccional** — migracion `2026-07-14-500000_CreateNotificationRequestedTable.php`: tabla `notification_requested` como outbox transaccional con soporte para idempotencia, reintentos con backoff, circuit breaker, y dead-letter.

### Changed
- `NotificationSend` command legacy reemplazado por `NotificationsCommand` multi-canal
- Arquitectura de notificaciones migrada de email-only a multi-canal con provider pattern
- Documentacion actualizada: nuevo ADR-019, canales SMTP documentados en CONFIGURATION.md

### Security
- Secretos de proveedores (WhatsApp, Telegram) almacenados fuera de `wwwroot/` en `/var/lib/marachain/integrations/`
- Referencias opacas en MySQL (nunca credenciales en texto claro)
- Cifrado en reposo para credenciales de cuentas globales
- Rotacion y revocacion de secretos por canal
- Fallback por email si un canal complementario falla
- Ningun contenido sensible (documento, CID, claves, hash) se envia en notificaciones

---

## [1.4.0] - 2026-07-14

### Added
- **StorageService** (`app/Services/StorageService.php`): almacena ciphertext de documentos cifrados en BD. Valida el envelope `marachain-envelope v1` (version, algorithm, iv, ciphertext, tag). Operaciones `store()` y `retrieve()`.
- **EvidenceService** (`app/Services/EvidenceService.php`): registro automatico de eventos de negocio (DocumentSent, TransferAccepted, TransferRejected, DocumentUploaded). Metodo `record()` con soporte para payload JSON y aggregate references.
- **DocumentUploadController** (`app/Controllers/DocumentUploadController.php`): endpoint `POST /documents/upload` que recibe archivo cifrado multipart + envelope JSON. Valida formato de envelope, MIME types, y limites de tamano.
- **TransferController::accept / reject**: endpoints `POST /transfers/:id/accept` y `POST /transfers/:id/reject` con registro automatico de evidencias via EvidenceService.
- **SHIELD-user linkage**: nueva migracion `2026-07-14-400000_add_shield_user_id_to_users` que anade columna `shield_user_id` (INT FK → `shield_users.id`) a la tabla `users`. Permite que SHIELD gestione autenticacion mientras `users` almacena datos especificos de MARAChain.
- **BaseWebController::getAuthenticatedUserId()**: resolucion del `user.id` (UUID v4) desde el `shield_user_id` de la sesion SHIELD activa. Usado por controladores web para operaciones de negocio.
- **TransfersController (web) con datos reales**: inbox/outbox ahora consultan `DocumentTransferModel` real en lugar de mock data. Las vistas muestran transferencias del usuario autenticado.
- **Dropzone JS + MARACrypto**: integracion del componente Dropzone para upload de documentos con cifrado client-side. `MARACrypto.encryptDocument()` genera DEK, cifra contenido con AES-256-GCM via WebCrypto, y construye el envelope antes del upload.
- **Helpers/Uuid.php**: funcion `generate_uuid_v4()` centralizada para generacion DRY de UUIDs RFC 4122 (antes duplicada en 10 archivos).
- **nginx-fnmt-mtls.conf**: configuracion completa de Nginx para autenticacion mTLS con certificados FNMT. Incluye `ssl_verify_client optional`, paso de cabeceras `SSL_CLIENT_*` a PHP-FPM, y location `/auth/fnmt` con mTLS obligatorio.
- **Deploy scripts**: `scripts/deploy-staging.sh` y `scripts/deploy-prod.sh` para despliegue automatizado via rsync a VPS. Soportan releases atomicas con symlink `current/`.
- **`.env.example`**: template de configuracion con variables MySQL, SMTP, y encryption keys documentadas.

### Changed
- `BaseWebController` anade `getAuthenticatedUserId()` para vincular sesion SHIELD con usuario MARAChain.
- `TransfersController` (web) reemplaza mock data por consultas reales a `DocumentTransferModel`.
- `Routes.php` ampliado con rutas de upload (`/documents/upload`), accept/reject de transferencias, y rutas FNMT auth.
- `User` entity ahora incluye `shield_user_id` en `$datamap` para linkage bidireccional.
- `DatabaseSeeder` actualizado con datos de prueba para el nuevo linkage SHIELD-user.
- `.gitignore` actualizado con exclusiones de `resources/frontend/alpino/original/` y `wwwroot/build/*`.
- `composer.json` mantiene dependencias `codeigniter4/shield: ^1.3`, `codeigniter4/settings: ^2.3`.

### Security
- Nginx mTLS config con `ssl_verify_client optional` y validacion estricta en `/auth/fnmt`.
- StorageService valida integridad del envelope criptografico (tag AEAD) antes de almacenar.
- DocumentUploadController rechaza envelopes con algoritmos no soportados o versiones incorrectas.
- Dropzone cifra documentos en navegador: la DEK nunca sale del cliente, el backend solo ve ciphertext.

---

## [1.2.1] - 2026-07-14

### Fixed
- **Cryptography (CR-1)**: `FnmtController::decryptTotpSecret` returning empty string — replaced HMAC with AES-256-GCM AEAD for TOTP secret encryption/decryption. Existing users require TOTP re-enrollment.
- **Data Integrity (CR-2)**: `LedgerService::sealBlock` without database transaction — wrapped in `transStart()`/`transComplete()` with rollback on failure.
- **Chain Verification (CR-3)**: `verifyChain` tautological block hash verification — recomputed Merkle root now used before block hash computation.
- **Race Condition (CR-4)**: TOCTOU in `incrementoTotpFailures` and `incrementAttemptCount` — replaced with atomic `SET col = col + 1` via Query Builder.
- **State Machine (HI-6)**: `revokeTransfer` bypassed state transition validator — added `allowedTransitions()` guard.
- **Concurrency (HI-7)**: `transitionStatus` without atomic state guard — added `->where('status', $row['status'])` to prevent lost updates.
- **Error Suppression (HI-8)**: `@` operator in `NotificationSend::sendEmail` — removed; added `FILTER_VALIDATE_EMAIL` and `error_get_last()` logging.

### Security
- **Hardcoded Secret (HI-1)**: Removed `marachain-dev-key` HMAC fallback in `FnmtController` — now throws `RuntimeException` if env var unset.
- **Swallowed Errors (HI-2)**: `AuthController::register` profile creation failure now logs critical, attempts user rollback, and returns error to user.
- **Audit Trail Loss (HI-3)**: Evidence recording failures now logged at `critical` level with `EVIDENCE_LOST:` prefix and full stack trace.
- **Trust Boundary (HI-4)**: Replaced `$_SERVER` with `$this->request->getServer()` in `FnmtController` to prevent SSL header injection.
- **Type Safety (HI-5)**: `AuthController::updateLastLogin` now guards against null `$user->id` before calling model method.

### Changed
- Test suite expanded: 164→178 tests, 390→422 assertions (added 14 `LedgerServiceTest` tests)
- `DocumentTransfer` entity: `REVOKED` added to allowed transitions from `PENDING_RECIPIENT`, `READY`, `SENDING`, `SENT`

---

## [1.2.0] - 2026-07-14

### Added
- **SHIELD Authentication Integration**: session-based login, register, logout
  - `Web\AuthController` — login, register, logout with backend + frontend validation
  - `Web\BaseWebController` — base class for web HTML controllers with shared rendering
  - SHIELD auth tables migration (`create_auth_tables`) via `php spark shield:setup`
  - Config files: `AuthGroups.php`, `AuthToken.php`, `Settings.php`
  - `session` filter applied to protected web routes (inbox, outbox, profile, contacts)
  - `throttle:auth` rate limiting on login and register endpoints
  - `composer.json` updated: `codeigniter4/shield: ^1.3`, `codeigniter4/settings: ^2.3`
- **Web Frontend — Bootstrap 5 + Alpino Admin Dashboard**
  - `Views/layouts/main.php` — main layout template
  - `Views/auth/login.php` — login page
  - `Views/auth/register.php` — registration page
  - `Views/transfers/` — inbox, outbox, create transfer views
  - `Views/contacts/index.php` — contacts management view
  - `Views/profile/index.php` — user profile view
  - `Web\TransfersController` — inbox, outbox, create transfer (HTML)
  - `Web\ContactsController` — index, store, edit, update, delete (HTML)
  - `Web\ProfileController` — user profile page (HTML)
  - Alpino Bootstrap 5 Admin Dashboard theme in `resources/frontend/`
  - `Feature::$autoRoutesImproved = true` enabled
- **Service Layer**: 6 interfaces/services for provider abstraction
  - `IdentityProviderInterface` — identity verification abstraction (FNMT, DNIe)
  - `SignatureProviderInterface` — signature provider abstraction
  - `EncryptionService` — encryption/decryption service
  - `TimestampProviderInterface` — trusted timestamping abstraction
  - `LedgerService` — ledger block creation, Merkle tree, chain integrity verification
  - `LedgerAnchorInterface` — external blockchain anchoring abstraction
- **CLI Commands** (`php spark`):
  - `ledger:genesis` — create the MARAChain genesis block (#1)
  - `ledger:seal` (`LedgerSeal`) — seal pending evidence into a new ledger block
  - `notification:send` (`NotificationSend`) — process pending notifications with retry
- **Rate Limiting**: `App\Filters\Throttle` — file-based token bucket
  - Configurable per route group: `auth` (6 req/min), `api` (60 req/min)
  - SHA1 fingerprint based on IP + path
  - Returns HTTP 429 with `retry_after` header
- **Health Check**: `HealthController` — `GET /health` endpoint
  - Checks database connectivity, migrations applied, ledger block count
  - Returns HTTP 200 (healthy) or 503 (degraded) with JSON report
  - Used by deploy scripts (smoke test) and monitoring
- **Other**:
  - `Common.php` — shared helper functions
  - `Language/en/Validation.php` — English validation error messages
  - `LedgerServiceTest` — unit test for ledger service
  - Web routes: `/login`, `/register`, `/logout`, `/inbox`, `/outbox`, `/transfers/new`, `/profile`, `/web/contacts/*`

### Changed
- `Routes.php` reorganized with clear sections: health, auth, web (session-protected), API
- `composer.json` added dependencies: `codeigniter4/shield`, `codeigniter4/settings`
- Route count increased from 37 to 55+ (added web/auth routes)

### Fixed
- Protected web routes behind SHIELD `session` filter to prevent unauthorized access
- Login and register endpoints rate-limited to prevent brute force attacks

### Security
- SHIELD session-based authentication with secure password hashing
- Rate limiting on authentication endpoints (brute force prevention)
- Health endpoint does not expose sensitive internal details
- All web forms validated on both frontend and backend

---

## [1.1.1] - 2026-07-13

### Added
- **Entity layer**: 9 entities with CI4 `$casts` and `$datamap` (snake_case ↔ camelCase)
  - `User` — identity (physical/legal), NIF/NIE encrypted, TOTP, guarantee level (eIDAS)
  - `Device` — device registration with public key fingerprint
  - `Document` — document metadata, versioning, SHA-256 hashing
  - `DocumentTransfer` — transfer ACL, idempotency key, security levels
  - `SignatureRequest` — signature intent, manifest hash, nonce, provider
  - `Evidence` — append-only events, payload JSON, aggregate references
  - `LedgerBlock` — block number, Merkle root, block signature
  - `Contact` — address book (physical/legal entities)
  - `Notification` — email outbox, retry logic, dead letter queue
- **Migration layer**: 9 database migrations using CI4 Forge
  - UUID v4 `CHAR(36)` primary keys on all tables
  - `ENUM` columns for statuses and type classifications
  - Foreign keys with `ON DELETE CASCADE` / `ON DELETE RESTRICT`
  - `utf8mb4` charset with appropriate collations
  - Unique constraints on `email`, `tax_id_hmac`, `idempotency_key`
  - Indexes on frequently queried columns (`status`, `event_type`, `aggregate_id`)
- **Model layer**: 9 models with business logic methods
  - `UserModel` — CRUD + TOTP management (enable/disable/block after 5 failures)
  - `DeviceModel` — register, revoke, mark as lost, update last seen
  - `DocumentModel` — create, seal (immutable), version control, hash lookup
  - `DocumentTransferModel` — create transfer, revoke, inbox/outbox queries
  - `SignatureRequestModel` — request, consume, validate, nonce tracking
  - `EvidenceModel` — append-only creation, aggregate queries, ledger inclusion
  - `LedgerBlockModel` — block creation, chain integrity verification, Merkle validation
  - `ContactModel` — CRUD + filtered search by type/status
  - `NotificationModel` — outbox pattern, retry count, dead letter after max attempts
  - All models use Query Builder (no raw SQL with concatenation)
- **Controller layer**: 9 REST controllers with 35 API endpoints
  - `UserController` — `index`, `show`, `create`, `update`, `delete`, `enableTotp`
  - `DeviceController` — `index`, `show`, `register`, `revoke`
  - `DocumentController` — `index`, `show`, `create`, `seal`, `delete`
  - `TransferController` — `index`, `outbox`, `inbox`, `create`, `show`, `revoke`
  - `SignatureController` — `request`, `show`
  - `EvidenceController` — `index`, `show`
  - `LedgerController` — `index`, `show`, `verify`
  - `ContactController` — `index`, `show`, `create`, `update`, `delete`
  - `NotificationController` — `index`, `show`
- **BaseController**: shared utilities for all controllers
  - `camelToSnake()` — converts JSON input keys for validation compatibility
  - `validateGroup()` — validates data against named groups from `Config\Validation`
- **Input validation**: 9 validation groups in `Config\Validation.php`
  - `user` — email, identity type, first name, phone (E.164), tax ID (NIF/NIE/CIF)
  - `device` — device name, type, public key fingerprint (64 hex chars)
  - `document` — title, MIME type (PDF only), file size, SHA-256 hash
  - `transfer` — security level, idempotency key (64 hex), document/sender/recipient IDs
  - `signature` — intent, provider, digest algorithm, manifest hash, nonce
  - `evidence` — event ID (36 chars UUID), event type, payload JSON, hash
  - `ledger` — block number, Merkle root (64 hex), block hash (64 hex), signature
  - `contact` — type, names, email, phone (E.164), tax ID, country (ISO 3166-1 alpha-2)
  - `notification` — recipient email, type (13 subtypes), subject, status, priority
- **Custom validation rules**: `CustomRules.php`
  - `valid_tax_id` — Spanish NIF (8D+L), NIE (X/Y/Z+7D+L), CIF (L+7D+[DJ])
  - `valid_phone_e164` — E.164 format (`^\+?[1-9]\d{1,14}$`)
  - `valid_hex` — hexadecimal string of exact length (e.g., SHA-256 = 64 chars)
  - `valid_uuid` — UUID v4 regex (`^[0-9a-f]{8}-...-4...-[89ab]...-[0-9a-f]{12}$`)
- **Security headers filter**: `App\Filters\SecurityHeaders`
  - 7 OWASP-compliant headers applied globally as `after` filter
  - `X-Content-Type-Options: nosniff`
  - `X-Frame-Options: DENY`
  - `X-XSS-Protection: 1; mode=block`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Strict-Transport-Security: max-age=31536000; includeSubDomains`
  - `Content-Security-Policy: default-src 'self'`
  - `Permissions-Policy: camera=(), microphone=(), geolocation=()`
- **PHPUnit test suite**: 164 tests, 390 assertions (SQLite :memory:)
  - 9 model test files covering CRUD, edge cases, business logic, TOTP flow
  - 9 controller test files covering HTTP status codes, validation, error responses
  - 2 health tests validating app configuration
  - CI4 `FeatureTestTrait` for HTTP integration testing
  - SQLite `:memory:` database with automatic migration before each test
- **CI4 bootstrap**: full CodeIgniter 4 application structure
  - `composer.json` with `codeigniter4/framework: ^4.7` and `php: ^8.2`
  - `phpunit.xml.dist` with environment set to `testing`
  - `spark` CLI entry point
  - `.env` template with all configurable options
- **UUID v4 generation**: RFC 4122-compliant UUID generation in all models
- **camelCase/snake_case datamap**: transparent mapping via CI4 Entity `$datamap`
- **Routes**: 37 total routes (35 API + 1 home + 1 ledger/verify)

### Security
- Encrypted tax ID storage pattern (AEAD + HMAC search key)
- TOTP with progressive blocking (5 failures → 30 min block)
- All IDs use UUID v4 (prevents enumeration)
- Prepared statements via CI4 Query Builder throughout
- Input validation on all controller endpoints
- Security headers on every response
- No secrets hardcoded in source

---

## [1.0.0] - 2026-07-13

### Added
- Initial project setup
- CodeIgniter 4 application bootstrap
- Project specification documents (7 client documents in `docs/`)
- OpenSpec SDD specification (`project.openspec.yaml`)
- Task roadmap with 66 atomic tasks (`roadmap.yaml`)
- Environment configuration (`development`, `testing`, `production`)
- Database configuration (MySQL default, SQLite tests)
