# Configuration Guide

> **Version:** 1.8.0 | **Last Updated:** 2026-07-16

This document describes all configuration options for the MARAChain application.

---

## Environment Variables

All environment variables are defined in the `.env` file (copy from `env` template).

### CI_ENVIRONMENT

| Variable | Default | Required | Description |
|----------|---------|----------|-------------|
| `CI_ENVIRONMENT` | `production` | Yes | Sets the application environment |

**Values:**

| Value | Boot File | Description |
|-------|-----------|-------------|
| `development` | `Config/Boot/development.php` | Full error reporting, debug toolbar, CI_DEBUG=true |
| `testing` | `Config/Boot/testing.php` | SQLite :memory: database, suppressed errors |
| `production` | `Config/Boot/production.php` | No error display, optimized autoloader |

```ini
# .env
CI_ENVIRONMENT = development
```

---

## Database Configuration

### Default Connection (MySQL)

Defined in `app/Config/Database.php` → `$default` array.
All values can be overridden via `.env` using the format `database.default.{key}`.

| Variable | Default | Required | Description |
|----------|---------|----------|-------------|
| `database.default.hostname` | `localhost` | Yes | MySQL host |
| `database.default.database` | `ci4` | Yes | Database name |
| `database.default.username` | `root` | Yes | MySQL user |
| `database.default.password` | `root` | Yes | MySQL password |
| `database.default.DBDriver` | `MySQLi` | Yes | Database driver |
| `database.default.DBPrefix` | ` ` | No | Table prefix |
| `database.default.port` | `3306` | No | MySQL port |
| `database.default.charset` | `utf8mb4` | No | Character set |
| `database.default.DBCollat` | `utf8mb4_general_ci` | No | Collation |

```ini
# .env
database.default.hostname = localhost
database.default.database = marachain
database.default.username = marachain_user
database.default.password = secure_password_here
database.default.DBDriver = MySQLi
database.default.port = 3306
```

### Tests Connection (SQLite)

Defined in `app/Config/Database.php` → `$tests` array.
**Automatically selected** when `CI_ENVIRONMENT=testing`.

| Parameter | Value | Description |
|-----------|-------|-------------|
| `DBDriver` | `SQLite3` | In-memory database |
| `database` | `:memory:` | No persistent file |
| `DBPrefix` | `db_` | Prefix for test isolation |
| `foreignKeys` | `true` | FK enforcement |
| `busyTimeout` | `1000` | Lock timeout (ms) |

---

## Application Configuration

### `app/Config/App.php`

| Property | Default | Description |
|----------|---------|-------------|
| `$baseURL` | `'http://localhost:8080/'` | Application base URL (set via `app.baseURL` in .env) |
| `$indexPage` | `''` | URL index page (empty = no index.php) |
| `$forceGlobalSecureRequests` | `false` | Force HTTPS globally |
| `$CSPEnabled` | `false` | Content Security Policy |

### `app/Config/Encryption.php`

| Property | Default | Description |
|----------|---------|-------------|
| `$key` | `''` | Encryption key (hex-encoded, 32 bytes) |
| `$driver` | `'OpenSSL'` or `'Sodium'` | Crypto driver |
| `$blockSize` | `16` | Block size for encryption |
| `$digest` | `'SHA512'` | HMAC digest algorithm |

### MARAChain-Specific Encryption Keys

| Variable | Default | Required | Description |
|----------|---------|----------|-------------|
| `encryption.key` | `''` | Yes | AES-256-GCM key for TOTP secrets at rest (32 bytes = 64 hex chars) |
| `encryption.hmacKey` | `''` | Yes | HMAC key for tax ID lookups (64+ random chars) |

**Generate keys:**

```bash
# AES-256 key (32 bytes → 64 hex chars)
php -r "echo 'encryption.key = ' . bin2hex(random_bytes(32)) . PHP_EOL;"

# HMAC key
php -r "echo 'encryption.hmacKey = ' . bin2hex(random_bytes(32)) . PHP_EOL;"
```

---

## Validation Configuration

### Validation Groups (`app/Config/Validation.php`)

9 named validation groups (Fase 4 — completed):

| Group | Entity | Rules |
|-------|--------|-------|
| `$user` | User | email, identityType, firstName, lastName, phone (E.164), taxId (NIF/NIE/CIF) |
| `$device` | Device | deviceName, deviceType, publicKeyFingerprint (64 hex) |
| `$document` | Document | title, mimeType (PDF), fileSize, fileHashSha256 (64 hex) |
| `$transfer` | DocumentTransfer | securityLevel, idempotencyKey (64 hex), document/sender/recipient IDs |
| `$signature` | SignatureRequest | signatureIntent, provider, digestAlgorithm, manifestHash (64 hex), nonce |
| `$evidence` | Evidence | eventId (UUID v4), eventType, payloadJson, aggregateType/Id |
| `$ledger` | LedgerBlock | blockNumber, merkleRoot (64 hex), blockHash (64 hex), signature |
| `$contact` | Contact | contactType, names, email, phone (E.164), taxId, country (ISO 3166-1 alpha-2) |
| `$notification` | Notification | recipientEmail, notificationType (13 subtypes), subject, status, priority |

### Custom Rules (`app/Validation/CustomRules.php`)

| Rule | Validation |
|------|------------|
| `valid_tax_id` | Spanish NIF (8D+L), NIE (X/Y/Z+7D+L), CIF (L+7D+[DJ]) |
| `valid_phone_e164` | E.164: `^\+?[1-9]\d{1,14}$` |
| `valid_hex` | Hexadecimal string of exact length |
| `valid_uuid` | UUID v4: `^[0-9a-f]{8}-...-4...-[89ab]...-[0-9a-f]{12}$` |

---

## Filter Configuration

### Registered Filters

| Alias | Class | Type |
|-------|-------|------|
| `csrf` | `CSRF::class` | Security |
| `honeypot` | `Honeypot::class` | Security |
| `security` | `App\Filters\SecurityHeaders::class` | Security (MARAChain custom) |
| `api-auth` | `CodeIgniter\Shield\Filters\SessionAuth::class` | Auth (API protection) |
| `throttle` | `App\Filters\Throttle::class` | Rate Limiting |
| `session` | `CodeIgniter\Shield\Filters\SessionAuth::class` | Auth (SHIELD web) |
| `forcehttps` | `ForceHTTPS::class` | Security |

### Global Filters

| Position | Filter | Description |
|----------|--------|-------------|
| `before` | `forcehttps` | Redirect HTTP → HTTPS |
| `after` | `security` | MARAChain security headers — all responses |

### Throttle Configuration

| Group | Capacity | Rate | Description |
|-------|----------|------|-------------|
| `auth` | 6 | 6/min | Login, register, TOTP verify |
| `api` | 60 | 60/min | API REST endpoints |

---

## Route Configuration

### `app/Config/Routes.php`

70+ total routes defined:

| Group | Routes | Filter |
|-------|--------|--------|
| `/` (Home) | 1 | - |
| `/health` | 1 | - (public) |
| Auth (SHIELD) | 5 | `throttle:auth` |
| FNMT Auth | 5 | `throttle:auth` (POST) |
| API (api-auth) | 39+ | `api-auth` |
| Web (session) | 18 | `session` |

---

## Email

MARAChain uses email for notification delivery (SMTP):

```ini
# .env
email.fromEmail = 'noreply@marachain.example.com'
email.fromName = 'MARAChain'
email.SMTPHost = 'smtp.example.com'
email.SMTPPort = 587
email.SMTPUser = 'smtp_user'
email.SMTPPass = 'smtp_password'
email.protocol = 'smtp'
```

---

## Notification Channels

| Channel | Provider Class | Status |
|---------|---------------|--------|
| `EMAIL` | `EmailNotificationProvider` | **Active** — SMTP via `email.*` env vars |
| `WHATSAPP` | `WhatsAppNotificationProvider` | Stub — pending PoC |
| `TELEGRAM` | `TelegramNotificationProvider` | Stub — pending PoC |
| `SMS` | `SmsNotificationProvider` | Stub — pending integration |

### Secretos de Proveedores

Las credenciales se almacenan **fuera de `wwwroot/`** en:

```
/var/lib/marachain/integrations/
├── whatsapp/global/
├── telegram/global/
└── sms/global/
```

---

## Feature Flags

| Feature | Status | Version |
|---------|--------|---------|
| SHIELD authentication | Active | 1.2.0 |
| TOTP 2FA | Active | 1.2.1 (AES-256-GCM) |
| Rate limiting (Throttle) | Active | 1.2.0 |
| Health check endpoint | Active | 1.2.0 (ampliado 1.8.0) |
| Web frontend (Bootstrap 5.3 + Alpino) | Active | 1.8.0 (BS5 migrado) |
| PWA (manifest + service worker) | Active | 1.8.0 |
| CLI commands (ledger, notifications, transfers, ipfs) | Active | 1.8.0 (5 commands) |
| Service layer (ports & adapters) | Active | 1.2.0 |
| FNMT certificate auth (mTLS) | Active | 1.4.0 |
| FNMT GET rate limiting | Active | 1.8.0 |
| Document upload with ciphertext (StorageService) | Active | 1.4.0 |
| IPFS private cluster storage | Active | 1.8.0 |
| IpfsReconcile command | Active | 1.8.0 |
| Evidence recording (EvidenceService) | Active | 1.4.0 |
| SHIELD-user linkage (BaseWebController) | Active | 1.4.0 |
| Dropzone JS + MARACrypto client encryption | Active | 1.4.0 |
| Deploy scripts (staging/prod) | Active | 1.4.0 |
| Systemd workers (notifications, ledger-seal, transfers-expire) | Active | 1.8.0 |
| Notification system (multi-channel outbox) | Active | 1.5.0 |
| Email notification provider (SMTP) | Active | 1.5.0 |
| WhatsApp notification provider | Stub | 1.5.0 |
| Telegram notification provider | Stub | 1.5.0 |
| SMS notification provider | Stub | 1.5.0 |
| SHIELD Settings table (DB-backed) | Active | 1.6.0 |
| Context column for settings segregation | Active | 1.6.0 |
| api-auth filter (API route protection) | Active | 1.6.0 |
| FNMT TOTP rate limiting | Active | 1.6.0 |
| NotificationRequestedModel (outbox model) | Active | 1.6.0 |
| TimestampService | Active | 1.7.0 |
| TimestampController (REST) | Active | 1.7.0 |
| Timestamp receipt endpoint (Merkle proof) | Active | 1.8.0 |
| IPFS CID + blockchain anchor columns | Active | 1.7.0 |
| Merkle proofs (generateProof) | Active | 1.8.0 |
| OpenAPI 3.1 + Swagger UI | Active | 1.8.0 |
| PHPStan CI4 (static analysis) | Active (dev) | 1.8.0 |
| SonarQube integration | Active | 1.8.0 |
| Blockchain anchoring (external DLT) | Planned | - |
| Playwright E2E tests | Planned | - |
| Multi-tenancy | Planned | - |

---

## Consistency Verification Checklist

- [ ] `.env` file exists and is not committed to git (in `.gitignore`)
- [ ] `encryption.key` is set to a 64-character hex string
- [ ] `encryption.hmacKey` is set and not empty
- [ ] Database credentials are correct for the environment
- [ ] `CI_ENVIRONMENT` matches the deployment target
- [ ] `app.baseURL` matches the actual domain
- [ ] Security headers are enabled (`security` filter in `$globals['after']`)
- [ ] `$forceGlobalSecureRequests` is `true` in production
- [ ] `CI_DEBUG` is `false` in production
