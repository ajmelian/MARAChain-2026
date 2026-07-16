# Security Policy

> **Version:** 1.7.0 | **Last Updated:** 2026-07-16

MARAChain maneja documentos confidenciales, datos de identidad (NIF/NIE), y evidencias criptograficas. La seguridad es un requisito fundamental, no una caracteristica opcional.

---

## Supported Versions

| Version | Status | Security Support |
|---------|--------|-----------------|
| 1.7.0 (pre-alpha) | In Development | Not yet in production |
| 1.6.0 (pre-alpha) | EOL (superseded by 1.7.0) | Not released |
| 1.5.0 (pre-alpha) | EOL (superseded by 1.6.0) | Not released |

Once in production, only the latest `MAJOR.MINOR` release will receive security patches.

---

## Reporting a Vulnerability

**NO abras un issue publico para vulnerabilidades de seguridad.**

Reporta vulnerabilidades de forma privada:

1. **Email**: `security@marachain.example.com` (por configurar)
2. **Formato**: Incluye descripcion detallada, pasos para reproducir, e impacto estimado
3. **PGP Key**: Disponible bajo peticion (por configurar)
4. **Tiempo de respuesta**: Acuse de recibo en 48h, actualizacion cada 5 dias laborables

### Proceso de divulgacion

1. Recepcion y acuse de recibo (max 48h)
2. Triaje y evaluacion de severidad
3. Desarrollo del fix en rama privada `hotfix/X.Y.Z-description`
4. Backport a todas las versiones soportadas
5. Release coordinada con el reportero
6. Divulgacion publica 30 dias despues del fix

---

## Security Measures Implemented

### 1. Security Headers (OWASP Secure Headers Project)

Implementado via `App\Filters\SecurityHeaders` como filtro global `after`:

| Header | Value |
|--------|-------|
| `X-Content-Type-Options` | `nosniff` |
| `X-Frame-Options` | `DENY` |
| `X-XSS-Protection` | `1; mode=block` |
| `Referrer-Policy` | `strict-origin-when-cross-origin` |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` |
| `Content-Security-Policy` | `default-src 'self'` |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=()` |

### 2. Input Validation (Defense in Depth)

Validation en backend (CI4 rules) y frontend (JS) identica para todos los inputs:

| Campo | Validacion | Frontend Regex |
|-------|-----------|----------------|
| Email | `valid_email` + `max_length[254]` | `^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$` |
| Phone | `valid_phone_e164` (CustomRules) | `^\+?[1-9]\d{1,14}$` |
| Tax ID (NIF/NIE/CIF) | `valid_tax_id` (CustomRules) | NIF/NIE/CIF regex patterns |
| UUID v4 | `valid_uuid` (CustomRules) | `^[0-9a-f]{8}-...-4...-[89ab]...-[0-9a-f]{12}$` |
| Hex (SHA-256) | `exact_length[64]` | 64-character hex |

**Validation groups** definidos en `app/Config/Validation.php` para cada entidad (9 grupos) — Fase 4 completada.

### 3. Query Builder (Prevencion de SQL Injection)

Todo acceso a base de datos usa **CI4 Query Builder**, nunca raw SQL concatenado:

```php
// Correcto — Query Builder con prepared statements
$this->where('email', $email)->first();

// Prohibido — raw SQL con concatenacion
// $this->db->query("SELECT * FROM users WHERE email = '$email'");
```

### 4. UUID v4 for IDs

- Todas las PKs son `CHAR(36)` UUID v4 generados en PHP (`random_bytes(16)`)
- Previene enumeracion de IDs (no secuenciales)
- `$useAutoIncrement = false` en todos los modelos

### 5. Encrypted Tax ID (NIF/NIE)

- **Cifrado AEAD** (`tax_id_encrypted`): valor cifrado con autenticacion
- **HMAC determinista** (`tax_id_hmac`): busqueda sin descifrar

### 6. TOTP Support

- Secrets TOTP cifrados en reposo via AES-256-GCM
- Bloqueo progresivo tras 5 fallos consecutivos (30 minutos)
- Contador atomico: `SET col = col + 1` (anti-TOCTOU)

### 7. Session Security

- Sesiones gestionadas via SHIELD (`CodeIgniter\Shield\Filters\SessionAuth`)
- Rotacion de sesion y proteccion CSRF integradas
- Cookie `HttpOnly` y `Secure` en produccion
- `forcehttps` filter redirige HTTP → HTTPS

### 8. Rate Limiting

- **auth group**: 6 req/min (login, register, TOTP verify)
- **api group**: 60 req/min (REST endpoints)
- Fingerprint via SHA1(IP address + request path)
- Retorna HTTP 429 con header `retry_after`

### 9. HTTPS Enforcement

- `forcehttps` filtro global `before`
- HSTS header con `max-age=31536000; includeSubDomains`

### 10. API Authentication Filter (`api-auth`)

- Filtro aplicado a TODAS las rutas API REST
- Requiere sesion SHIELD activa con permisos de grupo
- Rutas publicas: `/health` (health check)

### 11. FNMT TOTP Rate Limiting

- `throttle:auth` aplicado a rutas TOTP FNMT (POST)
- Limite de 6 req/min para prevenir brute-force de codigos TOTP

### 12. Ciphertext Envelope Validation (StorageService)

- Validacion estricta del formato `marachain-envelope v1`: `{version, algorithm, iv, ciphertext, tag}`
- Verificacion de integridad AEAD (tag) antes de almacenar ciphertext en BD
- La DEK (Data Encryption Key) nunca se almacena en el backend

### 13. Nginx mTLS Configuration

- `ssl_verify_client optional` a nivel global
- `ssl_verify_client` obligatorio en location `/auth/fnmt`
- `ssl_verify_depth 4` para validar cadena de certificacion FNMT completa

### 14. Atomic Database Operations

- `incrementoTotpFailures()` y `incrementAttemptCount()` usan `SET col = col + 1` atomico
- `sealBlock()` envuelto en transaccion BD con rollback en fallo
- State transitions con guarda atomica `->where('status', $row['status'])`

---

## OWASP Top 10 Compliance

| # | Riesgo | Medida Implementada | Estado |
|---|--------|---------------------|--------|
| A01 | Broken Access Control | SHIELD session-based auth; rutas web `session` filter; rutas API `api-auth` filter | ✅ Implementado |
| A02 | Cryptographic Failures | AES-256-GCM para NIF y TOTP, encryption.key en .env, sin hardcoding | ✅ Implementado |
| A03 | Injection | Query Builder (prepared statements), validacion regex identica front/back, sin raw SQL | ✅ Implementado |
| A04 | Insecure Design | SDD con OpenSpec, threat modeling (STRIDE/LINDDUN), ADR documentados, auditoria de seguridad | ✅ Implementado |
| A05 | Security Misconfiguration | SecurityHeaders global, forcehttps, CSP, Throttle rate limiting, sin debug en prod | ✅ Implementado |
| A06 | Vulnerable Components | `composer audit`, `composer.lock`, dependencias minimas auditadas | ⚠️ Sin CI |
| A07 | Auth Failures | SHIELD + TOTP con bloqueo atomico (5 fallos), rate limiting en login/register, UUID no enumerable | ✅ Implementado |
| A08 | Software & Data Integrity | SHA-256 hashes, Merkle tree en ledger, verificacion de cadena, transacciones atomicas | ✅ Implementado |
| A09 | Logging & Monitoring | CI4 Logger, evidencias append-only con `EVIDENCE_LOST` logging, health endpoint, sin PII en logs | ✅ Implementado |
| A10 | SSRF | N/A en MVP (sin fetch remoto); validacion futura de webhooks | N/A |

### OWASP ASVS Target

- **Nivel 2** como objetivo general
- **Nivel 3** para modulos criticos: Identity, Cryptography, Evidence, Administration

---

## Dependency Scanning

```bash
# Auditoria de dependencias PHP
composer audit
```

### Dependencias principales

| Paquete | Version | Auditoria |
|---------|---------|-----------|
| `codeigniter4/framework` | `^4.7` | Comunidad activa |
| `codeigniter4/shield` | `^1.3` | Auth oficial CI4 |
| `codeigniter4/settings` | `^2.3` | Settings oficial CI4 |
| `phpunit/phpunit` | `^10.5.16` | Dev only |
| `fakerphp/faker` | `^1.9` | Dev only |
| `mikey179/vfsstream` | `^1.6` | Dev only |

---

## Secret Management

**NUNCA hardcodear secretos en el codigo fuente.**

| Secreto | Ubicacion | Formato |
|---------|-----------|---------|
| Database password | `.env` → `database.default.password` | Plain text |
| Encryption key | `.env` → `encryption.key` | Hex (64 chars) |
| HMAC key | `.env` → `encryption.hmacKey` | Hex (64 chars) |
| TOTP secrets | `users.totp_secret_encrypted` (DB) | AEAD ciphertext |
| Tax ID (NIF/NIE) | `users.tax_id_encrypted` (DB) | AEAD ciphertext |
| Notification secrets | `/var/lib/marachain/integrations/` | Fuera de wwwroot |

**`.env` esta en `.gitignore`** — NUNCA se commitea.

---

## Security Checklist (Pre-Merge)

Antes de cada merge a `develop`:

- [ ] `composer audit` sin vulnerabilidades criticas/altas
- [ ] Tests de seguridad incluidos en la feature
- [ ] Validacion de inputs en backend (CI4 rules) y frontend (JS)
- [ ] Sin raw SQL ni concatenacion
- [ ] Sin `var_dump()`, `die()`, `echo` de datos sensibles
- [ ] Sin `@` error suppression en operaciones criticas
- [ ] Sin secretos en el diff (`git diff --cached | grep -i password`)
- [ ] Security headers activos en todas las respuestas
- [ ] Rate limiting aplicado en rutas publicas
- [ ] UUID v4 generado via `random_bytes()` (no `uniqid()`)
- [ ] Transiciones de estado con guarda atomica
- [ ] Operaciones multi-tabla en transaccion BD
- [ ] Code review con checklist OWASP completada
- [ ] Commit message sigue Conventional Commits

---

## Threat Model

### STRIDE Analysis

| Threat | Module | Mitigation |
|--------|--------|------------|
| **S**poofing | Authentication | SHIELD sessions + FNMT cert mTLS + TOTP 2FA |
| **T**ampering | Documents | SHA-256 hash + Merkle tree + ledger append-only + envelope AEAD validation |
| **R**epudiation | Evidence | Ledger append-only con firma; transacciones BD atomicas; EvidenceService append-only |
| **I**nfo Disclosure | Encryption | AES-256-GCM (NIF, TOTP, ciphertext), WebCrypto E2E, only-4-your-eyes, DEK never on backend |
| **D**oS | Transfers | Throttle rate limiting (auth: 6/min, api: 60/min) |
| **E**levation | Administration | SHIELD grupos de permisos; auditoria de operaciones privilegiadas |

### LINDDUN (Privacy)

| Threat | Mitigation |
|--------|------------|
| **L**inkability | UUID v4 no correlacionable entre contextos |
| **I**dentifiability | NIF cifrado, busqueda solo via HMAC |
| **N**on-repudiation | Ledger firmado, evidencias canonicalizadas |
| **D**etectability | Acceso a documentos solo via ACL de transferencia |
| **D**isclosure | Cifrado E2E, sin clave maestra, DEK solo en cliente, envelope con AEAD |
| **U**nawareness | Notificaciones de transferencias y accesos; evidencias de negocio via EvidenceService |
| **N**on-compliance | Arquitectura documentada, ADR, audit trail |
