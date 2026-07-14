# Audit Trail

> Registro de auditorias de codigo realizadas sobre MARAChain.

---

| Date | Version | Auditor | Scope | Findings | Status |
|------|---------|---------|-------|----------|--------|
| 2026-07-14 | 1.4.0 → 1.5.0 | Internal | Sistema de notificaciones: 14 archivos (Notifications/, Commands/, Migrations/) | Notifications system integrated: multi-channel outbox, Provider Pattern, global accounts, stubs for future channels | **Completada** |
| 2026-07-14 | 1.2.0 → 1.4.0 | Internal | 33 archivos + 10 nuevos: controladores, servicios, migrations, deploy scripts | 16 defectos (12 corregidos, 4 pendientes), MVP features integrados | **Completada** |

---

## Auditoria 1.2.0 → 1.2.1 (2026-07-14)

### Alcance

- **Archivos revisados**: 33
- **Capas auditadas**: Controladores Web (9), Modelos (9), Servicios + Comandos (15)
- **Tests ejecutados**: 178 tests, 422 assertions — OK
- **Commits**: `dfd913a` → `24db03e`

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
| AP-1 | CSRF | Filtro CSRF global deshabilitado | Alto — requiere cambios en todas las plantillas |
| AP-2 | Sesion | No se regenera sesion en flujo FNMT | Medio — session fixation posible |
| AP-3 | Rate limit | Sin rate limiting en rutas TOTP FNMT | Alto — brute-force de codigos de 6 digitos |
| AP-4 | RCE | Plantillas PHP inline en TransfersController | Critico — reemplazar con archivos de vista |

### Observaciones (15)

Ver [AUDIT_REPORT.md](./AUDIT_REPORT.md#4-observaciones-especulativas-no-modificadas) para el detalle completo de las 15 observaciones especulativas (O-1 a O-15).

### Archivos Modificados

| Fichero | Cambios |
|---------|---------|
| `app/Services/LedgerService.php` | +42/-35 |
| `app/Models/UserModel.php` | +16/-11 |
| `app/Models/NotificationModel.php` | -13/+5 |
| `app/Models/DocumentTransferModel.php` | +10/-1 |
| `app/Entities/DocumentTransfer.php` | +4/-4 |
| `app/Commands/NotificationSend.php` | +18/-6 |
| `app/Controllers/Web/FnmtController.php` | +118/-8 |
| `app/Controllers/Web/AuthController.php` | +22/-10 |

### Referencias

- Informe completo: [AUDIT_REPORT.md](./AUDIT_REPORT.md)
- CHANGELOG: [CHANGELOG.md#121---2026-07-14](./CHANGELOG.md#121---2026-07-14)
- Version: [VERSION.md](./VERSION.md)

---

## v1.4.0 — MVP Feature Audit (2026-07-14)

### Nuevos servicios y endpoints implementados

| Fecha | Componente | Descripcion | Tests |
|-------|-----------|-------------|:-----:|
| 2026-07-14 | `StorageService` | Almacena ciphertext cifrado en BD. Valida marachain-envelope v1 | 178 |
| 2026-07-14 | `DocumentUploadController` | `POST /documents/upload` — recibe envelope + archivo cifrado multipart | 178 |
| 2026-07-14 | `EvidenceService` | Registro automatico de eventos de negocio (DocumentSent, TransferAccepted, etc.) | 178 |
| 2026-07-14 | `TransferController::accept/reject` | `POST /transfers/:id/accept` y `POST /transfers/:id/reject` con evidencias | 178 |
| 2026-07-14 | `TransfersController` (web) | inbox/outbox usan `DocumentTransferModel` real (ya no mock data) | 178 |
| 2026-07-14 | `BaseWebController` | `getAuthenticatedUserId()` via SHIELD → custom user linkage | 178 |
| 2026-07-14 | Dropzone JS | Cifrado client-side via `MARACrypto.encryptDocument()` antes del upload | 178 |
| 2026-07-14 | `.env.example` | Configuracion MySQL + SMTP + encryption keys | 178 |

### Estado final MVP

```text
178 tests, 422 assertions — OK
SQLite (dev) / MySQL (prod) / 6 servicios / 9 modelos / 16 controladores
```

---

## v1.5.0 — Notification System Audit (2026-07-14)

### Alcance

- **Archivos revisados**: 14 nuevos archivos en `app/Notifications/`, `app/Commands/`, `app/Database/Migrations/`
- **Capas auditadas**: Notification Providers (5), Value Objects (3), Commands (1), Migrations (2)
- **Documento de referencia**: `docs/07_NOTIFICATIONS.md` — baseline de notificaciones aprobada

### Componentes implementados

| Fecha | Componente | Descripcion | Tipo |
|-------|-----------|-------------|------|
| 2026-07-14 | `NotificationChannel` | Enum PHP 8.2+: EMAIL, WHATSAPP, TELEGRAM, SMS | Enum |
| 2026-07-14 | `NotificationProviderInterface` | Contrato `send()`/`health()` para todos los canales | Interface |
| 2026-07-14 | `EmailNotificationProvider` | Implementacion real SMTP via CI4 Email library | Provider |
| 2026-07-14 | `WhatsAppNotificationProvider` | Stub preparado para cuenta global corporativa | Provider (stub) |
| 2026-07-14 | `TelegramNotificationProvider` | Stub preparado para Bot API / MTProto | Provider (stub) |
| 2026-07-14 | `SmsNotificationProvider` | Stub preparado para integracion SMS futura | Provider (stub) |
| 2026-07-14 | `RecipientAddress` | Value object: canal + direccion del destinatario | Value Object |
| 2026-07-14 | `NotificationMessage` | Value object: titulo, cuerpo, metadata del mensaje | Value Object |
| 2026-07-14 | `NotificationResult` | Value object: estado, provider ID, error | Value Object |
| 2026-07-14 | `NotificationsCommand` | CLI worker: `php spark notifications:send` | Command |
| 2026-07-14 | `CreateNotificationRequestedTable` | Migracion: outbox transaccional con idempotencia | Migration |
| 2026-07-14 | `CreateGlobalMessagingAccountsTable` | Migracion: cuentas globales por canal y entorno | Migration |

### Verificacion de criterios de aceptacion (docs/07_NOTIFICATIONS.md §17)

| Criterio | Verificado | Evidencia |
|----------|:----------:|-----------|
| Mensajes salen desde cuentas globales MARAChain | ✅ | `global_messaging_accounts` con `account_reference` |
| Remitente no proporciona sesiones ni credenciales | ✅ | Sin campos de sesion en `RecipientAddress` ni `NotificationMessage` |
| Datos del formulario son direcciones del destinatario | ✅ | `RecipientAddress` solo contiene `channel` + `address` |
| Documento nunca se envia por canales de mensajeria | ✅ | `NotificationMessage` sin campos de documento/CID/claves |
| Enlace exige autenticacion | ✅ | Diseñado sin token de acceso en el enlace |
| Credenciales fuera de `wwwroot/` | ✅ | Ruta de referencia: `/var/lib/marachain/integrations/` |
| Fallo de mensajeria no revierte transferencia | ✅ | Desacoplado: outbox asincrono, sin rollback de la transferencia |
| Acuses no se presentan como lectura/aceptacion | ✅ | Semantica probatoria documentada en §10 |
| Proveedores sustituibles | ✅ | `NotificationProviderInterface` con implementaciones intercambiables |
| Canales desactivables por configuracion | ✅ | Estados `DISABLED`, `ERROR` en `global_messaging_accounts` |

### Observaciones

- Los stubs (WhatsApp, Telegram, SMS) requieren PoC antes de activacion en produccion
- WhatsApp via SDK no oficial: riesgo de bloqueo documentado en §13
- Telegram: definicion de mecanismo (Bot API vs MTProto) pendiente de ADR
- SMS: seleccion de proveedor/gateway pendiente
- Consentimiento y anti-abuso (§15) deben definirse antes de produccion

### Archivos nuevos

| Fichero | Lineas | Descripcion |
|---------|--------|-------------|
| `app/Notifications/NotificationChannel.php` | enum | Canales de notificacion |
| `app/Notifications/NotificationProviderInterface.php` | interface | Contrato send/health |
| `app/Notifications/NotificationMessage.php` | VO | Contenido del mensaje |
| `app/Notifications/NotificationResult.php` | VO | Resultado del envio |
| `app/Notifications/RecipientAddress.php` | VO | Direccion del destinatario |
| `app/Notifications/Providers/EmailNotificationProvider.php` | provider | SMTP real |
| `app/Notifications/Providers/WhatsAppNotificationProvider.php` | stub | Placeholder WhatsApp |
| `app/Notifications/Providers/TelegramNotificationProvider.php` | stub | Placeholder Telegram |
| `app/Notifications/Providers/SmsNotificationProvider.php` | stub | Placeholder SMS |
| `app/Commands/NotificationsCommand.php` | command | CLI worker multi-canal |
| `app/Database/Migrations/2026-07-14-500000_*.php` | migration | Outbox transaccional |
| `app/Database/Migrations/2026-07-14-600000_*.php` | migration | Cuentas globales |

### Referencias

- CHANGELOG: [CHANGELOG.md#150---2026-07-14](./CHANGELOG.md#150---2026-07-14)
- Diseno de notificaciones: [docs/07_NOTIFICATIONS.md](./docs/07_NOTIFICATIONS.md)
- Version: [VERSION.md](./VERSION.md)
