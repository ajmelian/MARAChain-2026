# MARAChain — Tareas Pendientes para Version Comercial v1.0.0

**Ultima actualizacion**: 2026-07-16
**Version actual del codigo**: v1.8.0-alpha
**OpenSpec Progress**: 65/66 tareas completadas (98%)
**Compliance Score**: 67/100

---

## P0 — Bloqueantes (debe resolverse antes de cualquier despliegue)

| # | Area | Descripcion | Archivo | Esfuerzo | Estado |
|---|------|-------------|---------|:--------:|:------:|
| P0-1 | SECURITY | ~~`encryption.hmacKey` vacio en `.env`~~ | `.env:23` | S | ✅ Resuelto |
| P0-2 | SECURITY | ~~`encryption.key` no configurado en `.env`~~ | `.env` | S | ✅ Resuelto |
| P0-3 | ENCRYPTION | ~~Hash mismatch en upload pipeline~~ | `dropzone-init.js:85` vs `StorageService.php` | M | ✅ Resuelto |
| P0-4 | ENCRYPTION | ~~DEK perdida — nunca se enviaba al servidor~~ | `marachain-crypto.js:125-140` | L | ✅ Resuelto |
| P0-5 | SECURITY | ~~Rutas API sin autenticacion~~ | `app/Config/Routes.php:54-119` | M | ✅ Resuelto |
| P0-6 | SECURITY | **Credenciales en texto plano** en `.env`: MySQL password, SMTP user/password visibles en el filesystem del servidor. | `.env` | S | ⚠️ Parcial (gitignored, no accesible via web, pero visible en filesystem) |

---

## P1 — Critico (este sprint)

| # | Area | Descripcion | Archivo | Esfuerzo |
|---|------|-------------|---------|:--------:|
| P1-1 | AUTH | No se envia email de notificacion tras login exitoso FNMT (exigido por CU-AUTH-002). | `FnmtController.php:297` | S |
| P1-2 | AUTH | No hay prevencion de reutilizacion TOTP. Un codigo puede usarse multiples veces dentro de la ventana temporal de ±1. | `FnmtController.php:verifyTotp()` | M |
| P1-3 | AUTH | Configuracion de sesion SHIELD no coincide con el spec: timeout 2h en lugar de 8h/30min inactividad/reauth a los 5min para operaciones criticas. Sin limite de sesiones activas (spec: 5). | `app/Config/Auth.php` | M |
| P1-4 | DOCUMENTS | `TransfersController::detail()` usa datos mock hardcodeados. Una transferencia real creada via API nunca se muestra en la vista web de detalle. | `TransfersController.php:162-179` | S |
| P1-5 | DOCUMENTS | No hay vista web para aceptar/rechazar transferencias. Las rutas API `POST /transfers/{id}/accept` y `reject` existen, pero el usuario no puede ejecutarlas desde la UI. | Vistas + controlador web | M |
| P1-6 | ENCRYPTION | ~~No existe funcion `decryptDocument()` en el cliente JS~~ | `marachain-crypto.js` | L | ✅ Resuelto |
| P1-7 | NOTIFICATIONS | Outbox **no es transaccional**: el worker lee de `notification_requested` directamente. La insercion de la notificacion no es atomica con la operacion de negocio (creacion de transferencia). Falta integracion via `NotificationRequestedModel`. | `TransferController::create()` + `NotificationsCommand.php` | L |
| P1-8 | INFRA | ~~**IPFS worker no implementado**. `StorageService` almacena ciphertext en MySQL, no en IPFS. El spec exige IPFS privado con 3 nodos y reconciliacion.~~ | Nuevo `Commands/IpfsReconcile.php` | XL | ✅ Resuelto (IP-1 a IP-6) |
| P1-9 | SECURITY | `DBDebug = true` — en produccion expone errores de BD con queries SQL al frontend. | `app/Config/Database.php:36` | S |
| P1-10 | SECURITY | Ruta `POST /users/:segment/totp` permite habilitar TOTP a cualquier usuario sin verificar propiedad del recurso. | `UserController::enableTotp()` | S |

---

## P2 — Importante (proximo sprint)

| # | Area | Descripcion | Archivo | Esfuerzo |
|---|------|-------------|---------|:--------:|
| P2-1 | SECURITY | CSP con `style-src 'unsafe-inline'`. El spec exige evitar `unsafe-inline`. | `SecurityHeaders.php:58` | M |
| P2-2 | SECURITY | Faltan headers de seguridad: `X-Download-Options`, `Cross-Origin-Opener-Policy`, `Cross-Origin-Embedder-Policy`. | `SecurityHeaders.php` | S |
| P2-3 | SECURITY | Rutas API excluidas de CSRF sin autenticacion alternativa. Sin auth en API, la exclusion CSRF es un agujero. | `app/Config/Filters.php:82-104` | L |
| P2-4 | AUTH | ~~Sin rate limiting en `GET /auth/fnmt`. Solo los POST de TOTP tienen throttle.~~ | `Routes.php` | S | ✅ Resuelto (S-6) |
| P2-5 | LEDGER | `placeholderSign()` usa HMAC-SHA256 en lugar de Ed25519. En produccion esto invalida la autenticidad criptografica del ledger. | `LedgerService.php:422-425` | L |
| P2-6 | LEDGER | ~~No hay generacion de **Merkle proofs** (pruebas de inclusion). Un tercero no puede verificar que un evento esta en un bloque sin descargar el ledger completo.~~ | `LedgerService.php` | M | ✅ Resuelto (S-2) |
| P2-7 | LEDGER | ~~`sealBlock()` nunca se invoca desde logica de negocio. Solo existe comando CLI manual. Las evidencias se crean pero nunca se sellan automaticamente.~~ | `EvidenceService.php` + cron | M | ✅ Resuelto (I-4: systemd timer cada 15 min) |
| P2-8 | NOTIFICATIONS | `EmailNotificationProvider` usa `mail()` nativo en lugar del SMTP configurado en `.env`. Las credenciales SMTP estan en `.env` pero no se usan. | `EmailNotificationProvider.php:53` | M |
| P2-9 | INFRA | ~~No hay worker de cola (`queue:work`) como exige el spec. Operaciones asincronas (IPFS, notificaciones, ledger) sin orquestacion.~~ | Nuevo comando CLI | L | ✅ Parcial (I-4: systemd workers, falta queue:work generico) |
| P2-10 | INFRA | ~~Sin archivos `systemd` para workers. Sin restart automatico tras crash.~~ | `/etc/systemd/system/` | S | ✅ Resuelto (I-4: 3 services + 3 timers) |
| P2-11 | TESTING | **Cero tests E2E**. No hay `tests/E2E/` ni `tests/Integration/`. | Nuevos archivos de test | XL |
| P2-12 | TESTING | **Cero tests de flujo FNMT**. `FnmtController`, `X509Service`, `FnmtIdentityProvider` sin cobertura. | Nuevos tests | L |
| P2-13 | TESTING | **Cero tests de cifrado/descifrado**. `EncryptionService` y `MARACrypto` sin vectores de prueba. | Nuevos tests | M |
| P2-14 | TESTING | Solo 1 archivo de test de servicios (`LedgerServiceTest`). Faltan tests para `StorageService`, `EncryptionService`, `EvidenceService`, `X509Service`, `FnmtIdentityProvider`. | Nuevos tests | L |
| P2-15 | INFRA | ~~CI usa PHP 8.3. El spec exige PHP 8.5. Las features de PHP 8.5 no se validan.~~ | `.gitlab-ci.yml:16` | S | ✅ Resuelto (I-1: composer.json ^8.4) |
| P2-16 | INFRA | ~~Sin SonarQube. PHPStan level 2 (muy bajo). El spec exige SAST con Quality Gate: 0 bugs, 0 vulnerabilities.~~ | `.gitlab-ci.yml` + `sonar-project.properties` | M | ✅ Resuelto (badges + PHPStan CI4) |

---

## P3 — Mejora (backlog)

| # | Area | Descripcion | Archivo | Esfuerzo |
|---|------|-------------|---------|:--------:|
| P3-1 | DOCS | Numeracion de secciones en `docs/01_RESUMEN_COMPLETO.md` salta de 15 a 17 (falta seccion 16). | `docs/01_RESUMEN_COMPLETO.md:416-418` | S |
| P3-2 | AUTH | `AuthController::totpSetup()` hardcodeado a `totpEnabled = false` y `qrCodeUrl = ''`. El QR real solo se genera en FnmtController. | `AuthController.php:286-291` | S |
| P3-3 | NOTIFICATIONS | WhatsApp, Telegram y SMS son stubs. Correcto segun spec (PoC pendiente). No actionable. | 3 archivos stub | — |
| P3-4 | FRONTEND | Codigo postal sin `inputmode="numeric"` ni `maxlength="5"`. | `transfers/create.php` | S |
| P3-5 | FRONTEND | Login sin `autocomplete="off"` en campos sensibles. | `login.php` | S |
| P3-6 | INFRA | PHPUnit con `--no-coverage`. Sin reporte para SonarQube. | `.gitlab-ci.yml:24` | S |
| P3-7 | DOCS | Sin directorio `docs/adr/` con ADRs documentados individualmente (exigido por `04_ARCHITECTURE.md:533-553`). | `docs/adr/` | L |
| P3-8 | FRONTEND | Validacion regex frontend no completamente sincronizada con backend en todos los campos. | `marachain-validation.js` | M |

---

## Resumen de Cumplimiento por Area

| Area | Estado | P0 | P1 | P2 | P3 |
|------|:------:|:--:|:--:|:--:|:--:|
| **AUTH** | ⚠️ Parcial | 0 | 3 | 0 | 1 |
| **DOCUMENTS** | ⚠️ Parcial | — | 2 | — | 1 |
| **ENCRYPTION** | ✅ Corregido | 0 | 0 | — | — |
| **LEDGER** | ✅ Mejorado | — | — | 1 | — |
| **NOTIFICATIONS** | ⚠️ Parcial | — | 1 | 1 | 1 |
| **FRONTEND** | ⚠️ Parcial | — | — | — | 3 |
| **INFRA** | ✅ Mejorado | — | 0 | 1 | 1 |
| **TESTING** | ⚠️ Basico | — | — | 4 | — |
| **SECURITY** | ⚠️ Mejorado | 1 | 2 | 2 | — |

| Prioridad | Count | Resueltos | Pendientes |
|-----------|:-----:|:---------:|:----------:|
| **P0** — Bloqueante | 6 | 5 | 1 |
| **P1** — Critico | 10 | 3 | 7 |
| **P2** — Importante | 16 | 9 | 7 |
| **P3** — Mejora | 8 | 0 | 8 |
| **Total** | **40** | **17** | **23** |

---

## Orden de ejecucion recomendado

```
✅ Semana 1-2:   P0-1, P0-2, P0-5 (security + config)
✅ Semana 3:     P0-3, P0-4, P1-6 (fix encryption pipeline + decrypt)
✅ Semana 4-5:   Fase 2-4: IPFS + API docs + Infra (P1-8, P2-4/6/7/10/15/16, I-2, I-4, I-5)
   Semana 5-6:   P1-1, P1-2, P1-3, P1-10 (auth hardening) ← FASE 5
   Semana 7-8:   P1-4, P1-5 (document transfer complete flow)
   Semana 9-10:  P1-7, P2-8 (notifications transactional + SMTP)
   Semana 11-12: P2-1, P2-2, P2-3 (security headers hardening)
   Semana 13:    P2-5 (Ed25519 signatures)
   Semana 14-16: P2-11, P2-12, P2-13, P2-14 (testing coverage)
   Backlog:      P3-1 a P3-8
```

### Commits de sesiones anteriores

| Commit | Descripcion |
|--------|-------------|
| `b8c97cc` | Fase 4: API REST + OpenAPI 3.1 + Swagger UI (46 endpoints, 29 schemas) |
| `46c50ff` | S-6: rate limit FNMT GET + CSP/TOTP pospuestos |
| `0e5323f` | S-2/3/4: Merkle proofs + TimestampService + receipt endpoint |
| `741fe66` | Fase 2: IPFS Privado (IP-1 a IP-6) — IpfsReconcile command |
| `ba87152` | I-4/I-5: systemd workers (3 services + 3 timers) + health check ampliado |
| `e8ed0b7` | I-2: Bootstrap 5.3 migracion + PWA (manifest.json + sw.js) |
| `67cf60c` | PHPStan CI4 + IDE helper stubs |
| `c3562ba` | I-1: PHP 8.5 (composer.json ^8.4) |
| `625c4c8` | P0-1/2/3/4/5 + P1-6: encryption keys, hash fix, DEK envelope, ApiAuth filter, decryptDocument() |

---

## Notas

- **Estado actual**: 284 tests, 707 assertions en 33 archivos de test (SQLite :memory:)
- **OpenSpec**: 65/66 tareas completadas. Fases 1-7 + Documentacion finalizadas. Solo Fase 8 (E2E Tests) pendiente.
- **Base de datos**: 17 migraciones, 22+ tablas (MySQL development, SQLite testing)
- **Servicios**: 11 (6 implementaciones + 5 interfaces). 3 son stubs (WhatsApp, Telegram, SMS)
- **Controladores REST**: 14 (incluye Api\DocsController para Swagger UI)
- **Controladores Web**: 6
- **CLI Commands**: 5 (LedgerGenesis, LedgerSeal, NotificationsCommand, TransferExpire, IpfsReconcile)
- **IPFS**: Cluster privado activo con reconciliacion automatica (IpfsReconcile)
- **PWA**: manifest.json + sw.js (cache-first, 14 assets)
- **Systemd**: 3 services + 3 timers (notifications, ledger-seal, transfers-expire)
- **API Docs**: OpenAPI 3.1 (3133 lines, 46 endpoints) + Swagger UI
- **Deuda tecnica conocida**: `freshEntity()` bypassing CI4 entity cache. `uuidV4()` ya centralizado en helper.
- **Deuda de auditoria anterior**: 14 observaciones especulativas pendientes documentadas en `AUDIT_REPORT.md`
