# Audit Trail

> Registro de auditorias de codigo realizadas sobre MARAChain.

---

| Date | Version | Auditor | Scope | Findings | Status |
|------|---------|---------|-------|----------|--------|
| 2026-07-14 | 1.2.0 → 1.2.1 | Internal | 33 archivos: 9 controladores Web, 9 modelos, 15 servicios/comandos | 16 defectos (12 corregidos, 4 pendientes de aprobacion) | **Completada** |

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
