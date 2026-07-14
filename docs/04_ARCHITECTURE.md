# MARAChain — Arquitectura

**Versión:** 1.2.0  
**Fecha:** 14 de julio de 2026  
**Estado:** Arquitectura de referencia aceptada  
**Clasificación:** Fuente de verdad

## 1. Estilo arquitectónico

MARAChain será un monolito modular.

Se combinarán:

- MVC en presentación;
- casos de uso en aplicación;
- dominio independiente;
- arquitectura hexagonal en límites externos;
- DDD táctico en módulos críticos.

```text
Presentation -> Application -> Domain
Infrastructure -> Ports
Domain -> sin dependencia de framework
```

## 2. Contexto C4

```mermaid
flowchart LR
    U[Remitente / destinatario] --> C[Cliente web criptográfico]
    C --> M[MARAChain]
    M --> IDP[Proveedor de identidad]
    M --> SIG[Proveedor de firma]
    M --> TSA[Proveedor de sello]
    M --> SMTP[Email]
    M --> DLT[Blockchain externa futura]
    A[Administrador / auditor] --> M
```

## 3. Contenedores C4

```mermaid
flowchart TB
    B[Navegador + WebCrypto] -->|HTTPS / mTLS| N[Nginx]
    N --> P[PHP 8.5 + CodeIgniter 4 + Shield]
    P --> DB[(MySQL)]
    P --> O[Outbox / Queue]
    W[Workers Spark] --> DB
    W --> IPFS[IPFS privado]
    W --> SMTP[SMTP]
    W --> LED[Ledger interno]
    LED --> DLT[DLT externa futura]
```

## 4. Módulos y ownership

| Módulo | Responsabilidad |
|---|---|
| Identity | identidad interna, NIF/NIE, proveedores y credenciales |
| Authentication | FNMT, IdP, TOTP y nivel de garantía |
| Session | integración Shield y sesiones |
| Documents | documentos, versiones y manifiestos |
| Encryption | formatos, suites y sobres |
| Signatures | intención, proveedor y validación |
| Transfers | destinatarios, ACL y estados |
| Evidence | evidencias canonicalizadas |
| Ledger | bloques, Merkle, firmas y checkpoints |
| Storage | IPFS, pins, réplicas y reconciliación |
| Notifications | email y cuentas globales corporativas de WhatsApp y Telegram, con adaptadores sustituibles |
| Organizations | tenants y representación futura |
| Administration | operaciones privilegiadas auditadas |
| Billing | planes y cuotas futuras |
| Frontend | navegación, vistas CI4, componentes Alpino, formularios y orquestación del cliente |

## 5. Agregados

- `UserIdentity`.
- `Device`.
- `Organization`.
- `Document`.
- `DocumentTransfer`.
- `SignatureRequest`.
- `EvidenceChain`.
- `LedgerBlock`.
- `StorageObject`.

## 6. Zonas de confianza

```mermaid
flowchart LR
    Z1[Dispositivo usuario] --> Z2[Internet]
    Z2 --> Z3[Perímetro Nginx]
    Z3 --> Z4[Aplicación PHP]
    Z4 --> Z5[Datos MySQL/IPFS]
    Z4 --> Z6[Keystore/Criptografía]
    Z4 --> Z7[Proveedores externos]
    Z8[Administración] --> Z3
```

### Secretos por zona

- dispositivo: certificado FNMT, claves WebCrypto y TOTP;
- perímetro: clave TLS;
- aplicación: HMAC, cifrado de campos y credenciales;
- IPFS: `swarm.key`;
- ledger: clave Ed25519;
- release: clave de firma de artefactos.

Ninguna zona concentrará todos los secretos.

## 7. Flujo de autenticación fase 1

```mermaid
sequenceDiagram
    participant U as Usuario
    participant N as Nginx
    participant A as Authentication
    participant I as Identity
    participant S as Shield
    participant E as Evidence

    U->>N: HTTPS + certificado FNMT
    N->>N: Validar mTLS
    N->>A: Contexto saneado
    A->>A: Validar política X.509
    A->>I: NIF/NIE normalizado
    I-->>A: UserIdentity
    A->>U: Solicitar TOTP
    U->>A: Código TOTP
    A->>A: Validar TOTP
    A->>S: Crear sesión
    S-->>A: Sesión Shield
    A->>E: LoginSuccess
    A-->>U: Acceso
```

## 8. Flujo de autenticación fase 2

```mermaid
sequenceDiagram
    participant U as Usuario
    participant M as MARAChain
    participant P as IdP externo
    participant I as Identity
    participant S as Shield

    U->>M: Iniciar autenticación
    M->>P: Redirección o request
    P->>U: Ceremonia de identidad
    P-->>M: Aserción verificable
    M->>M: Validar issuer, audience y nonce
    M->>I: Resolver identidad
    I-->>M: UserIdentity
    M->>S: Crear sesión
    S-->>U: Sesión Shield
```

## 9. Firma y envío

```mermaid
sequenceDiagram
    participant U as Navegador
    participant M as MARAChain
    participant P as Proveedor firma
    participant Q as Queue
    participant I as IPFS
    participant E as Evidence
    participant L as Ledger

    U->>U: Hash documento
    U->>M: Solicitar contexto
    M-->>U: Manifiesto y nonce
    U->>U: Canonicalizar y calcular digest
    U->>M: Digest
    M->>P: Petición de firma
    P-->>M: Firma sobre digest
    M->>M: Validar firma
    U->>U: Cifrar documento
    U->>M: Ciphertext y sobres
    M->>Q: Operación pendiente
    Q->>I: Add, pin y verify
    I-->>Q: CID
    Q->>E: Append evidencia
    E->>L: Incorporar hash
    Q-->>M: Transfer AVAILABLE
```

## 10. Lectura

```mermaid
sequenceDiagram
    participant U as Destinatario
    participant M as MARAChain
    participant I as IPFS
    participant E as Evidence

    U->>M: Solicitar acceso
    M->>M: Validar sesión, ACL y estado
    M->>I: Recuperar ciphertext
    I-->>M: Ciphertext
    M-->>U: Ciphertext y sobre
    U->>U: Desencapsular DEK
    U->>U: Verificar y descifrar
    M->>E: DocumentAccessed o Downloaded
```

## 11. Cifrado

```text
Documento -> DEK -> AES-256-GCM -> Ciphertext
DEK -> Encapsulación por destinatario -> RecipientEnvelope
```

El backend no conocerá la DEK.

Formato inicial:

```json
{
  "format": "marachain-envelope",
  "version": 1,
  "contentCipher": "AES-256-GCM",
  "manifestHash": "sha256:...",
  "recipients": []
}
```

La encapsulación final se decidirá tras PoC.

## 12. Firma e identidad

Se mantendrán contratos independientes:

```text
IdentityProviderInterface
SignatureProviderInterface
TimestampProviderInterface
```

Un proveedor podrá implementar varios contratos, pero el dominio no dependerá de él.

## 13. Persistencia

Reglas:

- repositorio por agregado;
- no acceso directo a tablas ajenas;
- migraciones inmutables;
- PII cifrada;
- HMAC para búsquedas deterministas;
- evidencias y ledger append-only;
- estados mutables separados.

## 14. Eventos internos

Se utilizarán eventos de dominio y outbox.

Campos comunes:

- `eventId`;
- `eventType`;
- `schemaVersion`;
- `occurredAt`;
- `aggregateId`;
- `correlationId`;
- `causationId`;
- payload mínimo.

## 15. Ledger sustituible

```php
interface LedgerAnchorInterface
{
    public function anchor(
        LedgerCheckpoint $checkpoint
    ): AnchorReceipt;

    public function verify(
        AnchorReceipt $receipt
    ): VerificationResult;

    public function status(
        AnchorId $id
    ): AnchorStatus;
}
```

El ledger interno siempre existirá.

La blockchain externa recibirá raíces y checkpoints, no datos documentales directos.

## 16. Multitenencia

Preparación desde el MVP:

- `tenant_id` nullable;
- contexto tenant derivado de membresía;
- repositorios tenant-aware;
- pruebas de aislamiento.

La representación empresarial completa será posterior.

## 17. Despliegue

Topología inicial:

```mermaid
flowchart TB
    A[Servidor aplicación] --> DB[(MySQL)]
    A --> I1[IPFS nodo 1]
    I1 <--> I2[IPFS nodo 2]
    I1 <--> I3[IPFS nodo 3]
    V[Verificador ledger] --> DB
    M[Monitorización] --> A
    M --> DB
    M --> I1
```

Rutas:

- `/var/www/marachain/current/public`;
- `/etc/marachain`;
- `/var/lib/marachain`;
- `/var/log/marachain`;
- `/etc/marachain/keystore`.

## 18. Alta disponibilidad

- PHP stateless;
- sesiones en MySQL inicialmente;
- workers escalables;
- IPFS replicado;
- ledger con líder o lock;
- backups MySQL;
- verificador independiente.

## 19. Supply chain

- lockfiles;
- SBOM;
- artefactos firmados;
- dependencias locales;
- sin CDN crítico;
- revisión de dos personas;
- actualizaciones firmadas;
- secret scanning.

## 20. Amenazas

### Activos

- identidades;
- documentos;
- DEK;
- claves;
- TOTP;
- sesiones;
- firmas;
- evidencias;
- ledger;
- backups;
- releases.

### Atacantes

- tercero remoto;
- usuario fraudulento;
- administrador malicioso;
- operador comprometido;
- proveedor externo;
- desarrollador o CI comprometido;
- malware local.

### Riesgos residuales

- pérdida irreversible;
- copias descargadas no revocables;
- metadatos observables;
- malware local;
- JavaScript manipulado;
- denegación de servicio;
- ausencia inicial de validación longeva.

## 21. Arquitectura de notificaciones globales

### 21.1. Contexto

```mermaid
flowchart LR
    T[DocumentTransfer AVAILABLE] --> O[Outbox de notificaciones]
    O --> W[Worker PHP]
    W --> E[Proveedor Email]
    W --> WA[Cuenta global WhatsApp]
    W --> TG[Cuenta global Telegram]
    E --> R[Destinatario]
    WA --> R
    TG --> R
```

Las cuentas pertenecen a MARAChain. Los usuarios remitentes no aportan sesiones ni credenciales de mensajería.

### 21.2. Límites de confianza

```text
Aplicación PHP
    ↓ referencia opaca
Secretos de infraestructura
    ├── sesión global WhatsApp
    └── sesión o credencial global Telegram
```

Las credenciales se mantienen fuera del webroot y se separan por entorno.

### 21.3. Contratos

```text
Notifications Domain
        ↓
NotificationProviderInterface
        ├── Email adapter
        ├── Global WhatsApp adapter
        └── Global Telegram adapter
```

El dominio no conocerá el SDK o protocolo concreto.

### 21.4. Semántica

- El mensaje identifica a MARAChain como emisor técnico.
- El contenido puede indicar quién es el remitente documental.
- La dirección WhatsApp o Telegram identifica el destino del aviso.
- Un acuse del canal no equivale a acceso, lectura ni aceptación.
- Los canales no conceden acceso documental.
- El documento nunca se transmite por estos canales.

### 21.5. Resiliencia

- outbox transaccional;
- reintentos con backoff;
- idempotencia;
- dead-letter;
- circuit breaker por proveedor;
- health checks;
- fallback por email;
- desactivación inmediata de un canal degradado.

## 22. Arquitectura frontend

### 22.1. Capas

```text
Vistas CodeIgniter 4
        ↓
Componentes y controladores UI
        ↓
Servicios JavaScript de aplicación
        ↓
Servicios criptográficos / Web Workers
        ↓
WebCrypto
```

Alpino será una base visual. No contendrá reglas de dominio ni lógica criptográfica.

### 22.2. Separación entre fuente comercial y runtime

```text
Plantilla original y documentación
resources/frontend/alpino/original/
        ↓ adaptación y saneamiento
Vistas CodeIgniter 4
wwwroot/app/Views/
        +
Assets seleccionados
wwwroot/public/assets/alpino/
        +
Lógica propia
wwwroot/public/assets/js/
```

`resources/frontend/alpino/` no formará parte del artefacto desplegable. Los HTML originales no serán servidos por Nginx ni accesibles desde el navegador.

La separación evita:

- desplegar demos o documentación;
- exponer componentes no utilizados;
- mezclar código comercial con lógica propia;
- incorporar plugins sin inventario;
- dificultar la actualización o sustitución de dependencias.

### 22.3. Navegación

```text
/inbox            -> transferencias recibidas
/outbox           -> transferencias enviadas
/transfers/new    -> nuevo envío
/transfers/{id}   -> detalle
/contacts         -> contactos
/evidence         -> evidencias
/profile          -> perfil
/settings         -> configuración
```

La ruta posterior a la autenticación será `/inbox`.

### 22.4. Pantallas Alpino de referencia

- Inbox: `mail-inbox.html`.
- Detalle: `mail-single.html`.
- Nuevo envío: `mail-compose.html`.
- Contactos: `contact.html`.
- Perfil: `profile.html`.
- Selector documental: `form-upload.html` y Dropzone.

Mapeo de referencia:

```text
mail-inbox.html  -> app/Views/inbox/index.php
mail-single.html -> app/Views/transfers/show.php
mail-compose.html -> app/Views/transfers/create.php
contact.html -> app/Views/contacts/index.php
profile.html -> app/Views/profile/show.php
form-upload.html -> componente integrado en transfers/create.php
```

### 22.5. Límite de confianza del cliente

El navegador es responsable del hashing, cifrado, gestión temporal de claves y descifrado. La plantilla no ejecutará directamente estas operaciones: invocará servicios JavaScript propios y versionados.

El fichero original no cruzará el límite del navegador antes de cifrarse.

## 23. ADR obligatorios

- lenguaje y framework;
- monolito modular;
- Shield;
- identidad por fases;
- firma por digest;
- cifrado WebCrypto;
- encapsulación;
- multidispositivo;
- ausencia de recuperación;
- IPFS;
- ledger;
- blockchain externa;
- multitenencia;
- cola MySQL;
- API fase 2;
- eliminación y crypto-erasure;
- adopción y saneamiento de Alpino Horizontal;
- flujo de subida Dropzone sin auto-upload;
- arquitectura JavaScript y Web Workers;
- modelo visual Inbox/Outbox basado en `DocumentTransfer`.
