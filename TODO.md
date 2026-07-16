# MARAChain — Tareas Pendientes para Versión Comercial v1.0.0

**Última actualización**: 2026-07-14  
**Versión actual del código**: v1.5.0-alpha  
**Compliance Score**: 52/100 (+10 desde última auditoría)

---

## P0 — Bloqueantes (debe resolverse antes de cualquier despliegue)

| # | Área | Descripción | Archivo | Esfuerzo | Estado |
|---|------|-------------|---------|:--------:|:------:|
| P0-1 | 🔐 SECURITY | ~~`encryption.hmacKey` vacío en `.env`~~ | `.env:23` | S | ✅ Resuelto |
| P0-2 | 🔐 SECURITY | ~~`encryption.key` no configurado en `.env`~~ | `.env` | S | ✅ Resuelto |
| P0-3 | 🔒 ENCRYPTION | ~~Hash mismatch en upload pipeline~~ | `dropzone-init.js:85` vs `StorageService.php` | M | ✅ Resuelto |
| P0-4 | 🔒 ENCRYPTION | ~~DEK perdida — nunca se enviaba al servidor~~ | `marachain-crypto.js:125-140` | L | ✅ Resuelto |
| P0-5 | 🔐 SECURITY | ~~Rutas API sin autenticación~~ | `app/Config/Routes.php:54-119` | M | ✅ Resuelto |
| P0-6 | 🔐 SECURITY | **Credenciales en texto plano** en `.env`: MySQL password, SMTP user/password visibles en el filesystem del servidor. | `.env` | S | ⚠️ Parcial (gitignored, no accesible via web, pero visible en filesystem) |

---

## P1 — Crítico (este sprint)

| # | Área | Descripción | Archivo | Esfuerzo |
|---|------|-------------|---------|:--------:|
| P1-1 | 🔑 AUTH | No se envía email de notificación tras login exitoso FNMT (exigido por CU-AUTH-002). | `FnmtController.php:297` | S |
| P1-2 | 🔑 AUTH | No hay prevención de reutilización TOTP. Un código puede usarse múltiples veces dentro de la ventana temporal de ±1. | `FnmtController.php:verifyTotp()` | M |
| P1-3 | 🔑 AUTH | Configuración de sesión SHIELD no coincide con el spec: timeout 2h en lugar de 8h/30min inactividad/reauth a los 5min para operaciones críticas. Sin límite de sesiones activas (spec: 5). | `app/Config/Auth.php` | M |
| P1-4 | 📄 DOCUMENTS | `TransfersController::detail()` usa datos mock hardcodeados. Una transferencia real creada vía API nunca se muestra en la vista web de detalle. | `TransfersController.php:162-179` | S |
| P1-5 | 📄 DOCUMENTS | No hay vista web para aceptar/rechazar transferencias. Las rutas API `POST /transfers/{id}/accept` y `reject` existen, pero el usuario no puede ejecutarlas desde la UI. | Vistas + controlador web | M |
| P1-6 | 🔒 ENCRYPTION | ~~No existe función `decryptDocument()` en el cliente JS~~ | `marachain-crypto.js` | L | ✅ Resuelto |
| P1-7 | 📬 NOTIFICATIONS | Outbox **no es transaccional**: el worker lee de `notification_requested` directamente. La inserción de la notificación no es atómica con la operación de negocio (creación de transferencia). Falta `NotificationRequestedModel`. | `TransferController::create()` + `NotificationsCommand.php` | L |
| P1-8 | 🖥️ INFRA | **IPFS worker no implementado**. `StorageService` almacena ciphertext en MySQL, no en IPFS. El spec exige IPFS privado con 3 nodos y reconciliación. | Nuevo `Commands/IpfsReconcile.php` | XL |
| P1-9 | 🔐 SECURITY | `DBDebug = true` — en producción expone errores de BD con queries SQL al frontend. | `app/Config/Database.php:36` | S |
| P1-10 | 🔐 SECURITY | Ruta `POST /users/:segment/totp` permite habilitar TOTP a cualquier usuario sin verificar propiedad del recurso. | `UserController::enableTotp()` | S |

---

## P2 — Importante (próximo sprint)

| # | Área | Descripción | Archivo | Esfuerzo |
|---|------|-------------|---------|:--------:|
| P2-1 | 🔐 SECURITY | CSP con `style-src 'unsafe-inline'`. El spec exige evitar `unsafe-inline`. | `SecurityHeaders.php:58` | M |
| P2-2 | 🔐 SECURITY | Faltan headers de seguridad: `X-Download-Options`, `Cross-Origin-Opener-Policy`, `Cross-Origin-Embedder-Policy`. | `SecurityHeaders.php` | S |
| P2-3 | 🔐 SECURITY | Rutas API excluidas de CSRF sin autenticación alternativa. Sin auth en API, la exclusión CSRF es un agujero. | `app/Config/Filters.php:82-104` | L |
| P2-4 | 🔑 AUTH | Sin rate limiting en `GET /auth/fnmt`. Solo los POST de TOTP tienen throttle. | `Routes.php` | S |
| P2-5 | 📒 LEDGER | `placeholderSign()` usa HMAC-SHA256 en lugar de Ed25519. En producción esto invalida la autenticidad criptográfica del ledger. | `LedgerService.php:422-425` | L |
| P2-6 | 📒 LEDGER | No hay generación de **Merkle proofs** (pruebas de inclusión). Un tercero no puede verificar que un evento está en un bloque sin descargar el ledger completo. | `LedgerService.php` | M |
| P2-7 | 📒 LEDGER | `sealBlock()` nunca se invoca desde lógica de negocio. Solo existe comando CLI manual. Las evidencias se crean pero nunca se sellan automáticamente. | `EvidenceService.php` + cron | M |
| P2-8 | 📬 NOTIFICATIONS | `EmailNotificationProvider` usa `mail()` nativo en lugar del SMTP configurado en `.env`. Las credenciales SMTP están en `.env` pero no se usan. | `EmailNotificationProvider.php:53` | M |
| P2-9 | 🖥️ INFRA | No hay worker de cola (`queue:work`) como exige el spec. Operaciones asíncronas (IPFS, notificaciones, ledger) sin orquestación. | Nuevo comando CLI | L |
| P2-10 | 🖥️ INFRA | Sin archivos `systemd` para workers. Sin restart automático tras crash. | `/etc/systemd/system/` | S |
| P2-11 | 🧪 TESTING | **Cero tests E2E**. No hay `tests/E2E/` ni `tests/Integration/`. | Nuevos archivos de test | XL |
| P2-12 | 🧪 TESTING | **Cero tests de flujo FNMT**. `FnmtController`, `X509Service`, `FnmtIdentityProvider` sin cobertura. | Nuevos tests | L |
| P2-13 | 🧪 TESTING | **Cero tests de cifrado/descifrado**. `EncryptionService` y `MARACrypto` sin vectores de prueba. | Nuevos tests | M |
| P2-14 | 🧪 TESTING | Solo 1 archivo de test de servicios (`LedgerServiceTest`). Faltan tests para `StorageService`, `EncryptionService`, `EvidenceService`, `X509Service`, `FnmtIdentityProvider`. | Nuevos tests | L |
| P2-15 | 🖥️ INFRA | CI usa PHP 8.3. El spec exige PHP 8.5. Las features de PHP 8.5 no se validan. | `.gitlab-ci.yml:16` | S |
| P2-16 | 🖥️ INFRA | Sin SonarQube. PHPStan level 2 (muy bajo). El spec exige SAST con Quality Gate: 0 bugs, 0 vulnerabilities. | `.gitlab-ci.yml` + `sonar-project.properties` | M |

---

## P3 — Mejora (backlog)

| # | Área | Descripción | Archivo | Esfuerzo |
|---|------|-------------|---------|:--------:|
| P3-1 | 📖 DOCS | Numeración de secciones en `docs/01_RESUMEN_COMPLETO.md` salta de 15 a 17 (falta sección 16). | `docs/01_RESUMEN_COMPLETO.md:416-418` | S |
| P3-2 | 🔑 AUTH | `AuthController::totpSetup()` hardcodeado a `totpEnabled = false` y `qrCodeUrl = ''`. El QR real solo se genera en FnmtController. | `AuthController.php:286-291` | S |
| P3-3 | 📬 NOTIFICATIONS | WhatsApp, Telegram y SMS son stubs. Correcto según spec (PoC pendiente). No actionable. | 3 archivos stub | — |
| P3-4 | 🎨 FRONTEND | Código postal sin `inputmode="numeric"` ni `maxlength="5"`. | `transfers/create.php` | S |
| P3-5 | 🎨 FRONTEND | Login sin `autocomplete="off"` en campos sensibles. | `login.php` | S |
| P3-6 | 🖥️ INFRA | PHPUnit con `--no-coverage`. Sin reporte para SonarQube. | `.gitlab-ci.yml:24` | S |
| P3-7 | 📖 DOCS | Sin directorio `docs/adr/` con ADRs documentados individualmente (exigido por `04_ARCHITECTURE.md:533-553`). | `docs/adr/` | L |
| P3-8 | 🎨 FRONTEND | Validación regex frontend no completamente sincronizada con backend en todos los campos. | `marachain-validation.js` | M |

---

## Resumen de Cumplimiento por Área

| Área | Estado | P0 | P1 | P2 | P3 |
|------|:------:|:--:|:--:|:--:|:--:|
| **AUTH** | ⚠️ Parcial | 0 | 3 | 1 | 1 |
| **DOCUMENTS** | ⚠️ Parcial | — | 2 | — | 1 |
| **ENCRYPTION** | ✅ Corregido | 0 | 0 | — | — |
| **LEDGER** | ✅ Funcional | — | — | 3 | — |
| **NOTIFICATIONS** | ⚠️ Parcial | — | 1 | 1 | 1 |
| **FRONTEND** | ⚠️ Parcial | — | — | — | 3 |
| **INFRA** | 🔴 Incompleto | — | 1 | 4 | 1 |
| **TESTING** | ⚠️ Básico | — | — | 4 | — |
| **SECURITY** | ⚠️ Mejorado | 1 | 2 | 3 | — |

| Prioridad | Count | Resueltos | Pendientes |
|-----------|:-----:|:---------:|:----------:|
| **P0** — Bloqueante | 6 | 5 | 1 |
| **P1** — Crítico | 10 | 1 | 9 |
| **P2** — Importante | 16 | 0 | 16 |
| **P3** — Mejora | 8 | 0 | 8 |
| **Total** | **40** | **6** | **34** |

---

## Orden de ejecución recomendado

```
✅ Semana 1-2:   P0-1, P0-2, P0-5 (security + config)
✅ Semana 3:     P0-3, P0-4, P1-6 (fix encryption pipeline + decrypt)
   Semana 4-5:   P1-1, P1-2, P1-3, P1-10 (auth hardening)
   Semana 6-7:   P1-4, P1-5 (document transfer complete flow)
   Semana 8-9:   P1-7, P2-7, P2-8 (notifications transactional + SMTP)
   Semana 10-11: P2-1, P2-2, P2-3, P2-4 (security headers + auth routes)
   Semana 12-13: P2-5, P2-6 (Ed25519 signatures + Merkle proofs)
   Semana 14:    P2-9, P2-10, P2-15, P2-16 (CI/CD + workers)
   Semana 15-16: P2-11, P2-12, P2-13, P2-14 (testing coverage)
   Backlog:      P3-1 a P3-8
```

### Commits de la sesión anterior

| Commit | Descripción |
|--------|-------------|
| `625c4c8` | P0-1/2/3/4/5 + P1-6: encryption keys, hash fix, DEK envelope, ApiAuth filter, decryptDocument() |

---

## Notas

- **Estado actual**: ~220 tests unitarios, ~500 assertions. Nuevos tests de servicios (5) y controladores web (5) en v1.6.0.
- **Base de datos**: 16 migraciones, 21 tablas (MySQL development, SQLite testing).
- **Servicios**: 10 interfaces/servicios implementados. 3 son stubs (WhatsApp, Telegram, SMS).
- **Deuda técnica conocida**: `freshEntity()` bypassing CI4 entity cache. `uuidV4()` ya centralizado en helper.
- **Deuda de auditoría anterior**: 14 observaciones especulativas pendientes documentadas en `AUDIT_REPORT.md`.
