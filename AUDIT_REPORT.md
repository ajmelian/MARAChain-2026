# Informe de Auditoría de Corrección — MARAChain

**Fecha**: 2026-07-14  
**Versión auditada**: main (commits `dfd913a` → `24db03e`)  
**Estado final**: 178 tests, 422 assertions — OK

---

## 1. Resumen Ejecutivo

Se realizó una auditoría de corrección sobre el codebase de MARAChain, revisando 33 archivos en 3 capas: controladores web (9 archivos), modelos de persistencia (9 archivos) y servicios + comandos (15 archivos). Se identificaron **16 defectos confirmados**, de los cuales **12 han sido corregidos** con cambios locales mínimos. **4 hallazgos requieren aprobación del equipo** antes de ser implementados por implicar cambios de schema, API, o decisión de producto. Los tests existentes (178 tests, 422 assertions) continúan pasando.

---

## 2. Correcciones Confirmadas

### CR-1: FnmtController::decryptTotpSecret devolvía cadena vacía — autenticación FNMT recurrente rota

- **Fichero**: `wwwroot/app/Controllers/Web/FnmtController.php:407-495`
- **Categoría**: Criptografía incorrecta (HMAC usado como cifrado reversible)
- **Escenario**: Un usuario FNMT que ya completó el enrolamiento TOTP vuelve a autenticarse. `encryptTotpSecret()` usaba `hash_hmac('sha256', ...)` — un hash unidireccional, no cifrado reversible. `decryptTotpSecret()` era un stub que devolvía `''`. `verifyTotp('', $code)` siempre retorna `false`. Tras 5 intentos el usuario queda bloqueado 30 minutos.
- **Radio de afectación**: 100% de usuarios FNMT recurrentes. CU-AUTH-002 no funcional.
- **Corrección**: Reemplazado HMAC por AES-256-GCM AEAD (`openssl_encrypt`/`openssl_decrypt`). El secreto TOTP se almacena como `base64(IV + ciphertext + tag)`, reversible. ⚠️ Los secretos antiguos (basados en HMAC) no son migrables; los usuarios existentes necesitarán re-enrolamiento TOTP.

### CR-2: sealBlock sin transacción de base de datos

- **Fichero**: `wwwroot/app/Services/LedgerService.php:106-191`
- **Categoría**: Integridad de datos (operaciones multi-tabla no atómicas)
- **Escenario**: `sealBlock()` inserta un bloque en `ledger_blocks` y luego actualiza registros de `evidences` en un bucle `foreach`. Si la actualización de evidencias falla a mitad del bucle, el bloque ya está persistido pero las evidencias no están vinculadas. Una ejecución posterior de `sealBlock()` recogería las mismas evidencias huérfanas y crearía un bloque duplicado con los mismos eventos — violando la garantía append-only del ledger.
- **Corrección**: Envuelto todo el método en `$this->ledgerBlockModel->db->transStart()` / `transComplete()` con `try/catch` y `transRollback()`.

### CR-3: verifyChain — verificación de hash de bloque tautológica

- **Fichero**: `wwwroot/app/Services/LedgerService.php:281-318`
- **Categoría**: Verificación de integridad ineficaz
- **Escenario**: El bloque usa `'merkleRoot' => $storedMerkle` (el valor almacenado, potencialmente manipulado) para recomputar el hash de bloque en lugar de usar `$recomputedMerkle` (recalculado desde los eventos). Si un atacante modifica `merkle_root` en la DB, la verificación del hash de bloque coincide porque usa el mismo valor manipulado como entrada.
- **Corrección**: Movida la recomputación del Merkle root ANTES del hash de bloque. El `$recomputedData` ahora usa `$recomputedMerkle`.

### CR-4: Condición de carrera en incrementoTotpFailures y incrementAttemptCount (TOCTOU)

- **Ficheros**: `wwwroot/app/Models/UserModel.php:207-224`, `wwwroot/app/Models/NotificationModel.php:222-239`
- **Categoría**: Condición de carrera (read-then-write)
- **Escenario**: Dos peticiones concurrentes leen `totp_failures = 3`, ambas computan `4`, ambas escriben `4`. El contador debería ser `5`. Un atacante puede evitar el bloqueo de 5 fallos compitiendo peticiones.
- **Corrección**: Reemplazado por `SET totp_failures = totp_failures + 1` atómico via Query Builder con `->set('totp_failures', 'totp_failures + 1', false)`. Mismo patrón aplicado a `NotificationModel::incrementAttemptCount()`.

### HI-1: Clave HMAC hardcodeada 'marachain-dev-key' como fallback

- **Fichero**: `wwwroot/app/Controllers/Web/FnmtController.php:87-96`
- **Categoría**: Secreto hardcodeado
- **Corrección**: Eliminado el fallback. Ahora lanza `\RuntimeException('encryption.hmacKey is not configured.')` si la variable de entorno está vacía.

### HI-2: Fallo en creación de perfil custom silenciado en register()

- **Fichero**: `wwwroot/app/Controllers/Web/AuthController.php:187-218`
- **Categoría**: Error silenciado (swallowed error)
- **Corrección**: El bloque catch ahora: (1) loguea a nivel `critical`, (2) intenta eliminar el usuario SHIELD (rollback), (3) devuelve error al usuario en lugar de hacer auto-login.

### HI-3: Fallos en grabación de evidencias silenciados

- **Ficheros**: `wwwroot/app/Controllers/Web/AuthController.php:303-331`, `wwwroot/app/Controllers/Web/FnmtController.php:553-575`
- **Categoría**: Pérdida de trazabilidad de auditoría
- **Corrección**: `recordEvidence()` ahora loguea a nivel `critical` con prefijo `EVIDENCE_LOST:` y stack trace completo.

### HI-4: `$_SERVER` usado directamente — violación de frontera de confianza

- **Fichero**: `wwwroot/app/Controllers/Web/FnmtController.php:73-74`
- **Categoría**: Inyección de cabeceras SSL
- **Escenario**: Si un reverse proxy no limpia cabeceras `SSL_CLIENT_*`, un atacante puede inyectar `SSL_CLIENT_VERIFY: SUCCESS` y `SSL_CLIENT_S_DN` con un NIF falso.
- **Corrección**: Cambiado `$_SERVER` por `$this->request->getServer()` (input filtrado de CI4).

### HI-5: `updateLastLogin` pasa 0 cuando `$user->id` es null

- **Fichero**: `wwwroot/app/Controllers/Web/AuthController.php:100-105`
- **Categoría**: Type cast inseguro
- **Corrección**: Añadida guarda explícita: solo llama `updateLastLogin()` cuando `$shieldUserId !== null && $shieldUserId > 0`.

### HI-6: revokeTransfer — bypass del validador de máquina de estados

- **Fichero**: `wwwroot/app/Models/DocumentTransferModel.php:234-250`
- **Categoría**: Violación de invariante de negocio
- **Escenario**: `revokeTransfer()` establecía `REVOKED` directamente sin validar la transición. Permitía revocar un `ACCEPTED` (estado terminal sin transiciones de salida).
- **Corrección**: Añadida validación via `$entity->allowedTransitions()` antes de la actualización. También añadido `REVOKED` a las transiciones permitidas desde `PENDING_RECIPIENT`, `READY`, `SENDING` y `SENT`.

### HI-7: transitionStatus sin guarda atómica de concurrencia

- **Fichero**: `wwwroot/app/Models/DocumentTransferModel.php:218`
- **Categoría**: TOCTOU en transición de estado
- **Corrección**: La actualización ahora incluye `->where('status', $row['status'])` como guarda atómica. Si el estado cambió concurrentemente, `affectedRows()` será 0.

### HI-8: NotificationSend::sendEmail — supresión `@` en mail()

- **Fichero**: `wwwroot/app/Commands/NotificationSend.php:104`
- **Categoría**: Error silenciado
- **Corrección**: Eliminado el operador `@`. Añadida validación `FILTER_VALIDATE_EMAIL`. En caso de fallo se registra `error_get_last()`.

---

## 3. Hallazgos que Requieren Aprobación

### AP-1: CSRF globalmente deshabilitado

- **Fichero**: `wwwroot/app/Config/Filters.php:81-84`
- **Escenario**: `'csrf'` está comentado en `$globals['before']`. Todas las rutas POST están expuestas a CSRF.
- **Acción requerida**: Descomentar `'csrf'`, añadir `except => ['api/*', 'health']` para excluir rutas API, y añadir `<?= csrf_field() ?>` a todos los formularios HTML en las vistas. **Requiere cambios coordinados en todas las plantillas.**

### AP-2: Session fixation en flujo FNMT

- **Fichero**: `wwwroot/app/Controllers/Web/FnmtController.php:116-138`
- **Escenario**: La sesión no se regenera entre la verificación del certificado mTLS y la verificación TOTP.
- **Acción requerida**: Añadir `session()->regenerate()` tras la resolución de identidad FNMT (línea ~85) y tras la verificación TOTP exitosa (antes de crear sesión SHIELD, línea ~504). **Requiere pruebas del ciclo de vida de sesión SHIELD.**

### AP-3: FnmtController — sin rate limiting en rutas TOTP

- **Fichero**: `wwwroot/app/Config/Routes.php:22-26`
- **Escenario**: Los códigos TOTP son de 6 dígitos. Sin rate limiting, el brute-force es viable antes de que el bloqueo de 5 fallos actúe.
- **Acción requerida**: Añadir `'filter' => 'throttle:auth'` a las rutas `auth/fnmt/totp-setup` y `auth/fnmt/totp-verify`.

### AP-4: Plantillas PHP inline (RCE potencial)

- **Fichero**: `wwwroot/app/Controllers/Web/TransfersController.php:233-250, 276-331`
- **Escenario**: Uso de `view('string:' . $dynamicContent)` con etiquetas `<?php` inline. Si datos no escapados entran en estas plantillas, se convierten en ejecución de código PHP.
- **Acción requerida**: Reemplazar con archivos `.php` de vista. **Requiere creación de nuevos archivos de vista.**

---

## 4. Observaciones Especulativas No Modificadas

| # | Fichero:línea | Observación | Riesgo |
|---|---------------|-------------|--------|
| O-1 | `X509Service.php:243` | `determineGuaranteeLevel()` siempre devuelve `'high'`, sin inspeccionar OIDs de políticas de certificado | Certificados FNMT no cualificados tratados como cualificados |
| O-2 | `LedgerService.php:350` | `createGenesisBlock()` tiene TOCTOU (check + insert no atómicos). Mitigado parcialmente por UNIQUE en `block_number` | Dos procesos concurrentes crearían bloque génesis duplicado (error PDO) |
| O-3 | `EvidenceModel.php:206` | `assignToLedger()` recibe `$blockId` pero no lo persiste (no existe columna `block_id`) | Evidencia vinculada solo por número, no por UUID |
| O-4 | Todos los modelos | `generateUuidV4()` duplicado en 10 archivos | Violación DRY; bug en una copia requiere arreglo en todas |
| O-5 | `HealthController.php:71` | Expone `PHP_VERSION` sin autenticación | Information disclosure |
| O-6 | `X509Service.php:178` | `parseDN()` frágil con caracteres escapados (`\/`, `\=`) | Aceptable para formato FNMT conocido (sin escapes) |
| O-7 | `SecurityHeaders.php:49` | `X-XSS-Protection: 1; mode=block` obsoleto | Riesgo bajo; CSP `script-src` ofrece mejor protección |
| O-8 | `SecurityHeaders.php:58` | CSP `default-src 'self'` bloquea imágenes externas (QR codes) | La vista TOTP carga QR de `api.qrserver.com` — bloqueado |
| O-9 | `Throttle.php:71-81` | Rate limiter basado en archivos con TOCTOU (read sin lock, write con lock) | Dos peticiones concurrentes pueden compartir bucket |
| O-10 | `SignatureRequestModel.php:209` | `updateStatus()` sin validación de transiciones de máquina de estados | Saltar de CREATED a CONSUMED directamente |
| O-11 | `DocumentModel.php:290` | `createNewVersion()` TOCTOU en incremento de versión | Dos versiones "#4" concurrentes con UUIDs distintos |
| O-12 | `UserModel.php:98` | `create()` detecta duplicados de email con check + insert no atómicos | DB tiene UNIQUE, pero la excepción PDO no se traduce a RuntimeException amigable |
| O-13 | `ProfleController.php:42` | `findByShieldUserId($shieldUser->id ?? 0)` — pasa 0 si id es null | Búsqueda silenciosa que no encuentra nada |
| O-14 | `X509Service.php:57` | `extractIdentityFromNginx()` devuelve null indiferenciado para 3 fallos distintos | Imposible diagnosticar si es infraestructura, formato o datos |
| O-15 | Todos los modelos | Patrón `freshEntity()` duplica SELECTs (N+1 en bucles) | Latencia en `sealBlock` al iterar evidencias |

---

## 5. Tests Realizados y Resultados

| Suite | Tests | Assertions | Resultado |
|-------|-------|------------|-----------|
| `tests/Unit/Models/` | 91 | 313 | ✅ OK |
| `tests/Unit/Controllers/` | 73 | 77 | ✅ OK |
| `tests/Unit/Services/` | 14 | 32 | ✅ OK |
| **Total** | **178** | **422** | ✅ **OK** |

### Tests de integridad del Ledger (14 tests)

- Merkle tree: 6 tests (single leaf, 2/3/4 leaves, empty, determinístico) ✅
- Genesis block: 2 tests (creación, doble creación lanza excepción) ✅
- Block sealing: 2 tests (sin evidencia → null, con evidencia → bloque #2) ✅
- Chain verification: 2 tests (cadena vacía, génesis solo, génesis+sellado) ✅
- Tamper detection: 1 test (hash manipulado detectado) ✅

---

## 6. Tests No Realizados / Brechas de Verificación

| Brecha | Descripción |
|--------|-------------|
| **Transacción sealBlock** | No hay test que simule fallo a mitad del bucle de evidencias para verificar rollback |
| **Concurrencia** | No hay tests de condiciones de carrera. PHPUnit no soporta tests multi-proceso nativamente |
| **CSRF** | No hay tests que verifiquen protección CSRF en rutas POST (el filtro está deshabilitado) |
| **Rate limiting** | No hay tests del filtro Throttle |
| **FNMT flujo completo** | No hay tests E2E del flujo FNMT (requiere certificados X.509 reales o mocks de Nginx) |
| **Email worker** | `NotificationSend` no tiene tests unitarios |
| **HealthController** | Sin tests del endpoint `/health` |
| **Merkle+hash integrado** | Los tests de tamper modifican `block_hash` pero no `merkle_root`; la corrección C-02 debería tener un test que modifique `merkle_root` y verifique que el hash de bloque también falle |

---

## 7. Archivos Modificados

| Fichero | Cambios | Correcciones |
|---------|---------|-------------|
| `app/Services/LedgerService.php` | +42/-35 | C-01 (transacción), C-02 (Merkle antes de hash), C-03 (atomicidad) |
| `app/Models/UserModel.php` | +16/-11 | C-04 (atomic increment) |
| `app/Models/NotificationModel.php` | -13/+5 | C-04 (atomic increment) |
| `app/Models/DocumentTransferModel.php` | +10/-1 | HI-6 (state machine), HI-7 (atomic guard) |
| `app/Entities/DocumentTransfer.php` | +4/-4 | HI-6 (REVOKED en más estados) |
| `app/Commands/NotificationSend.php` | +18/-6 | HI-8 (email validation, sin @) |
| `app/Controllers/Web/FnmtController.php` | +118/-8 | CR-1, HI-1, HI-3, HI-4 |
| `app/Controllers/Web/AuthController.php` | +22/-10 | HI-2, HI-3, HI-5 |
