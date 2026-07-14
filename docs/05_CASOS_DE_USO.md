# MARAChain — Casos de Uso

**Versión:** 1.1.1  
**Fecha:** 13 de julio de 2026  
**Estado:** Baseline funcional aprobada  
**Clasificación:** Fuente de verdad

## 1. Actores

- Usuario no autenticado.
- Remitente.
- Destinatario.
- Administrador.
- Auditor.
- Operador.
- Proveedor de identidad.
- Proveedor de firma.
- Proveedor de sello temporal.
- Servicio de email.
- Red IPFS.
- Ledger interno.
- Blockchain externa futura.

---

## CU-AUTH-001 — Primer acceso con FNMT

**Actor principal:** Usuario no autenticado.

### Precondiciones

- certificado FNMT vigente;
- navegador compatible;
- usuario no bloqueado.

### Flujo principal

1. El usuario pulsa iniciar sesión.
2. Nginx solicita el certificado.
3. Se valida mTLS.
4. Se extrae y normaliza la identidad.
5. Se crea o localiza `UserIdentity`.
6. Se inicia el enrolamiento TOTP.
7. Se genera el secreto.
8. Se muestra el QR.
9. El usuario introduce un código.
10. Se valida.
11. Se crea la sesión Shield.
12. Se envía aviso.
13. Se registra evidencia.

### Alternativas

- certificado ausente;
- certificado caducado;
- emisor no admitido;
- NIF inválido;
- TOTP inválido;
- demasiados intentos.

---

## CU-AUTH-002 — Acceso recurrente

1. Se valida el certificado FNMT.
2. Se resuelve la identidad.
3. Se solicita TOTP.
4. Se valida.
5. Shield crea sesión.
6. Se registra evidencia y se envía email.

---

## CU-AUTH-003 — Acceso con proveedor externo

**Fase:** 2.

1. El usuario inicia autenticación.
2. MARAChain redirige al proveedor.
3. El proveedor autentica.
4. Devuelve una aserción.
5. MARAChain valida firma, issuer, audience, nonce y expiración.
6. Resuelve la identidad.
7. Aplica step-up si la política lo exige.
8. Crea sesión Shield.

---

## CU-AUTH-004 — Reset de TOTP

1. El usuario se autentica con identidad fuerte.
2. Solicita reset.
3. MARAChain invalida sesiones.
4. Aplica periodo de enfriamiento.
5. Genera nuevo secreto.
6. El usuario confirma.
7. Se registra evidencia.
8. Se envía aviso.

El reset no recupera claves documentales.

---

## CU-SESSION-001 — Consultar sesiones

El usuario consulta sesiones activas y visualiza:

- fecha;
- última actividad;
- navegador;
- sistema;
- IP aproximada;
- identificador truncado.

---

## CU-SESSION-002 — Revocar sesión

1. El usuario selecciona una sesión.
2. Reautentica si procede.
3. Shield revoca.
4. Se registra evidencia.
5. Se notifica.

---

## CU-DEVICE-001 — Alta del primer dispositivo

1. El usuario está autenticado.
2. El navegador genera una clave.
3. Se registra la clave pública.
4. Se protege el material local.
5. Se crea una época criptográfica.
6. Se registra evidencia.

---

## CU-DEVICE-002 — Añadir segundo dispositivo

1. El nuevo dispositivo autentica.
2. Genera una clave.
3. Solicita vinculación.
4. El dispositivo existente aprueba.
5. Se intercambia la capacidad de desbloqueo por canal efímero.
6. Se registra el dispositivo.
7. Se genera evidencia.

---

## CU-DEVICE-003 — Pérdida total

1. El usuario se autentica.
2. Declara pérdida.
3. MARAChain revoca dispositivos.
4. Se crea una nueva época.
5. El usuario puede operar con documentos nuevos.
6. Los documentos anteriores pueden quedar inaccesibles.

---

## CU-DOC-001 — Crear borrador

1. El usuario inicia la creación.
2. Selecciona un fichero.
3. El cliente valida tipo y tamaño.
4. Se calcula el hash.
5. Se crea la identidad del documento.
6. Estado `DRAFT`.

---

## CU-DOC-002 — Sellar versión

1. El usuario confirma el contenido.
2. Se construye el manifiesto.
3. Se canonicaliza.
4. Se calcula el hash.
5. Estado `SEALED`.
6. Cualquier cambio crea una nueva versión.

---

## CU-SIGN-001 — Firmar digest

1. El cliente calcula el hash documental.
2. MARAChain crea el contexto.
3. El cliente construye el manifiesto.
4. Calcula el digest.
5. MARAChain solicita firma.
6. El proveedor realiza step-up.
7. El proveedor firma el digest.
8. MARAChain recibe el resultado.
9. Valida identidad, firma, nonce y transacción.
10. Consume la petición.
11. Registra evidencia.

El proveedor nunca recibe el documento.

---

## CU-ENC-001 — Cifrar documento

1. El cliente genera DEK.
2. Cifra el documento con AES-256-GCM.
3. Encapsula DEK para remitente y destinatarios.
4. Elimina DEK temporal de memoria.
5. Envía ciphertext y sobres.

---

## CU-TRANSFER-001 — Enviar documento

### Precondiciones

- sesión válida;
- documento sellado;
- destinatario con clave pública;
- firma válida cuando la política lo exija.

### Flujo principal

1. El usuario selecciona destinatarios.
2. El cliente construye artefactos.
3. MARAChain valida.
4. Solicita TOTP de autorización si procede.
5. Crea operación `PENDING`.
6. IPFS almacena y replica.
7. MySQL confirma.
8. Evidence registra.
9. Ledger incorpora.
10. Estado `AVAILABLE`.
11. Email notifica.

---

## CU-TRANSFER-002 — Identificar e invitar destinatario

### Datos del destinatario

- tipo: persona física o jurídica;
- nombre y apellidos o razón social, obligatorio;
- “A la atención de”, obligatorio si es persona jurídica;
- NIF/NIE o CIF, recomendado;
- email principal, obligatorio;
- otros emails, recomendados;
- cuenta de Telegram, recomendada;
- cuenta de WhatsApp, recomendada;
- teléfono móvil para SMS, recomendado;
- domicilio, recomendado;
- código postal de cinco caracteres, recomendado;
- provincia, recomendada.

### Datos del envío

- título del documento, obligatorio;
- descripción o motivación, obligatoria.

### Flujo principal

1. El remitente indica el tipo de destinatario.
2. Completa los campos obligatorios y, opcionalmente, los recomendados.
3. MARAChain valida y normaliza los datos.
4. Si no existe una identidad vinculada, crea una identidad pendiente.
5. Envía una invitación al email principal.
6. El destinatario se autentica y verifica su identidad.
7. MARAChain comprueba la correspondencia con los datos aportados.
8. Registra la clave pública del destinatario.
9. El remitente completa el envío.

Los emails secundarios, Telegram, WhatsApp y SMS serán canales de contacto o notificación. No concederán acceso ni sustituirán la identidad verificada.

---

## CU-ACCESS-001 — Consultar bandeja

El destinatario visualiza metadatos mínimos de transferencias autorizadas.

No se muestra contenido sin completar el acceso criptográfico.

---

## CU-ACCESS-002 — Acceder a documento

1. El usuario solicita acceso.
2. Se valida identidad, sesión, ACL, estado y expiración.
3. Se entrega el sobre correspondiente.
4. Se recupera ciphertext.
5. El cliente desencapsula DEK.
6. Verifica integridad.
7. Descifra.
8. Se registra `ACCESSED`.

---

## CU-ACCESS-003 — Descargar

1. Se transmite ciphertext.
2. El cliente verifica.
3. Se registra `DOWNLOADED`.

No se afirma que el usuario haya leído o comprendido el contenido.

---

## CU-TRANSFER-003 — Revocar

1. El remitente solicita revocación.
2. Se valida la política.
3. Estado `REVOKED`.
4. Se bloquean accesos futuros.
5. Se registra evidencia.
6. Se notifica.

No elimina copias ya descargadas.

---

## CU-TRANSFER-004 — Expirar

Un proceso asíncrono marca transferencias expiradas y bloquea accesos.

---

## CU-TRANSFER-005 — Aceptar o rechazar

**Fase:** posterior.

1. El destinatario selecciona aceptar o rechazar.
2. Se genera manifiesto.
3. Se firma.
4. Se registra evento inmutable.
5. Se notifica.

---

## CU-EVID-001 — Consultar evidencias

Un usuario autorizado consulta eventos vinculados a su documento o transferencia.

---

## CU-EVID-002 — Exportar evidencias

Se genera un paquete verificable con:

- eventos;
- hashes;
- firma;
- certificados;
- manifiesto;
- recibos;
- pruebas de ledger;
- instrucciones de verificación.

---

## CU-LEDGER-001 — Cerrar bloque

1. El worker selecciona eventos.
2. Calcula la raíz Merkle.
3. Referencia el hash anterior.
4. Firma el bloque.
5. Persiste append-only.
6. Marca los eventos incluidos.

---

## CU-LEDGER-002 — Verificar ledger

1. El verificador recorre bloques.
2. Comprueba hashes.
3. Comprueba Merkle.
4. Comprueba firmas.
5. Si detecta ruptura, genera un incidente.

---

## CU-LEDGER-003 — Anclar checkpoint

**Fase:** comercial.

1. Se crea un checkpoint agregado.
2. Se envía a la DLT.
3. Se conserva el recibo.
4. Se verifica.
5. Se registra el estado.

---

## CU-NOTIF-001 — Enviar email

1. Outbox crea la solicitud.
2. El worker envía.
3. Registra la respuesta.
4. Reintenta si el error es recuperable.
5. Envía a dead-letter si supera la política.

---

## CU-DELETE-001 — Suprimir documento

1. Se valida la legitimación.
2. Se comprueba `legal_hold`.
3. Se revocan accesos.
4. Se destruyen sobres.
5. Se eliminan relaciones.
6. Se ejecuta unpin.
7. Se ejecuta garbage collection.
8. Se programa la expiración de backup.
9. Se genera evidencia mínima.
10. El ledger no se reescribe.

---

## CU-ADMIN-001 — Bloquear usuario

1. El administrador autorizado selecciona usuario.
2. Justifica la operación.
3. Reautentica.
4. Se bloquea el acceso.
5. Se revocan sesiones.
6. Se genera evidencia.

---

## CU-ADMIN-002 — Consultar salud

El operador consulta:

- colas;
- IPFS;
- ledger;
- MySQL;
- SMTP;
- almacenamiento;
- certificados;
- errores.

No puede descifrar documentos.

---

## CU-AUDIT-001 — Auditoría

El auditor consulta evidencias, no contenido.

Los datos personales se minimizan según finalidad y rol.

---

## CU-ERROR-001 — Fallo IPFS

- no se publica el envío;
- se reintenta;
- se reconcilia;
- se registra el estado.

---

## CU-ERROR-002 — Fallo de ledger

La transferencia queda `LEDGER_PENDING` o se bloquea conforme a política.

---

## CU-ERROR-003 — Fallo de email

El envío no se revierte. La notificación queda pendiente.

---

## CU-ERROR-004 — Firma inválida

La operación se aborta antes del almacenamiento definitivo.

---

---

## CU-UI-001 — Acceder a Inbox después de autenticarse

1. El usuario completa la autenticación.
2. Shield crea la sesión.
3. MARAChain redirige a `/inbox`.
4. Se cargan las `DocumentTransfer` recibidas.
5. Cada fila muestra remitente, título, estado, fecha, firma, seguridad y expiración.

No se mostrará un dashboard estadístico como vista inicial del usuario estándar.

---

## CU-UI-002 — Consultar Outbox

1. El usuario accede a `/outbox`.
2. MARAChain carga las transferencias donde actúa como remitente.
3. La vista muestra el estado individual de cada transferencia.
4. Las acciones disponibles se calculan según estado y permisos.

---

## CU-UPLOAD-001 — Seleccionar documento mediante Drag & Drop o Click

1. El usuario abre Nuevo envío.
2. Arrastra un fichero al área Dropzone o hace clic para seleccionarlo.
3. El componente valida tipo, tamaño y número de ficheros.
4. La carga automática permanece desactivada.
5. El cliente calcula el hash.
6. Se construye el manifiesto.
7. Se firma el digest cuando la política lo exige.
8. El cliente cifra el documento.
9. Solo entonces se sube el ciphertext.
10. La interfaz distingue las fases de hashing, firma, cifrado y transferencia.

El MVP manejará un documento principal por transferencia.

---

## CU-PROFILE-001 — Consultar perfil

1. El usuario accede a `/profile`.
2. La vista basada en Alpino `profile.html` muestra identidad verificada y proveedor.
3. Muestra nivel de garantía, email, estado TOTP, dispositivos y sesiones.
4. Permite gestionar preferencias y operaciones de seguridad autorizadas.

No se muestran secretos TOTP, claves privadas, claves WebCrypto, tokens ni datos criptográficos sensibles.

---

## CU-CONTACT-001 — Consultar contactos

1. El usuario accede a `/contacts`.
2. MARAChain lista personas físicas y jurídicas usadas previamente.
3. Se distingue entre contacto, identidad pendiente e identidad verificada.
4. El usuario puede seleccionar un contacto para iniciar un nuevo envío.

Los datos de contacto no implican autorización documental.


## Reglas globales

- ningún administrador recupera claves;
- ningún proveedor recibe el documento;
- el CID no se publica en blockchain;
- los hashes simples no se publican como identidad documental;
- las evidencias no se editan;
- las sesiones son Shield;
- el backend es PHP;
- la API pública es fase 2;
- los cambios requieren OpenSpec y pruebas;
- la autenticación redirige a Inbox;
- el frontend utiliza Alpino Horizontal;
- la selección documental utiliza Dropzone sin auto-upload;
- el fichero original nunca se envía antes del cifrado;
- los canales de contacto no conceden acceso ni equivalen a identidad verificada.
