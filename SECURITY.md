# Security Policy

> **Version:** 1.4.0 | **Last Updated:** 2026-07-14

MARAChain maneja documentos confidenciales, datos de identidad (NIF/NIE), y evidencias criptograficas. La seguridad es un requisito fundamental, no una caracteristica opcional.

---

## Supported Versions

| Version | Status | Security Support |
|---------|--------|-----------------|
| 1.4.0 (pre-alpha) | In Development | Not yet in production |
| 1.2.1 (pre-alpha) | EOL (superseded by 1.4.0) | Not released |
| 1.2.0 (pre-alpha) | EOL (superseded by 1.2.1) | Not released |
| 1.1.1 (pre-alpha) | EOL (superseded) | Not released |
| 1.0.0 (initial) | Archived | Not supported |

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

| Header | Value | OWASP Reference |
|--------|-------|-----------------|
| `X-Content-Type-Options` | `nosniff` | [X-Content-Type-Options](https://owasp.org/www-project-secure-headers/#x-content-type-options) |
| `X-Frame-Options` | `DENY` | [X-Frame-Options](https://owasp.org/www-project-secure-headers/#x-frame-options) |
| `X-XSS-Protection` | `1; mode=block` | [X-XSS-Protection](https://owasp.org/www-project-secure-headers/#x-xss-protection) |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | [Referrer-Policy](https://owasp.org/www-project-secure-headers/#referrer-policy) |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` | [HSTS](https://owasp.org/www-project-secure-headers/#strict-transport-security) |
| `Content-Security-Policy` | `default-src 'self'` | [CSP](https://owasp.org/www-project-secure-headers/#content-security-policy) |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=()` | [Permissions-Policy](https://owasp.org/www-project-secure-headers/#permissions-policy) |

### 2. Input Validation (Defense in Depth)

Validation en backend (CI4 rules) y frontend (JS) identica para todos los inputs:

| Campo | Validacion | Frontend Regex |
|-------|-----------|----------------|
| Email | `valid_email` + `max_length[254]` | `^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$` |
| Phone | `valid_phone_e164` (CustomRules) | `^\+?[1-9]\d{1,14}$` |
| Tax ID (NIF/NIE/CIF) | `valid_tax_id` (CustomRules) | NIF/NIE/CIF regex patterns |
| UUID v4 | `valid_uuid` (CustomRules) | `^[0-9a-f]{8}-...-4...-[89ab]...-[0-9a-f]{12}$` |
| Hex (SHA-256) | `exact_length[64]` | 64-character hex |
| MIME type | `in_list[application/pdf]` | Solo PDF en MVP |

**Validation groups** definidos en `app/Config/Validation.php` para cada entidad (9 grupos).

### 3. Query Builder (Prevencion de SQL Injection)

Todo acceso a base de datos usa **CI4 Query Builder**, nunca raw SQL concatenado:

```php
// Correcto — Query Builder con prepared statements
$this->where('email', $email)->first();
$this->db->table($this->table)->where('id', $id)->get()->getRowArray();

// Prohibido — raw SQL con concatenacion
// $this->db->query("SELECT * FROM users WHERE email = '$email'");
```

### 4. UUID v4 for IDs

- Todas las PKs son `CHAR(36)` UUID v4 generados en PHP (`random_bytes(16)`)
- Previene enumeracion de IDs (no secuenciales)
- `$useAutoIncrement = false` en todos los modelos
- Compatible con entornos distribuidos futuros

### 5. Prepared Statements

CI4 Query Builder genera automaticamente prepared statements parametrizados:

```php
// Internamente: INSERT INTO users (id, email, ...) VALUES (?, ?, ...)
$this->insert($row);
```

### 6. Encrypted Tax ID (NIF/NIE)

El NIF/NIE se almacena con cifrado de doble capa:

- **Cifrado AEAD** (`tax_id_encrypted`): valor cifrado con autenticacion
- **HMAC determinista** (`tax_id_hmac`): busqueda sin descifrar
  - SHA-256 HMAC con clave separada (64 caracteres hex)
  - Busqueda por `findByTaxIdHmac($hmac)` sin exponer el valor real

### 7. TOTP Support

- Secrets TOTP cifrados en reposo (`totp_secret_encrypted`)
- Bloqueo progresivo tras 5 fallos consecutivos (30 minutos)
- Contador de fallos con reset automatico en exito
- Estado `blocked` en la entidad User

### 8. Session Security

- Sesiones gestionadas via SHIELD (`CodeIgniter\Shield\Filters\SessionAuth`)
- Rotacion de sesion y proteccion CSRF integradas en SHIELD
- Cookie `HttpOnly` y `Secure` en produccion
- `forcehttps` filter redirige HTTP → HTTPS

### 9. Rate Limiting

- Implementado via `App\Filters\Throttle` — token bucket basado en archivos
- **auth group**: 6 req/min (login, register, TOTP verify)
- **api group**: 60 req/min (REST endpoints)
- Fingerprint via SHA1(IP address + request path)
- Retorna HTTP 429 con header `retry_after`
- Sin dependencia externa (Redis/memcached) para MVP

### 10. HTTPS Enforcement

- `forcehttps` filtro global `before`
- HSTS header con `max-age=31536000; includeSubDomains`
- `$forceGlobalSecureRequests` configurable via `.env`

### 12. Ciphertext Envelope Validation (StorageService)

- Validacion estricta del formato `marachain-envelope v1`: `{version, algorithm, iv, ciphertext, tag}`
- Verificacion de integridad AEAD (tag) antes de almacenar ciphertext en BD
- Rechazo de envelopes con versiones no soportadas o algoritmos no permitidos
- La DEK (Data Encryption Key) nunca se almacena en el backend

### 13. Nginx mTLS Configuration

- `ssl_verify_client optional` a nivel global (permite acceso anonimo y certificado)
- `ssl_verify_client` obligatorio en location `/auth/fnmt` (requiere certificado FNMT valido)
- Cabeceras `SSL_CLIENT_*` pasadas a PHP-FPM via `fastcgi_param`
- `ssl_verify_depth 4` para validar cadena de certificacion FNMT completa
- Configuracion documentada en `nginx-fnmt-mtls.conf`

### 14. Atomic Database Operations

- `incrementoTotpFailures()` y `incrementAttemptCount()` usan `SET col = col + 1` atomico (evita TOCTOU)
- `sealBlock()` envuelto en transaccion BD con rollback en fallo
- State transitions con guarda atomica `->where('status', $row['status'])`

---

## OWASP Top 10 Compliance

| # | Riesgo | Medida Implementada | Estado |
|---|--------|---------------------|--------|
| A01 | Broken Access Control | SHIELD session-based auth con grupos de permisos; rutas web protegidas con `session` filter | ✅ Implementado |
| A02 | Cryptographic Failures | AES-256-GCM para NIF y TOTP, encryption.key en .env, sin hardcoding, HMAC para busqueda | ✅ Implementado |
| A03 | Injection | Query Builder (prepared statements), validacion regex identica front/back, sin raw SQL | ✅ Implementado |
| A04 | Insecure Design | SDD con OpenSpec, threat modeling (STRIDE/LINDDUN), ADR documentados, auditoria de seguridad (v1.2.1) | ✅ Implementado |
| A05 | Security Misconfiguration | SecurityHeaders global, forcehttps, CSP, Throttle rate limiting, sin debug en prod | ✅ Implementado |
| A06 | Vulnerable Components | `composer audit`, `composer.lock`, dependencias minimas auditadas, CI/CD con auditoria | ⚠️ Sin CI |
| A07 | Auth Failures | SHIELD + TOTP con bloqueo atomico (5 fallos), rate limiting en login/register, UUID no enumerable | ✅ Implementado |
| A08 | Software & Data Integrity | SHA-256 hashes para documentos, Merkle tree en ledger, verificacion de cadena, transacciones atomicas | ✅ Implementado |
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

# En CI/CD (GitLab CI / GitHub Actions)
# composer audit --format=json > audit.json
# Exit code 1 si hay vulnerabilidades criticas o altas
```

### Dependencias principales

| Paquete | Version | Auditoria |
|---------|---------|-----------|
| `codeigniter4/framework` | `^4.7` | Comunidad activa, releases frecuentes |
| `codeigniter4/shield` | `^1.3` | Auth oficial CI4, mantenido por BCIT |
| `codeigniter4/settings` | `^2.3` | Settings oficial CI4, mantenido por BCIT |
| `phpunit/phpunit` | `^10.5.16` | Dev only, no incluido en produccion |
| `fakerphp/faker` | `^1.9` | Dev only, para seeds de prueba |
| `mikey179/vfsstream` | `^1.6` | Dev only, mock de filesystem |

---

## Secret Management

**NUNCA hardcodear secretos en el codigo fuente.**

| Secreto | Ubicacion | Formato |
|---------|-----------|---------|
| Database password | `.env` → `database.default.password` | Plain text |
| Encryption key | `.env` → `encryption.key` | Hex (64 chars) |
| TOTP secrets | `users.totp_secret_encrypted` (DB) | AEAD ciphertext |
| Tax ID (NIF/NIE) | `users.tax_id_encrypted` (DB) | AEAD ciphertext |
| API keys (futuro) | `.env` o vault externo | Por definir |

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
