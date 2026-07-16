# Audit Trail

> Registro de auditorias de codigo realizadas sobre MARAChain.

---

| Date | Version | Auditor | Scope | Findings | Status |
|------|---------|---------|-------|----------|--------|
| 2026-07-16 | 1.6.0 → 1.7.0 | Internal | Documentacion completa, OpenSpec state sync, Phase 4 validacion, consistencia entre ficheros | Actualizacion de 8 ficheros de documentacion: README, ARCHITECTURE, CHANGELOG, VERSION, CONFIGURATION, SECURITY, AUDITORY, INSTALL. State tracker: 64/66 tareas completadas. 17th migration documentada. TimestampService y TimestampController documentados. | **Completada** |
| 2026-07-16 | 1.5.0 → 1.6.0 | Internal | Settings table, context column, api-auth filter, NotificationRequestedModel, new tests | 2 migrations + 1 model + 1 filter + 10 test files: SHIELD settings DB-backed, API routes protected, FNMT TOTP rate-limited (AP-3 fixed) | **Completada** |
| 2026-07-14 | 1.4.0 → 1.5.0 | Internal | Sistema de notificaciones: 14 archivos (Notifications/, Commands/, Migrations/) | Notifications system integrated: multi-channel outbox, Provider Pattern, global accounts, stubs for future channels | **Completada** |
| 2026-07-14 | 1.2.0 → 1.4.0 | Internal | 33 archivos + 10 nuevos: controladores, servicios, migrations, deploy scripts | 16 defectos (12 corregidos, 4 pendientes), MVP features integrados | **Completada** |

---

## v1.7.0 — Documentation & Phase 4 Validation Audit (2026-07-16)

### Alcance

- **Archivos revisados**: 8 archivos de documentacion (README.md, ARCHITECTURE.md, CHANGELOG.md, VERSION.md, CONFIGURATION.md, SECURITY.md, AUDITORY.md, INSTALL.md)
- **State tracker**: `.opencode/openspec/.state.yaml` — 66 atomic tasks
- **Roadmap phases**: Fase 1 (Entities) → Fase 7 (Security) verificadas como completadas

### Correcciones de documentacion

| ID | Documento | Descripcion | Tipo |
|----|-----------|-------------|------|
| DOC-1 | README.md | Recuento de migraciones: 16→17 (nueva `800000_AddIpfsAndBlockchainIds`) | Correccion |
| DOC-2 | README.md | Recuento de controladores REST: 11→12 (nuevo `TimestampController`) | Correccion |
| DOC-3 | README.md | Recuento de servicios: 10→11 (nuevo `TimestampService`) | Correccion |
| DOC-4 | README.md | Tests: 22→33 archivos (~220→~500 assertions) | Correccion |
| DOC-5 | README.md | Estructura del proyecto actualizada con 17 migraciones | Correccion |
| DOC-6 | ARCHITECTURE.md | ADR-020 documentado: migracion 800000 columnas IPFS/blockchain | Nuevo |
| DOC-7 | ARCHITECTURE.md | Directory tree actualizado con TimestampController y 17 migraciones | Correccion |
| DOC-8 | ARCHITECTURE.md | API endpoints: TimestampController endpoints documentados | Nuevo |
| DOC-9 | ARCHITECTURE.md | Database section: 16→17 items | Correccion |
| DOC-10 | CHANGELOG.md | Nueva entrada [1.7.0] con todos los cambios de documentacion | Nuevo |
| DOC-11 | CHANGELOG.md | [Unreleased] reorganizado con P1/P2 items del TODO | Correccion |
| DOC-12 | VERSION.md | Bump: 1.6.0→1.7.0, nueva entrada en version history | Actualizacion |
| DOC-13 | CONFIGURATION.md | Version header 1.6.0→1.7.0, Feature Flags actualizados | Actualizacion |
| DOC-14 | SECURITY.md | Version header 1.6.0→1.7.0, Supported Versions actualizado | Actualizacion |
| DOC-15 | INSTALL.md | Version header, test counts corregidos, tabla de migraciones actualizada | Correccion |
| DOC-16 | AUDITORY.md | Nueva entrada de auditoria v1.7.0 | Nuevo |

### OpenSpec State Sync

| Fase | Tareas | Estado | Evidencia |
|------|--------|:------:|-----------|
| **Fase 1** — Entities | 001-008-015-022-029-036-043-050-057 (9) | ✅ Completed | 9 entidades en `app/Entities/` |
| **Fase 2** — Migrations | 002-009-016-023-030-037-044-051-058 (9) | ✅ Completed | 17 migraciones en `app/Database/Migrations/` |
| **Fase 3** — Model Tests (RED) | 003-010-017-024-031-038-045-052-059 (9) | ✅ Completed | 9 model test files |
| **Fase 4** — Models (GREEN) | 004-011-018-025-032-039-046-053-060 (9) | ✅ Completed | 10 modelos |
| **Fase 5** — Validation | 005-012-019-026-033-040-047-054-061 (9) | ✅ Completed | 9 validation groups + 4 CustomRules |
| **Fase 6** — Controller Tests (RED) | 006-013-020-027-034-041-048-055-062 (9) | ✅ Completed | 15 controller test files |
| **Fase 7** — Controllers (GREEN) | 007-014-021-028-035-042-049-056-063 (9) | ✅ Completed | 12 REST + 6 Web controllers |
| **Fase 8** — Security + Docs | 064, 066 (2) | ✅/🔄 | SecurityHeaders ✅, Docs 🔄 |
| **Fase 9** — E2E Tests | 065 (1) | ⏳ Pending | Playwright tests no iniciados |

### Totales verificados

```text
Tareas completadas:  64/66 (97%)
Tareas pendientes:   1 (065 E2E Tests)
Tareas en progreso:  1 (066 Documentacion)
Migraciones:         17
Controladores REST:  12
Controladores Web:   6
Modelos:             10
Servicios:           11 (6 impl + 5 interfaces)
Tests:               33 archivos, ~500 assertions
```

### Referencias

- CHANGELOG: [CHANGELOG.md#170---2026-07-16](./CHANGELOG.md#170---2026-07-16)
- Version: [VERSION.md](./VERSION.md)
- State: [.opencode/openspec/.state.yaml](./.opencode/openspec/.state.yaml)

---

## v1.2.0 → 1.2.1 (2026-07-14)

### Correcciones Aplicadas (12/16)

| ID | Categoria | Descripcion | Fichero Principal |
|----|-----------|-------------|-------------------|
| CR-1 | Criptografia | HMAC reemplazado por AES-256-GCM para secretos TOTP | `FnmtController.php` |
| CR-2 | Integridad | sealBlock envuelto en transaccion BD | `LedgerService.php` |
| CR-3 | Integridad | verifyChain usa Merkle recomputado, no almacenado | `LedgerService.php` |
| CR-4 | Concurrencia | Incrementos atomicos (SET col = col + 1) | `UserModel.php`, `NotificationModel.php` |
| HI-1 | Secreto | Clave HMAC hardcodeada eliminada | `FnmtController.php` |
| HI-2 | Error silenciado | Fallo en creacion de perfil ahora loguea y revierte | `AuthController.php` |
| HI-3 | Auditoria | Evidencias perdidas logueadas con EVIDENCE_LOST | `AuthController.php`, `FnmtController.php` |
| HI-4 | Confianza | $_SERVER reemplazado por $this->request->getServer() | `FnmtController.php` |
| HI-5 | Type safety | Guarda contra null en updateLastLogin | `AuthController.php` |
| HI-6 | Negocio | revokeTransfer valida transiciones de estado | `DocumentTransferModel.php` |
| HI-7 | Concurrencia | Guarda atomica en transicion de estado | `DocumentTransferModel.php` |
| HI-8 | Error silenciado | Operador @ eliminado de sendEmail | `NotificationSend.php` |

### Pendientes de Aprobacion (4/16)

| ID | Categoria | Descripcion | Impacto |
|----|-----------|-------------|---------|
| AP-1 | CSRF | Filtro CSRF global deshabilitado | Alto |
| AP-2 | Sesion | No se regenera sesion en flujo FNMT | Medio |
| AP-3 | Rate limit | ~~Sin rate limiting en rutas TOTP FNMT~~ (corregido en v1.6.0) | ✅ Resuelto |
| AP-4 | RCE | Plantillas PHP inline en TransfersController | Critico |

---

## v1.4.0 — MVP Feature Audit (2026-07-14)

### Nuevos servicios y endpoints implementados

| Fecha | Componente | Descripcion |
|-------|-----------|-------------|
| 2026-07-14 | `StorageService` | Almacena ciphertext cifrado en BD. Valida marachain-envelope v1 |
| 2026-07-14 | `DocumentUploadController` | `POST /documents/upload` — envelope + archivo cifrado multipart |
| 2026-07-14 | `EvidenceService` | Registro automatico de eventos de negocio |
| 2026-07-14 | `TransferController::accept/reject` | Endpoints con evidencias |
| 2026-07-14 | `TransfersController` (web) | inbox/outbox reales (no mock data) |
| 2026-07-14 | `BaseWebController` | `getAuthenticatedUserId()` SHIELD → MARAChain linkage |
| 2026-07-14 | Dropzone JS | Cifrado client-side via `MARACrypto.encryptDocument()` |

---

## v1.5.0 — Notification System Audit (2026-07-14)

### Componentes implementados

| Fecha | Componente | Descripcion |
|-------|-----------|-------------|
| 2026-07-14 | `NotificationChannel` | Enum PHP 8.2+: EMAIL, WHATSAPP, TELEGRAM, SMS |
| 2026-07-14 | `NotificationProviderInterface` | Contrato `send()`/`health()` |
| 2026-07-14 | `EmailNotificationProvider` | Implementacion real SMTP |
| 2026-07-14 | `WhatsAppNotificationProvider` | Stub para cuenta global corporativa |
| 2026-07-14 | `TelegramNotificationProvider` | Stub para Bot API / MTProto |
| 2026-07-14 | `SmsNotificationProvider` | Stub para integracion SMS |
| 2026-07-14 | `NotificationsCommand` | CLI worker: `php spark notifications:send` |
| 2026-07-14 | Outbox + Global accounts | Migraciones 500000 y 600000 |

---

## v1.6.0 — Settings, api-auth & Notification Model Audit (2026-07-16)

### Componentes implementados

| Fecha | Componente | Descripcion |
|-------|-----------|-------------|
| 2026-07-16 | `CreateSettingsTable` | Tabla `settings` para configuracion SHIELD en BD |
| 2026-07-16 | `AddContextColumn` | Columna `context` para segregacion staging/prod |
| 2026-07-16 | `NotificationRequestedModel` | Outbox transaccional con estados, idempotencia, circuit breaker |
| 2026-07-16 | `api-auth` filter | Protege TODAS las rutas API REST con SHIELD SessionAuth |
| 2026-07-16 | Service tests ×5 | StorageService, EvidenceService, X509Service, FnmtIdentityProvider, EncryptionService |
| 2026-07-16 | Web tests ×5 | AuthController, ContactsWeb, TransfersWeb, HealthController, LedgerControllerApi |

### Correcciones de seguridad aplicadas

| ID | Categoria | Descripcion | Origen |
|----|-----------|-------------|--------|
| AP-3 | Rate limiting | `throttle:auth` aplicado a rutas FNMT TOTP | Audit v1.2.1 |
