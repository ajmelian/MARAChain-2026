# MARAChain — Resumen Completo

**Versión:** 1.2.0  
**Fecha:** 14 de julio de 2026  
**Estado:** Baseline aprobada  
**Clasificación:** Fuente de verdad

## 1. Introducción

MARAChain es una plataforma SaaS destinada a la gestión, transmisión y custodia segura de documentos entre personas físicas y, en fases posteriores, entre personas, profesionales, empresas y Administraciones.

El proyecto parte de una necesidad concreta: enviar un fichero no equivale a disponer de una transmisión documental segura y verificable. En numerosos contextos es necesario acreditar quién intervino, qué documento se transmitió, cuándo se realizó la operación, quién pudo acceder, qué evidencias se generaron y si el contenido permaneció íntegro.

MARAChain combinará identidad electrónica, autenticación reforzada, firma electrónica delegada, cifrado de extremo a extremo, almacenamiento distribuido privado y un ledger criptográfico de evidencias.

## 2. Problema que resuelve

Los canales convencionales presentan limitaciones:

- el correo electrónico no garantiza por sí mismo la identidad real;
- una cuenta con contraseña no prueba suficientemente quién actúa;
- un enlace puede reenviarse o quedar expuesto;
- el proveedor de almacenamiento suele poder acceder técnicamente al contenido;
- los registros de actividad pueden modificarse o eliminarse;
- una descarga no equivale a lectura ni aceptación;
- firma, envío, custodia y auditoría suelen depender de herramientas separadas;
- la eliminación puede entrar en conflicto con evidencias históricas o registros inmutables.

MARAChain ofrecerá un proceso trazable desde la identificación de los participantes hasta la conservación o supresión controlada del documento.

## 3. Propuesta de valor

### 3.1. Identidad verificada

Los participantes no se identificarán únicamente mediante email y contraseña.

- En el MVP se empleará certificado FNMT de ciudadano.
- En la segunda fase la identidad se delegará en un tercero de confianza compatible con eIDAS, previsiblemente FirmaProfesional o Cl@ve.

### 3.2. Autenticación reforzada

La primera fase combinará:

```text
Certificado FNMT
        +
TOTP
        ↓
Sesión CodeIgniter Shield
```

La sesión interna, los permisos y la revocación seguirán bajo control de MARAChain y Shield.

### 3.3. Firma electrónica delegada

El proveedor de firma recibirá exclusivamente el hash o digest requerido.

Nunca recibirá:

- el documento en claro;
- la clave documental;
- la clave privada del usuario;
- el CID;
- los sobres criptográficos.

### 3.4. Cifrado `only-4-your-eyes`

El documento se cifrará en el dispositivo del usuario. El backend, los administradores, MySQL, IPFS y los proveedores externos no recibirán el contenido en claro.

### 3.5. Almacenamiento distribuido privado

El ciphertext se almacenará en una red IPFS privada, con replicación, control de pinning y reconciliación.

### 3.6. Evidencia verificable

Cada operación relevante generará un evento canónico e inmutable.

### 3.7. Ledger y anclaje externo

El MVP utilizará un ledger interno implementado en PHP. La versión comercial podrá anclar checkpoints agregados en una blockchain o DLT externa sin publicar documentos ni identificadores directos.

## 4. Alcance del MVP

El MVP se orientará principalmente a personas físicas autenticadas mediante certificado FNMT de ciudadano.

Los escenarios nativos serán C2C y relaciones en las que cada interviniente actúe bajo su propia identidad. La representación formal de empresas o Administraciones se incorporará después.

### Funciones incluidas

- alta y autenticación sin contraseña;
- autenticación FNMT directa;
- TOTP obligatorio;
- sesión Shield;
- subida de documentos;
- cálculo local del hash;
- cifrado local con WebCrypto;
- envío a destinatarios autorizados;
- recepción y descarga;
- almacenamiento en IPFS privado;
- metadatos y ACL en MySQL;
- notificaciones por email y arquitectura preparada para avisos globales por WhatsApp y Telegram;
- evidencias de autenticación, envío, acceso y descarga;
- ledger interno;
- auditoría administrativa sin acceso al contenido;
- revocación de accesos futuros;
- expiración;
- eliminación controlada.

### Funciones pospuestas

- pagos y suscripciones autoservicio;
- representación empresarial completa;
- grupos masivos;
- API pública;
- WhatsApp, Telegram y SMS;
- firma múltiple;
- aceptación y rechazo firmados;
- sellado de tiempo obligatorio;
- anclaje en blockchain externa;
- formatos Office y ejecutables;
- recuperación administrativa;
- dispositivos temporales.

## 5. Identidad y autenticación

### Fase 1

```text
Certificado FNMT
        +
TOTP
        ↓
Identidad MARAChain
        ↓
Sesión Shield
```

MARAChain validará directamente el certificado conforme a la política del MVP.

### Fase 2

```text
FirmaProfesional / Cl@ve / otro proveedor
        ↓
Identidad verificada
        ↓
Step-up o MFA
        ↓
Sesión Shield
```

La aplicación trabajará con una abstracción de proveedor de identidad, por lo que el dominio no dependerá de FNMT, FirmaProfesional o Cl@ve.

## 6. Firma por hash

El flujo de firma será:

```text
Documento
    ↓ SHA-256
Hash documental
    ↓ incluido en
Manifiesto canónico
    ↓ SHA-256
Digest de firma
    ↓
Proveedor de firma
```

El manifiesto vinculará:

- hash del documento;
- versión;
- remitente;
- destinatarios;
- operación;
- nivel de seguridad;
- nonce;
- fecha de emisión;
- caducidad.

Este enfoque evita enviar el documento al proveedor, reduce latencia, evita límites de tamaño impuestos por terceros y preserva la confidencialidad.

## 7. Cifrado extremo a extremo

El navegador generará una clave documental aleatoria por documento.

```text
DEK aleatoria
    ↓
AES-256-GCM
    ↓
Ciphertext
```

La DEK se encapsulará para remitente y destinatarios mediante sus claves públicas. El mecanismo asimétrico definitivo se aprobará después de la PoC.

El backend almacenará:

- ciphertext;
- nonce y tag;
- manifiesto;
- sobres de claves;
- identificadores de clave;
- CID;
- metadatos;
- estados;
- evidencias.

No almacenará la DEK ni claves privadas en claro.

## 8. Multidispositivo

La arquitectura prevé claves por dispositivo y una identidad criptográfica del usuario.

El alta de un nuevo dispositivo exigirá:

- identidad verificada;
- TOTP o step-up equivalente;
- generación de clave local;
- aprobación desde un dispositivo ya autorizado, cuando exista;
- canal efímero;
- registro de evidencia.

Si se pierden todos los dispositivos, el usuario podrá crear una nueva época criptográfica, pero no recuperará automáticamente documentos protegidos con claves anteriores.

## 9. Almacenamiento y datos

Los documentos cifrados residirán en IPFS privado.

MySQL conservará:

- usuarios e identidades;
- credenciales;
- sesiones;
- dispositivos;
- documentos y versiones;
- transferencias;
- ACL;
- CIDs;
- sobres;
- firmas;
- evidencias;
- outbox;
- trabajos;
- estados de notificación;
- ledger interno.

IPFS no será fuente de identidad ni autorización. Conocer un CID no otorgará acceso en claro.

## 10. Ledger y blockchain

El ledger interno será append-only y verificable. Agrupará evidencias en bloques con hashes, raíz Merkle y firma.

La blockchain externa no recibirá:

- documentos;
- CIDs;
- NIF/NIE;
- emails;
- nombres de archivo;
- IP;
- certificados completos;
- hashes simples del documento;
- destinatarios.

Recibirá checkpoints agregados sin semántica personal directa.

## 11. Eliminación

La eliminación será una operación compuesta:

1. revocación de accesos;
2. destrucción de sobres y claves aplicables;
3. eliminación de relaciones off-chain;
4. unpin en todos los nodos IPFS;
5. garbage collection;
6. expiración en backups;
7. conservación de una evidencia mínima;
8. mantenimiento de la integridad estructural del ledger.

La blockchain no se reescribirá.

## 12. Arquitectura tecnológica

- PHP 8.5;
- CodeIgniter 4, última versión estable compatible;
- CodeIgniter Shield;
- MySQL;
- Nginx;
- PHP-FPM;
- IPFS privado;
- JavaScript y WebCrypto;
- OpenAPI en fase 2;
- Composer;
- PHPUnit;
- PHPStan o Psalm.

## 13. Seguridad

La seguridad se integrará en el ciclo de desarrollo:

- OpenSpec;
- TDD;
- OWASP ASVS;
- STRIDE y LINDDUN;
- pruebas criptográficas;
- SAST;
- DAST;
- auditoría de dependencias;
- secret scanning;
- pentest;
- hardening;
- auditoría de operaciones privilegiadas.

## 14. Modelo regulatorio

MARAChain integrará servicios de identidad, firma y sellado compatibles con eIDAS. No se presentará inicialmente como prestador cualificado.

La posible consideración de sus capacidades propias de entrega, archivo o ledger como servicio de confianza no cualificado deberá validarse jurídicamente.

El RGPD seguirá siendo aplicable a identidades, metadatos, registros, evidencias y relaciones, aunque el contenido permanezca cifrado.

## 15. Experiencia de usuario y frontend

MARAChain utilizará la plantilla comercial Alpino Bootstrap 4, variante horizontal, como base visual. La plantilla se integrará como vistas de CodeIgniter 4 y no como un conjunto de páginas HTML independientes.

La pantalla inicial después de una autenticación correcta será la bandeja de entrada documental:

```text
Autenticación correcta
        ↓
Sesión Shield
        ↓
/inbox
```

La metáfora visual será similar a una aplicación de correo:

- Inbox: transferencias recibidas.
- Outbox: transferencias enviadas.
- Nuevo envío: creación de una transferencia documental.
- Contactos: personas físicas y jurídicas con las que se intercambia documentación.
- Perfil: identidad verificada, seguridad, dispositivos, sesiones y preferencias.
- Evidencias: historial y exportación de pruebas técnicas.

La entidad mostrada en Inbox y Outbox será `DocumentTransfer`, no el documento aislado. Esto permite representar el estado específico de cada envío y destinatario.

Las pantallas base de Alpino serán:

- `horizontal/mail-inbox.html`;
- `horizontal/mail-single.html`;
- `horizontal/mail-compose.html`;
- `horizontal/contact.html`;
- `horizontal/profile.html`;
- `horizontal/form-upload.html`.

La selección del fichero utilizará el componente “File Upload Drag & Drop OR With Click & Choose”, basado en Dropzone. La carga automática quedará desactivada. El documento original será validado, hasheado, firmado cuando corresponda y cifrado antes de transmitirse.

El formulario de nuevo envío solicitará los datos de identificación y contacto del destinatario, el título y la motivación del envío. Los canales de contacto no sustituirán la identidad verificada ni concederán acceso por sí mismos.

La especificación completa se encuentra en `06_FRONTEND_DESIGN.md`.

## 15. Notificaciones globales

MARAChain gestionará una cuenta global corporativa de WhatsApp y una cuenta global corporativa de Telegram.

```text
Usuario remitente
        ↓
DocumentTransfer disponible
        ↓
Módulo Notifications
        ├── Email
        ├── Cuenta global WhatsApp
        └── Cuenta global Telegram
        ↓
Destinatario
```

El remitente no conectará sus cuentas personales ni aportará sesiones de mensajería. Los campos de WhatsApp y Telegram del formulario de envío identificarán exclusivamente el destino del aviso.

El mensaje se enviará bajo la identidad visible de MARAChain e indicará, dentro del contenido permitido, quién es el remitente documental.

Los canales no enviarán:

- el documento;
- el CID;
- claves o sobres;
- hashes documentales;
- NIF/NIE/CIF completos;
- tokens duraderos;
- descripciones sensibles;
- evidencias completas.

El destinatario deberá acceder a MARAChain y autenticarse para consultar el documento.

Los acuses técnicos de los canales no equivaldrán a acceso, lectura o aceptación documental.

Las credenciales globales serán secretos de infraestructura y se almacenarán fuera de `wwwroot`, Git, logs y ledger. La implementación concreta de cada adaptador se seleccionará mediante PoC.

## 17. Evolución

- identidad delegada;
- firma delegada;
- sellado de tiempo;
- validación longeva;
- representación empresarial;
- API REST;
- integraciones;
- DLT externa;
- planes y pagos;
- mayor disponibilidad;
- clientes auxiliares solo si fueran necesarios.

## 18. Conclusión

MARAChain se define como una plataforma documental centrada en identidad, confidencialidad, integridad y trazabilidad. Su arquitectura evita que el backend o los proveedores externos necesiten acceder al documento en claro y mantiene desacopladas identidad, firma, cifrado, almacenamiento y evidencia.
