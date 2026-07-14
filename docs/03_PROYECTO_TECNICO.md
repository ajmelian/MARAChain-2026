# MARAChain — Proyecto Técnico

**Versión:** 1.2.0  
**Fecha:** 14 de julio de 2026  
**Estado:** Baseline técnica aprobada  
**Clasificación:** Fuente de verdad

## 1. Objeto

Este documento define la baseline técnica para el diseño, desarrollo, pruebas, despliegue y operación de MARAChain.

## 2. Principios técnicos

1. PHP 8.5 será el lenguaje principal del backend.
2. CodeIgniter 4 será el framework de aplicación.
3. CodeIgniter Shield gestionará usuarios, autenticación interna, autorización y sesiones.
4. La arquitectura será un monolito modular.
5. El dominio no dependerá directamente de CodeIgniter, HTTP, MySQL, IPFS o proveedores externos.
6. El contenido documental no llegará en claro al backend.
7. Las integraciones se implementarán mediante puertos y adaptadores.
8. Las evidencias serán append-only.
9. Toda decisión estructural tendrá ADR.
10. El desarrollo seguirá SDD, OpenSpec y TDD.

## 3. Stack tecnológico

### Backend

- PHP 8.5.
- CodeIgniter 4, última versión estable compatible.
- CodeIgniter Shield.
- Composer con `composer.lock`.
- Extensiones mínimas: `openssl`, `sodium`, `intl`, `mbstring`, `json`, `curl`, `pdo_mysql`, `fileinfo`.

### Datos

- MySQL.
- IPFS privado.
- Ledger interno implementado en PHP.

### Perímetro

- Nginx.
- PHP-FPM.
- TLS 1.3 cuando sea viable.
- mTLS para la autenticación FNMT directa.

### Cliente

- HTML5.
- JavaScript.
- WebCrypto.
- Web Workers para hashing y cifrado incremental.
- Sin CDN para librerías criptográficas o críticas.
- Alpino Bootstrap 4, variante horizontal, como base visual.
- Dropzone incluido en Alpino como base del selector de documentos.

### Calidad

- PHPUnit.
- PHPStan o Psalm.
- PHP CS Fixer o equivalente.
- SAST.
- DAST.
- secret scanning.
- auditoría de dependencias.

## 4. Estructura del repositorio

```text
marachain/
├── openspec/
│   ├── project.md
│   ├── specs/
│   ├── changes/
│   └── archive/
├── .opencode/
│   ├── agents/
│   ├── commands/
│   └── skills/
├── tests/
│   ├── Unit/
│   ├── Integration/
│   ├── Feature/
│   ├── Architecture/
│   ├── Security/
│   ├── EndToEnd/
│   ├── Fixtures/
│   └── Support/
├── docs/
│   ├── source-of-truth/
│   ├── adr/
│   ├── diagrams/
│   ├── threat-model/
│   └── historical/
├── resources/
│   └── frontend/
│       └── alpino/
│           ├── original/
│           │   ├── horizontal/
│           │   ├── documentation/
│           │   └── plugins/
│           ├── LICENSE_INFO.md
│           └── VERSION.md
├── wwwroot/
│   ├── app/
│   │   └── Views/
│   │       ├── layouts/
│   │       ├── inbox/
│   │       ├── outbox/
│   │       ├── transfers/
│   │       ├── contacts/
│   │       ├── profile/
│   │       ├── evidence/
│   │       ├── settings/
│   │       └── administration/
│   ├── public/
│   │   └── assets/
│   │       ├── alpino/
│   │       ├── js/
│   │       │   ├── app/
│   │       │   ├── transfers/
│   │       │   ├── crypto/
│   │       │   └── workers/
│   │       └── css/
│   ├── system/
│   ├── writable/
│   ├── vendor/
│   ├── composer.json
│   ├── composer.lock
│   ├── phpunit.xml
│   ├── phpstan.neon
│   ├── env
│   └── spark
├── marachain-vault/
├── AGENTS.md
├── README.md
├── CHANGELOG.md
├── VERSION.md
├── AUDITORY.md
└── Makefile
```

Los tests residirán en la raíz. `wwwroot/phpunit.xml` apuntará a `../tests`.

La plantilla original descomprimida y su documentación se conservarán en:

```text
resources/frontend/alpino/original/
```

Este directorio es material fuente de desarrollo y no se desplegará en producción.

Las rutas de ejecución serán:

```text
Vistas adaptadas a CodeIgniter 4:
wwwroot/app/Views/

Assets Alpino seleccionados y saneados:
wwwroot/public/assets/alpino/

JavaScript propio de MARAChain:
wwwroot/public/assets/js/

CSS propio de MARAChain:
wwwroot/public/assets/css/ o wwwroot/public/assets/css/marachain.css
```

El repositorio deberá ser privado por contener una plantilla comercial. `LICENSE_INFO.md` documentará la licencia adquirida y la prohibición de redistribución. `VERSION.md` registrará la versión, fecha y checksum del paquete original utilizado.

`marachain-vault` será opcional y solo se desarrollará si las pruebas de concepto demuestran que navegador y proveedores no cubren requisitos esenciales.

## 5. Módulos

- Identity.
- Authentication.
- Session.
- Organizations.
- Documents.
- Encryption.
- Signatures.
- Transfers.
- Evidence.
- Ledger.
- Storage.
- Notifications.
- Billing.
- Administration.
- Support futuro.

## 6. Identidad

La identidad interna se representará mediante UUID y será independiente del proveedor.

Tablas conceptuales:

- `users`;
- `identity_identifiers`;
- `identity_credentials`;
- `devices`;
- `auth_sessions`;
- `provider_links`;
- `organization_memberships`.

El NIF/NIE se almacenará cifrado y se localizará mediante HMAC determinista con una clave separada.

No se utilizará hash simple del NIF.

## 7. Autenticación fase 1

1. El usuario accede a una ruta protegida mediante mTLS.
2. Nginx solicita el certificado.
3. Nginx valida la cadena local.
4. El backend valida política, vigencia y atributos.
5. Se normaliza el NIF/NIE.
6. Se localiza o provisiona el usuario.
7. Se solicita TOTP.
8. Se valida TOTP.
9. Shield crea la sesión.
10. Se registra evidencia.
11. Se envía email.

El módulo `Authentication` implementará:

- `DirectFnmtIdentityProvider`;
- `TotpVerifier`;
- `AuthenticationPolicy`;
- `ShieldSessionManager`;
- `AuthenticationEvidenceService`.

Shield no validará directamente el certificado. Recibirá un usuario ya verificado.

## 8. Autenticación fase 2

```php
interface IdentityProviderInterface
{
    public function beginAuthentication(
        AuthenticationRequest $request
    ): ProviderAuthenticationRequest;

    public function completeAuthentication(
        ProviderAuthenticationResponse $response
    ): VerifiedIdentity;

    public function providerCode(): string;
}
```

Adaptadores previstos:

- `FirmaProfesionalIdentityProvider`;
- `ClaveIdentityProvider`;
- otros proveedores compatibles.

La sesión seguirá siendo una sesión Shield.

## 9. TOTP

Parámetros iniciales:

- RFC 6238;
- 6 dígitos;
- periodo de 30 segundos;
- ventana ±1;
- máximo de 5 fallos consecutivos;
- bloqueo temporal;
- prevención de reutilización;
- rate limit por identidad, IP y dispositivo.

El secreto TOTP se cifrará mediante AEAD. No se almacenará como hash.

El reseteo exigirá identidad fuerte, invalidará sesiones y generará evidencia.

## 10. Sesiones

Baseline:

- duración máxima: 8 horas;
- inactividad: 30 minutos;
- reautenticación reciente: 5 minutos para operaciones críticas;
- límite inicial configurable: 5 sesiones;
- rotación tras login, elevación, cambio TOTP y alta de dispositivo.

Las sesiones se almacenarán inicialmente en MySQL. Redis será una evolución.

## 11. Firma electrónica

```php
interface SignatureProviderInterface
{
    public function createSignatureRequest(
        SignatureIntent $intent
    ): ProviderSignatureRequest;

    public function completeSignature(
        ProviderSignatureResponse $response
    ): SignedDigest;

    public function validateSignature(
        SignedDigest $signature
    ): SignatureValidationResult;
}
```

El proveedor recibirá exclusivamente el digest. Nunca recibirá el documento, la DEK, las claves privadas, el CID o los sobres.

El manifiesto de firma incluirá:

- esquema y versión;
- identificador y versión documental;
- algoritmo y hash;
- tamaño;
- MIME;
- remitente;
- destinatarios;
- operación;
- nivel de seguridad;
- nonce;
- emisión y expiración.

MARAChain validará:

- existencia y vigencia de la petición;
- consumo único;
- digest;
- identidad firmante;
- algoritmo;
- transacción;
- nonce;
- firma;
- integridad de la respuesta.

## 12. Cifrado

Baseline de PoC:

- DEK de 256 bits.
- AES-256-GCM.
- SHA-256.
- `crypto.getRandomValues()`.

El mecanismo asimétrico se seleccionará mediante PoC y ADR.

La solución deberá:

- estar soportada por los navegadores objetivo;
- permitir encapsulación por destinatario;
- ser interoperable;
- admitir versionado;
- disponer de vectores de prueba;
- no depender de secretos derivados del NIF, certificado o TOTP.

El AAD incluirá:

- identificador;
- versión;
- hash del manifiesto;
- remitente;
- destinatarios;
- MIME;
- índice y longitud de bloque.

Los ficheros grandes se cifrarán por bloques. El hashing incremental se ejecutará en Web Worker o con librería auditada.

## 13. Multidispositivo

Cada dispositivo tendrá su propia clave.

El alta de un segundo dispositivo requerirá:

- autenticación;
- TOTP o step-up;
- generación de clave;
- aprobación desde dispositivo existente;
- canal efímero;
- evidencia.

La pérdida de todos los dispositivos implicará una nueva época criptográfica sin recuperación de épocas anteriores.

## 14. MARA Vault

Estado: opcional.

Solo se implementará si una PoC demuestra limitaciones insalvables en firma, persistencia segura, multidispositivo, compatibilidad o mitigación de amenazas críticas.

No contendrá dominio, usuarios, sesiones, MySQL, IPFS ni reglas de negocio.

## 15. Documentos y transferencias

Se separarán `Document` y `DocumentTransfer`.

Estados de documento:

- `DRAFT`;
- `SEALED`;
- `ENCRYPTED`;
- `ARCHIVED`;
- `DESTROYED`.

Estados de transferencia:

- `PENDING_RECIPIENT`;
- `READY`;
- `SENDING`;
- `SENT`;
- `AVAILABLE`;
- `ACCESSED`;
- `DOWNLOADED`;
- `ACCEPTED`;
- `REJECTED`;
- `EXPIRED`;
- `REVOKED`;
- `FAILED`.

Una revocación no elimina copias ya descargadas.

## 16. Destinatarios no registrados

El MVP no completará el envío hasta que el destinatario disponga de clave pública.

1. Crear invitación.
2. Enviar token.
3. Autenticar destinatario.
4. Verificar identidad.
5. Registrar clave pública.
6. Completar envío.

## 17. IPFS

Baseline productiva propuesta:

- 3 nodos;
- al menos 2 ubicaciones dentro de la UE;
- factor de replicación mínimo 2;
- API privada;
- `swarm.key` fuera de Git;
- verificación de CID y réplicas;
- reconciliador;
- unpin y garbage collection controlados.

## 18. Ledger

Los eventos se canonicalizarán mediante RFC 8785 y se hashearán con SHA-256.

Los bloques contendrán:

- secuencia;
- periodo;
- eventos incluidos;
- raíz Merkle;
- hash anterior;
- firma Ed25519;
- versión.

La blockchain externa recibirá checkpoints agregados, no documentos ni CIDs.

## 19. Consistencia

Se utilizará:

- Saga orquestada;
- outbox transaccional;
- idempotency key;
- reintentos;
- reconciliación;
- dead-letter.

Orden lógico:

1. firma;
2. cifrado;
3. operación pendiente;
4. IPFS;
5. MySQL + evidencia + outbox;
6. ledger;
7. estado disponible;
8. notificación.

## 20. Colas y workers

Inicialmente se utilizará una cola MySQL.

```bash
php spark marachain:queue:work
php spark marachain:ledger:verify
php spark marachain:ipfs:reconcile
php spark marachain:notifications:send
```

Los workers se gestionarán mediante `systemd`.

## 21. Notificaciones

### 21.1. Modelo de cuentas

MARAChain gestionará una cuenta global corporativa de WhatsApp y una cuenta global corporativa de Telegram.

Los usuarios remitentes no conectarán sus cuentas personales y no aportarán sesiones, cookies, códigos QR, tokens o credenciales de estos servicios.

Los datos introducidos en el formulario representarán únicamente la dirección del destinatario:

- `whatsapp_account`: número normalizado del destinatario;
- `telegram_account`: alias, identificador o dirección resoluble del destinatario;
- `mobile_phone`: número destinado al canal SMS.

### 21.2. Proveedores

```php
interface NotificationProviderInterface
{
    public function channel(): NotificationChannel;

    public function send(
        GlobalMessagingAccount $account,
        RecipientAddress $recipient,
        NotificationMessage $message
    ): NotificationResult;

    public function health(): ProviderHealth;
}
```

Adaptadores previstos:

```text
EmailNotificationProvider
GlobalWhatsAppNotificationProvider
GlobalTelegramNotificationProvider
SmsNotificationProvider
```

La elección del SDK, protocolo o gateway concreto será una decisión de infraestructura sometida a PoC y ADR. El dominio no dependerá de una biblioteca específica.

### 21.3. Flujo

```text
DocumentTransfer AVAILABLE
        ↓
NotificationRequested
        ↓
Outbox transaccional
        ↓
Worker PHP
        ↓
Selección de canales habilitados
        ├── Email
        ├── WhatsApp global
        └── Telegram global
        ↓
Registro del resultado técnico
```

El email será el canal operativo inicial. WhatsApp y Telegram se habilitarán por configuración cuando sus PoC y controles estén aprobados.

### 21.4. Secretos de infraestructura

Las sesiones o credenciales de las cuentas globales se almacenarán fuera de `wwwroot`:

```text
/var/lib/marachain/integrations/
├── whatsapp/
│   └── global/
└── telegram/
    └── global/
```

También podrán utilizarse un secret manager o un volumen cifrado equivalente.

Requisitos:

- separación entre desarrollo, preproducción y producción;
- permisos mínimos del usuario de servicio;
- cifrado en reposo;
- referencia opaca desde MySQL;
- ausencia en Git, logs, backups no cifrados y ledger;
- rotación, revocación y health checks;
- acceso administrativo auditado.

### 21.5. Contenido del mensaje

Las notificaciones podrán incluir:

- nombre visible de MARAChain;
- identificación minimizada del remitente;
- título documental cuando la política lo permita;
- aviso de disponibilidad;
- enlace a MARAChain sin acceso directo;
- fecha de expiración, si procede.

Nunca incluirán:

- documento;
- CID;
- NIF/NIE/CIF completo;
- claves o sobres;
- hash documental;
- token de acceso duradero;
- descripción sensible;
- evidencias completas.

### 21.6. Estados y evidencias

Estados técnicos:

- `QUEUED`;
- `SENDING`;
- `SENT`;
- `DELIVERED`, solo cuando el canal lo informe;
- `FAILED`;
- `RETRYING`;
- `DEAD_LETTER`.

Eventos:

- `notification.whatsapp.requested`;
- `notification.whatsapp.sent`;
- `notification.whatsapp.failed`;
- `notification.telegram.requested`;
- `notification.telegram.sent`;
- `notification.telegram.failed`.

Los acuses del canal no se interpretarán como lectura, aceptación o acceso al documento.

### 21.7. Riesgos y restricciones

- WhatsApp puede requerir una integración no oficial; su uso no tendrá SLA jurídico ni valor de entrega certificada.
- Telegram puede limitar el inicio de conversaciones según el mecanismo elegido y la relación previa con el destinatario.
- Se aplicarán consentimiento, opt-out, rate limiting, listas de bloqueo y prevención de abuso.
- El fallo de un canal complementario no revertirá una transferencia documental ya confirmada.
- El email actuará como fallback cuando corresponda.

## 22. Eliminación

1. Bloquear accesos.
2. Destruir sobres.
3. Eliminar asociaciones off-chain.
4. Ejecutar unpin.
5. Ejecutar garbage collection.
6. Expirar backups.
7. Registrar evidencia mínima.
8. Conservar checkpoints no identificables.

La blockchain no se reescribirá.

## 23. Seguridad

Baseline:

- OWASP ASVS nivel 2;
- nivel 3 para identidad, criptografía, evidencias y administración;
- CSP estricta;
- CSRF;
- same-origin;
- cookies seguras;
- HSTS;
- rate limiting;
- secretos externos;
- mínimo privilegio;
- pentest;
- STRIDE + LINDDUN.

## 24. Pruebas

Cobertura propuesta:

- 80 % de líneas;
- 75 % de ramas;
- 95 % de ramas críticas.

Tipos:

- unitarias;
- integración;
- feature;
- arquitectura;
- contrato;
- end-to-end;
- seguridad;
- rendimiento;
- resiliencia;
- restauración;
- vectores criptográficos.

## 25. Observabilidad

- logs JSON;
- correlation ID;
- exclusión de datos sensibles;
- métricas de aplicación, autenticación, colas, IPFS, ledger, MySQL y email;
- OpenTelemetry;
- Prometheus y Grafana cuando proceda;
- alertas críticas.

## 26. Continuidad

Baseline interna:

- SLO del 99,5 %;
- RPO de 15 minutos para MySQL y ledger;
- RTO de 4 horas;
- RPO 0 del ciphertext tras confirmar replicación.

No se publicarán como SLA contractual sin validación.

## 27. API

Fase 2:

- OpenAPI 3.1;
- JSON:API donde aplique;
- Shield Access Tokens o HMAC;
- mTLS + HMAC para integraciones de alto riesgo;
- no reutilizar sesión web para servidor a servidor.

## 28. PoC obligatorias

1. FNMT + mTLS.
2. FNMT + TOTP + Shield.
3. Cifrado WebCrypto.
4. Hashing incremental.
5. Persistencia de claves.
6. Segundo dispositivo.
7. Firma delegada sobre digest.
8. Compatibilidad Ubuntu, Windows y macOS con Firefox y Chromium.

## 29. Frontend

### 29.1. Baseline visual

La base visual será Alpino Bootstrap 4, variante horizontal. Se reutilizarán como referencia:

- `horizontal/mail-inbox.html`;
- `horizontal/mail-single.html`;
- `horizontal/mail-compose.html`;
- `horizontal/contact.html`;
- `horizontal/profile.html`;
- `horizontal/form-upload.html`;
- `horizontal/assets/css/inbox.css`;
- `horizontal/assets/plugins/dropzone/`.

La plantilla se descompondrá en layouts, parciales y vistas de CodeIgniter 4. Solo se incorporarán los assets necesarios. Los scripts demo, enlaces externos, componentes no utilizados y plugins vulnerables u obsoletos se eliminarán o sustituirán.

### 29.2. Ubicación de la plantilla y política de despliegue

La plantilla descomprimida se almacenará como referencia de desarrollo en:

```text
resources/frontend/alpino/original/
```

Estructura mínima:

```text
resources/frontend/alpino/
├── original/
│   ├── horizontal/
│   ├── documentation/
│   └── plugins/
├── LICENSE_INFO.md
└── VERSION.md
```

No se ejecutarán directamente los HTML originales. Tampoco se copiará de forma indiscriminada el directorio `assets` de la plantilla.

El proceso de build o despliegue excluirá:

```text
resources/frontend/alpino/
```

A producción solo llegarán:

```text
wwwroot/app/Views/
wwwroot/public/assets/alpino/
wwwroot/public/assets/js/
wwwroot/public/assets/css/
```

### 29.3. Mapeo de fuentes Alpino a vistas MARAChain

```text
resources/frontend/alpino/original/horizontal/mail-inbox.html
→ wwwroot/app/Views/inbox/index.php

resources/frontend/alpino/original/horizontal/mail-single.html
→ wwwroot/app/Views/transfers/show.php

resources/frontend/alpino/original/horizontal/mail-compose.html
→ wwwroot/app/Views/transfers/create.php

resources/frontend/alpino/original/horizontal/contact.html
→ wwwroot/app/Views/contacts/index.php

resources/frontend/alpino/original/horizontal/profile.html
→ wwwroot/app/Views/profile/show.php

resources/frontend/alpino/original/horizontal/form-upload.html
→ componente documental integrado en wwwroot/app/Views/transfers/create.php
```

### 29.4. Estructura de vistas

```text
wwwroot/app/Views/
├── layouts/
│   ├── main.php
│   ├── auth.php
│   └── partials/
├── inbox/
├── outbox/
├── transfers/
├── contacts/
├── profile/
├── evidence/
├── settings/
└── administration/
```

Los assets propios se separarán de los del template:

```text
wwwroot/public/assets/
├── alpino/
├── js/transfers/
├── js/crypto/
└── js/workers/
```

### 29.5. Subida documental

Dropzone se utilizará como capa de selección, no como cliente de subida automática. El flujo obligatorio será:

```text
Selección Drag & Drop o Click
        ↓
Validación local
        ↓
Hashing
        ↓
Manifiesto y firma, cuando proceda
        ↓
Cifrado WebCrypto
        ↓
Subida controlada del ciphertext
```

Para el MVP, una transferencia tendrá un documento principal. La ampliación a varios ficheros requerirá una especificación OpenSpec.

### 29.6. Datos del destinatario

El formulario de nuevo envío manejará:

- tipo de destinatario: persona física o jurídica;
- nombre y apellidos o razón social, obligatorio;
- “A la atención de”, obligatorio para persona jurídica;
- NIF/NIE o CIF, recomendado;
- email principal, obligatorio;
- otros emails, recomendados;
- cuenta de Telegram, recomendada;
- cuenta de WhatsApp, recomendada;
- teléfono móvil para SMS, recomendado;
- domicilio, recomendado;
- código postal español de cinco caracteres, recomendado;
- provincia, recomendada;
- título del documento, obligatorio;
- descripción o motivación, obligatoria.

El código postal se tratará como texto para conservar ceros iniciales. Los teléfonos se normalizarán preferentemente en E.164. Los valores de Telegram y WhatsApp son direcciones del destinatario para las cuentas globales de MARAChain. No son credenciales, sesiones, identidades verificadas ni mecanismos de autorización documental.

### 29.7. Seguridad frontend

- CSP estricta y scripts propios sin `unsafe-inline` cuando sea viable.
- No almacenar documentos o claves en `localStorage`.
- No registrar contenido, claves o datos sensibles en consola.
- CSRF para formularios de aplicación.
- Escape contextual de salida.
- Validación en cliente y repetición obligatoria en servidor.
- Auditoría de Bootstrap 4, jQuery, Dropzone y plugins heredados.
- Sin carga automática del fichero original.
- Acciones críticas protegidas mediante reautenticación o step-up.

La baseline completa se define en `06_FRONTEND_DESIGN.md`.

## 30. Pendientes externos

- contrato con proveedor de identidad;
- contrato con proveedor de firma;
- proveedor TSA;
- matriz jurídica de retención;
- infraestructura final;
- calificación regulatoria;
- SDK o protocolo definitivo para la cuenta global de WhatsApp;
- mecanismo definitivo para la cuenta global de Telegram;
- política de consentimiento, opt-out y prevención de abuso en mensajería;
- SLA comercial.
