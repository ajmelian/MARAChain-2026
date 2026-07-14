# Configuration Guide

> **Version:** 1.2.0 | **Last Updated:** 2026-07-14

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

### Auto-selection Logic

```php
// app/Config/Database.php
public function __construct()
{
    parent::__construct();
    if (ENVIRONMENT === 'testing') {
        $this->defaultGroup = 'tests';
    }
}
```

---

## Application Configuration

### `app/Config/App.php`

| Property | Default | Description |
|----------|---------|-------------|
| `$baseURL` | `'http://localhost:8080/'` | Application base URL (set via `app.baseURL` in .env) |
| `$indexPage` | `''` | URL index page (empty = no index.php) |
| `$forceGlobalSecureRequests` | `false` | Force HTTPS globally |
| `$CSPEnabled` | `false` | Content Security Policy |

```ini
# .env
app.baseURL = 'https://marachain.example.com/'
app.forceGlobalSecureRequests = false
app.CSPEnabled = false
```

### `app/Config/Security.php`

| Property | Default | Description |
|----------|---------|-------------|
| `$csrfProtection` | `'cookie'` | CSRF protection method |
| `$tokenRandomize` | `false` | Randomize CSRF token |
| `$regenerate` | `true` | Regenerate token on every request |

### `app/Config/Encryption.php`

| Property | Default | Description |
|----------|---------|-------------|
| `$key` | `''` | Encryption key (hex-encoded, 32 bytes) |
| `$driver` | `'OpenSSL'` or `'Sodium'` | Crypto driver |
| `$blockSize` | `16` | Block size for encryption |
| `$digest` | `'SHA512'` | HMAC digest algorithm |

```ini
# .env
encryption.key = hex64charstringhere
```

### `app/Config/Session.php`

| Property | Default | Description |
|----------|---------|-------------|
| `$driver` | `'CodeIgniter\Session\Handlers\FileHandler'` | Session handler |
| `$cookieName` | `'ci_session'` | Session cookie name |
| `$expiration` | `7200` | Session lifetime (seconds) |
| `$savePath` | `WRITEPATH . 'session'` | Session save path |

---

## Validation Configuration

### Validation Groups (`app/Config/Validation.php`)

9 named validation groups, one per entity:

| Group | Key | Rules Applied |
|-------|-----|--------------|
| `$user` | `email`, `identityType`, `firstName`, `lastName`, `legalName`, `status`, `guaranteeLevel`, `phone`, `taxIdEncrypted` | `required`, `valid_email`, `max_length`, `in_list`, `valid_phone_e164`, `valid_tax_id` |
| `$device` | `deviceName`, `deviceType`, `publicKeyFingerprint`, `publicKeyAlgorithm` | `required`, `max_length`, `in_list`, `exact_length[64]` |
| `$document` | `title`, `mimeType`, `fileSize`, `fileHashSha256`, `ownerId` | `required`, `max_length`, `in_list`, `greater_than`, `exact_length[64]` |
| `$transfer` | `securityLevel`, `idempotencyKey`, `documentId`, `senderId`, `recipientId` | `required`, `in_list`, `exact_length[64]` |
| `$signature` | `signatureIntent`, `signatureProvider`, `digestAlgorithm`, `manifestHash`, `nonce`, `documentId`, `userId` | `required`, `max_length`, `exact_length[64]` |
| `$evidence` | `eventId`, `eventType`, `payloadJson`, `payloadHash`, `aggregateType`, `aggregateId` | `required`, `exact_length[36]`, `max_length`, `exact_length[64]` |
| `$ledger` | `blockNumber`, `merkleRoot`, `blockHash`, `blockSignature`, `signingKeyFingerprint`, `eventsJson` | `required`, `greater_than`, `exact_length[64]` |
| `$contact` | `contactType`, `firstName`, `legalName`, `attentionOf`, `emailPrimary`, `phone`, `taxIdEncrypted`, `country`, `ownerId` | `required`, `valid_email`, `max_length`, `valid_phone_e164`, `valid_tax_id`, `exact_length[2]` |
| `$notification` | `recipientEmail`, `notificationType`, `subject`, `status`, `priority` | `required`, `valid_email`, `max_length`, `in_list` |

### Custom Rules (`app/Validation/CustomRules.php`)

| Rule | Signature | Validation |
|------|-----------|------------|
| `valid_tax_id` | `valid_tax_id` | Spanish NIF (8 digits + letter), NIE (X/Y/Z + 7 digits + letter), CIF (letter + 7 digits + digit/letter) |
| `valid_phone_e164` | `valid_phone_e164` | E.164 phone number: `^\+?[1-9]\d{1,14}$` |
| `valid_hex` | `valid_hex[64]` | Hexadecimal string of exact length |
| `valid_uuid` | `valid_uuid` | UUID v4: `^[0-9a-f]{8}-...-4...-[89ab]...-[0-9a-f]{12}$` |

### RuleSets (loaded in order)

1. `CodeIgniter\Validation\StrictRules\Rules` — CI4 built-in rules
2. `CodeIgniter\Validation\StrictRules\FormatRules` — format validation
3. `CodeIgniter\Validation\StrictRules\FileRules` — file validation
4. `CodeIgniter\Validation\StrictRules\CreditCardRules` — credit card rules
5. `App\Validation\CustomRules` — MARAChain custom rules

---

## Filter Configuration

### `app/Config/Filters.php`

#### Registered Filters

| Alias | Class | Type |
|-------|-------|------|
| `csrf` | `CSRF::class` | Security |
| `toolbar` | `DebugToolbar::class` | Debug |
| `honeypot` | `Honeypot::class` | Security |
| `invalidchars` | `InvalidChars::class` | Security |
| `secureheaders` | `SecureHeaders::class` | Security (CI4 built-in) |
| `security` | `App\Filters\SecurityHeaders::class` | Security (MARAChain custom) |
| `cors` | `Cors::class` | Security |
| `forcehttps` | `ForceHTTPS::class` | Security |
| `pagecache` | `PageCache::class` | Performance |
| `performance` | `PerformanceMetrics::class` | Monitoring |

#### Global Filters

| Position | Filter | Description |
|----------|--------|-------------|
| `before` | `forcehttps` | Redirect HTTP → HTTPS (required) |
| `before` | `pagecache` | Web page caching (required) |
| `after` | `security` | **MARAChain security headers** — applied to all responses |
| `after` | `pagecache` | Web page caching (required) |
| `after` | `performance` | Performance metrics (required) |
| `after` | `toolbar` | Debug toolbar (development only) |

---

## Route Configuration

### `app/Config/Routes.php`

37 total routes defined:

| Group | Routes | Methods |
|-------|--------|---------|
| `/` | 1 | GET |
| `/users` | 6 | GET, POST, PUT, DELETE |
| `/devices` | 4 | GET, POST, DELETE |
| `/documents` | 5 | GET, POST, DELETE |
| `/transfers` | 6 | GET, POST |
| `/signatures` | 2 | GET, POST |
| `/evidence` | 2 | GET |
| `/ledger` | 3 | GET |
| `/contacts` | 5 | GET, POST, PUT, DELETE |
| `/notifications` | 2 | GET |

**Route group feature**: `users`, `devices`, `documents`, `transfers`, `signatures`

**Literal routes before wildcards**: `/transfers/sent`, `/transfers/received`, `/ledger/verify` are defined before `(:segment)` captures.

---

## PHPUnit Configuration

### `phpunit.xml.dist`

| Setting | Value | Description |
|---------|-------|-------------|
| `bootstrap` | `vendor/codeigniter4/framework/system/Test/bootstrap.php` | CI4 test bootstrap |
| `failOnRisky` | `true` | Fail on risky tests |
| `failOnWarning` | `true` | Fail on PHP warnings |
| `colors` | `true` | Colored output |
| `columns` | `max` | Full terminal width |

### Environment (PHPUnit)

```xml
<php>
    <env name="CI_ENVIRONMENT" value="testing"/>
    <env name="database.tests.DBDriver" value="SQLite3"/>
    <env name="database.tests.database" value=":memory:"/>
    <env name="database.tests.foreignKeys" value="true"/>
    <env name="database.tests.DBPrefix" value="db_"/>
</php>
```

### Test Suites

| Suite | Path | Description |
|-------|------|-------------|
| `unit` | `./tests/Unit` | Unit tests (models + controllers) |
| `app` | `./tests` | All tests |

### Coverage

| Report | Path |
|--------|------|
| Clover XML | `build/logs/clover.xml` |
| HTML | `build/logs/html/` |
| Text | `php://stdout` |

### Source Exclusions

```
./app/Views/           (presentation templates)
./app/Config/Routes.php (route definitions)
./app/Config/Boot/*.php (per-environment bootstrap)
```

---

## Logging

### `app/Config/Logger.php`

| Variable | Default | Description |
|----------|---------|-------------|
| `logger.threshold` | `4` | Log level (0=None, 4=All) |

```ini
# .env
logger.threshold = 4
```

---

## Email

### `app/Config/Email.php`

MARAChain uses email for notification delivery (SMTP). Configuration via `.env`:

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

## Feature Flags

Currently, all features are in pre-alpha state. Feature flags will be introduced in future releases for:

- SHIELD authentication
- IPFS integration
- FNMT certificate auth
- WebCrypto client encryption
- Blockchain anchoring (external DLT)

---

## Consistency Verification Checklist

- [ ] `.env` file exists and is not committed to git (in `.gitignore`)
- [ ] `encryption.key` is set to a 64-character hex string
- [ ] Database credentials are correct for the environment
- [ ] `CI_ENVIRONMENT` matches the deployment target
- [ ] `app.baseURL` matches the actual domain
- [ ] Security headers are enabled (`security` filter in `$globals['after']`)
- [ ] `$forceGlobalSecureRequests` is `true` in production
- [ ] `CI_DEBUG` is `false` in production
