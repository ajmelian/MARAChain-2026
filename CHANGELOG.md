# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Planned
- WebCrypto client-side encryption for document uploads (Dropzone integration complete, pending full E2E flow)
- IPFS private cluster storage integration
- Email notification outbox with retry logic (SMTP integration)
- Organization/tenant support (multi-tenancy)
- Playwright E2E test suite (65 scenarios)
- API authentication (JWT/API keys for REST endpoints)
- CSRF protection on all web forms (AP-1 from audit)
- Session fixation fix in FNMT flow (AP-2 from audit)
- Rate limiting on TOTP routes (AP-3 from audit)
- Replace inline PHP template strings with view files (AP-4 from audit)

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
