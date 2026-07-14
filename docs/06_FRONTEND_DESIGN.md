# MARAChain — Diseño del Frontend

**Versión:** 1.2.0  
**Fecha:** 14 de julio de 2026  
**Estado:** Frontend Baseline aprobada  
**Clasificación:** Fuente de verdad

## 1. Objeto

Este documento define la fuente de verdad para el diseño, estructura, navegación, comportamiento, formularios, componentes, accesibilidad y seguridad del frontend de MARAChain.

Su alcance comprende el MVP y la evolución inmediata de la aplicación web. Las reglas de dominio, autenticación, cifrado, firma y almacenamiento se describen en los documentos técnicos correspondientes; este documento establece cómo se presentan y orquestan desde la interfaz.

## 2. Principios de experiencia de usuario

1. La interfaz debe ser simple y reconocible.
2. La metáfora principal será una aplicación de correo aplicada a transferencias documentales.
3. El usuario debe distinguir claramente documentos recibidos, documentos enviados y nuevos envíos.
4. Las operaciones criptográficas deben ser transparentes, pero sus estados deben mostrarse con precisión.
5. La interfaz no debe utilizar terminología jurídica o criptográfica innecesaria.
6. Las acciones disponibles dependerán del estado de la transferencia y de los permisos.
7. Los colores nunca serán el único medio para comunicar un estado.
8. El frontend no sustituirá las validaciones del backend.
9. La sencillez no podrá reducir las garantías de seguridad.

## 3. Plantilla visual

### 3.1. Base seleccionada

- Plantilla: **Alpino — Bootstrap 4 Admin WebApp Template**.
- Variante: **Horizontal**.
- Origen: licencia comercial adquirida previamente para el proyecto.
- Integración: vistas de CodeIgniter 4.

La plantilla será una base visual y de componentes. No se utilizará como arquitectura de aplicación ni como fuente de reglas de negocio.

La licencia y el ZIP original deberán conservarse en el repositorio privado o en almacenamiento interno autorizado. La plantilla no se redistribuirá fuera del ámbito permitido por su licencia.

La plantilla descomprimida y su documentación se conservarán en:

```text
resources/frontend/alpino/original/
```

El directorio padre incluirá:

```text
resources/frontend/alpino/
├── original/
├── LICENSE_INFO.md
└── VERSION.md
```

`LICENSE_INFO.md` registrará el producto, origen de la licencia, uso autorizado y prohibición de redistribución. `VERSION.md` registrará la versión, fecha de adquisición o extracción y checksum del paquete original.

### 3.2. Pantallas de referencia

| Función MARAChain | Archivo Alpino |
|---|---|
| Inbox | `horizontal/mail-inbox.html` |
| Detalle de transferencia | `horizontal/mail-single.html` |
| Nuevo envío | `horizontal/mail-compose.html` |
| Contactos | `horizontal/contact.html` |
| Perfil | `horizontal/profile.html` |
| Selección y subida | `horizontal/form-upload.html` |
| Estilos de bandeja | `horizontal/assets/css/inbox.css` |
| Plugin de selección | `horizontal/assets/plugins/dropzone/` |

### 3.3. Política de saneamiento

No se copiará indiscriminadamente todo el template.

Antes de incorporar un asset se deberá:

- justificar su uso;
- inventariar versión y licencia;
- comprobar vulnerabilidades conocidas;
- eliminar contenido demo;
- eliminar enlaces externos no aprobados;
- evitar CDNs;
- retirar scripts inline cuando sea viable;
- sustituir plugins sin mantenimiento o vulnerables;
- conservar únicamente fuentes, iconos, CSS y JavaScript necesarios.

La apariencia de Alpino podrá mantenerse aunque una dependencia interna deba actualizarse o sustituirse.

## 4. Modelo mental y mapeo de dominio

La interfaz representará cada transferencia documental como si fuera un mensaje.

```text
Inbox  -> DocumentTransfer donde el usuario es destinatario
Outbox -> DocumentTransfer donde el usuario es remitente
```

La bandeja no listará directamente `Document`, porque un mismo documento puede tener varias transferencias, destinatarios y estados.

Cada fila deberá representar como mínimo:

- remitente o destinatario;
- título del documento;
- resumen de la motivación;
- fecha y hora;
- estado;
- indicador de firma;
- nivel de seguridad;
- expiración;
- indicador de acceso o descarga;
- incidencias relevantes.

## 5. Navegación

### 5.1. Navegación principal

```text
MARAChain
├── Inbox
├── Outbox
├── Nuevo envío
├── Contactos
├── Evidencias
├── Actividad
├── Perfil
└── Configuración
```

### 5.2. Navegación administrativa

Visible únicamente para roles autorizados:

```text
Administración
├── Usuarios
├── Auditoría
├── Ledger
├── IPFS
├── Colas
└── Estado del sistema
```

### 5.3. Rutas iniciales

```text
/inbox
/outbox
/transfers/new
/transfers/{id}
/contacts
/evidence
/activity
/profile
/settings
```

Después de una autenticación correcta y de crear la sesión Shield, la aplicación redirigirá a:

```text
/inbox
```

No habrá un dashboard estadístico como pantalla inicial del usuario estándar.

## 6. Pantalla Inbox

La pantalla se basará en `mail-inbox.html`.

### 6.1. Contenido

- transferencias recibidas;
- contador de pendientes;
- filtros;
- búsqueda;
- ordenación;
- paginación;
- indicadores de estado;
- acciones contextuales.

### 6.2. Filtros iniciales

- todas;
- nuevas;
- disponibles;
- accedidas;
- descargadas;
- próximas a expirar;
- expiradas;
- revocadas;
- con incidencia.

### 6.3. Representación de estado

| Estado | Etiqueta visible |
|---|---|
| `AVAILABLE` | Disponible |
| `ACCESSED` | Accedido |
| `DOWNLOADED` | Descargado |
| `ACCEPTED` | Aceptado |
| `REJECTED` | Rechazado |
| `EXPIRED` | Expirado |
| `REVOKED` | Revocado |
| `FAILED` | Incidencia |

Cada estado utilizará texto, icono y, opcionalmente, color.

## 7. Pantalla Outbox

La pantalla reutilizará el patrón de Inbox y mostrará transferencias enviadas.

Cada fila podrá mostrar:

- destinatario;
- razón social o nombre;
- “A la atención de” cuando proceda;
- título;
- fecha;
- estado por destinatario;
- firma;
- expiración;
- acceso y descarga;
- acciones permitidas.

Las acciones podrán incluir:

- consultar detalle;
- consultar evidencias;
- revocar accesos futuros;
- reenviar una notificación;
- duplicar como nuevo envío;
- exportar paquete probatorio.

## 8. Pantalla de detalle

La pantalla se basará en `mail-single.html`.

Mostrará:

- remitente;
- destinatario o destinatarios;
- título;
- descripción o motivación;
- fecha de creación y envío;
- estado;
- firma y proveedor;
- sello de tiempo cuando exista;
- expiración;
- historial de eventos;
- evidencias;
- documento cifrado disponible;
- acciones permitidas.

El documento se descifrará únicamente en el cliente autorizado.

## 9. Nuevo envío

La pantalla se basará en `mail-compose.html` y se organizará en cinco bloques:

```text
1. Destinatario
2. Canales de contacto
3. Dirección postal
4. Documento
5. Seguridad y envío
```

## 10. Datos del destinatario

### 10.1. Tipo de destinatario

Control obligatorio:

```text
○ Persona física
○ Persona jurídica
```

Este valor modificará etiquetas y validaciones.

### 10.2. Campos

| Campo | Control HTML | Requisito | Validación principal |
|---|---|---|---|
| Nombre y apellidos / Razón social | `input type="text"` | Obligatorio | Longitud y caracteres admitidos |
| A la atención de | `input type="text"` | Obligatorio solo para persona jurídica | Nombre de persona, departamento o unidad |
| NIF/NIE o CIF | `input type="text"` | Recomendado | Normalización y validación española |
| Email principal | `input type="email"` | Obligatorio | Email válido y normalizado |
| Otros emails | `textarea` | Recomendado | Lista de emails válidos y deduplicados |
| Cuenta de Telegram | `input type="text"` | Recomendado | Normalización con o sin `@` |
| Cuenta de WhatsApp | `input type="tel"` | Recomendado | Normalización internacional |
| Teléfono móvil para SMS | `input type="tel"` | Recomendado | Normalización internacional |
| Domicilio | `textarea` | Recomendado | Longitud y normalización |
| Código postal | `input type="text"` | Recomendado | Cinco dígitos para España |
| Provincia | `input type="text"` | Recomendado | Provincia normalizada |
| Título del documento | `input type="text"` | Obligatorio | Longitud y texto no vacío |
| Descripción o motivación | `textarea` | Obligatorio | Longitud y texto no vacío |

### 10.3. Reglas específicas

- El código postal se tratará como texto, no como entero, para conservar ceros iniciales.
- Se utilizará `inputmode="numeric"`, `maxlength="5"` y un patrón de cinco dígitos para España.
- Los teléfonos se normalizarán preferentemente en formato E.164.
- Los emails adicionales podrán introducirse uno por línea o separados por coma o punto y coma.
- Los emails se normalizarán, validarán y deduplicarán.
- La cuenta de Telegram del destinatario se almacenará en forma normalizada.
- NIF/NIE/CIF y canales sensibles se cifrarán en persistencia.
- Los datos personales no se enviarán al ledger ni a la blockchain externa.

### 10.4. Contacto frente a identidad

Los siguientes valores son canales de contacto:

- email principal;
- emails adicionales;
- Telegram;
- WhatsApp;
- SMS.

No son por sí mismos una identidad verificada y no conceden acceso documental.

Los valores de WhatsApp y Telegram representan exclusivamente la dirección del destinatario. No son cuentas emisoras, credenciales ni sesiones.

Las cuentas emisoras serán globales y propiedad de MARAChain:

```text
Cuenta global WhatsApp MARAChain -> número WhatsApp del destinatario
Cuenta global Telegram MARAChain -> cuenta Telegram del destinatario
```

El usuario remitente nunca introducirá:

- cookies;
- tokens;
- claves;
- códigos QR;
- archivos de sesión;
- credenciales de WhatsApp o Telegram.

El acceso se asociará a una `UserIdentity` autenticada y autorizada.

## 11. Selección y subida del documento

### 11.1. Componente

Se utilizará el componente de Alpino:

> File Upload Drag & Drop OR With Click & Choose

El componente está basado en Dropzone y se encuentra en `horizontal/form-upload.html`.

### 11.2. Comportamiento

El usuario podrá:

- arrastrar y soltar;
- hacer clic y seleccionar;
- visualizar nombre, tamaño y tipo;
- eliminar la selección;
- cancelar el proceso;
- consultar progreso y errores.

### 11.3. Prohibición de auto-upload

Dropzone se configurará para no enviar automáticamente el fichero original.

El flujo será:

```text
Selección
    ↓
Validación local
    ↓
Hashing
    ↓
Construcción del manifiesto
    ↓
Firma del digest, cuando proceda
    ↓
Cifrado WebCrypto
    ↓
Subida controlada del ciphertext
```

### 11.4. Fases de progreso

La interfaz distinguirá:

1. Validando.
2. Calculando huella.
3. Solicitando firma.
4. Cifrando.
5. Transmitiendo.
6. Verificando almacenamiento.
7. Registrando evidencias.
8. Envío completado.

No se utilizará una única barra de “subida” que oculte estas fases.

### 11.5. Alcance MVP

El MVP gestionará un documento principal por transferencia.

La compatibilidad del componente con múltiples ficheros no implica que la función esté aprobada. El envío múltiple requerirá una especificación OpenSpec y un modelo explícito de manifiesto, firma y sobres por archivo.

## 12. Contactos

La pantalla se basará en `contact.html`.

Un contacto podrá representar:

- persona física;
- persona jurídica;
- identidad pendiente;
- identidad verificada.

La vista mostrará únicamente los datos necesarios y ocultará parcialmente identificadores fiscales.

Seleccionar un contacto rellenará el formulario de nuevo envío, pero no omitirá la validación de identidad ni de autorización.

## 13. Perfil de usuario

La pantalla se basará en `profile.html` y utilizará la ruta `/profile`.

### 13.1. Secciones

```text
Perfil
├── Identidad
├── Seguridad
├── Contacto
├── Dispositivos
├── Sesiones
├── Preferencias
└── Actividad
```

### 13.2. Datos visibles

- nombre y apellidos;
- NIF/NIE parcialmente oculto;
- proveedor de identidad;
- nivel de garantía;
- fecha de última verificación;
- email;
- estado TOTP;
- última autenticación;
- dispositivos autorizados;
- sesiones activas;
- preferencias de notificación;
- estado público de los canales globales disponibles;
- actividad de seguridad relevante.

### 13.3. Datos prohibidos

No se mostrarán:

- secreto TOTP;
- claves privadas;
- claves WebCrypto;
- DEK;
- sobres criptográficos;
- tokens;
- identificadores internos completos del proveedor;
- NIF/NIE completo sin necesidad justificada.

## 14. Notificaciones globales en la interfaz

El formulario mostrará WhatsApp y Telegram como datos opcionales del destinatario.

No existirá una pantalla para que el remitente conecte cuentas personales.

La interfaz podrá mostrar:

- canal disponible;
- canal no configurado;
- dirección inválida;
- aviso en cola;
- aviso enviado;
- fallo de aviso;
- fallback por email.

Las etiquetas deberán dejar claro que el mensaje será enviado por MARAChain:

```text
Avisar por WhatsApp desde MARAChain
Avisar por Telegram desde MARAChain
```

No se utilizarán expresiones como “conectar mi WhatsApp” o “usar mi Telegram”.

## 15. Componentes conceptuales

- `TransferRow`.
- `TransferStatusBadge`.
- `IdentityBadge`.
- `SignatureBadge`.
- `SecurityLevelBadge`.
- `ExpirationIndicator`.
- `EvidenceTimeline`.
- `RecipientTypeSelector`.
- `RecipientForm`.
- `ContactChannelFields`.
- `DocumentDropzone`.
- `ProcessingProgress`.
- `TotpStepUpModal`.
- `DeviceApprovalModal`.
- `EmptyState`.
- `ErrorState`.

Aunque se implementen mediante vistas PHP y JavaScript, se tratarán como componentes con responsabilidades acotadas.

## 16. Integración con CodeIgniter 4

### 16.1. Vistas

```text
wwwroot/app/Views/
├── layouts/
│   ├── main.php
│   ├── auth.php
│   └── partials/
│       ├── header.php
│       ├── navigation.php
│       ├── footer.php
│       ├── alerts.php
│       └── scripts.php
├── inbox/
│   ├── index.php
│   └── _transfer_row.php
├── outbox/
│   └── index.php
├── transfers/
│   ├── create.php
│   ├── show.php
│   └── evidence.php
├── contacts/
│   └── index.php
├── profile/
│   └── show.php
├── evidence/
├── settings/
└── administration/
```

### 16.2. Assets

```text
wwwroot/public/assets/
├── alpino/
│   ├── css/
│   ├── fonts/
│   ├── icons/
│   └── plugins/
├── js/
│   ├── app/
│   ├── transfers/
│   │   ├── recipient-form.js
│   │   ├── file-selector.js
│   │   ├── file-validation.js
│   │   ├── upload-controller.js
│   │   └── upload-progress.js
│   ├── crypto/
│   │   ├── hashing.js
│   │   ├── encryption.js
│   │   ├── key-management.js
│   │   └── envelope.js
│   └── workers/
│       ├── hashing.worker.js
│       └── encryption.worker.js
└── css/
    └── marachain.css
```

La lógica criptográfica no se incorporará a scripts de la plantilla.

### 16.3. Mapeo entre fuentes Alpino y vistas

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
→ componente de selección integrado en wwwroot/app/Views/transfers/create.php
```

Los HTML originales no se servirán directamente y no contendrán rutas de aplicación activas.

### 16.4. Política de copia y despliegue

Solo se copiarán a `wwwroot/public/assets/alpino/` los recursos necesarios, inventariados y revisados.

Se excluirán del despliegue:

```text
resources/frontend/alpino/
```

El artefacto productivo contendrá exclusivamente:

```text
wwwroot/app/Views/
wwwroot/public/assets/alpino/
wwwroot/public/assets/js/
wwwroot/public/assets/css/
```

La pipeline deberá fallar si detecta documentación, demos, páginas HTML originales o plugins no autorizados dentro del artefacto productivo.

## 17. Seguridad frontend

### 17.1. Almacenamiento local

- No almacenar documentos en `localStorage` o `sessionStorage`.
- No almacenar claves privadas exportables en texto claro.
- IndexedDB solo se utilizará después de definir el modelo de protección y las PoC.
- El material temporal se liberará después de completar o cancelar la operación.

### 17.2. Red y contenido

- HTTPS obligatorio.
- CSP estricta.
- HSTS.
- Sin scripts críticos desde CDN.
- Sin `eval`.
- Evitar `unsafe-inline`.
- Protección CSRF.
- Escape contextual de salida.
- Saneamiento de contenido dinámico.

### 17.3. Logging

No registrar en consola, telemetría o errores:

- contenido del documento;
- hash documental completo salvo necesidad técnica controlada;
- DEK;
- claves privadas;
- secreto TOTP;
- NIF/NIE/CIF completo;
- tokens;
- respuestas sensibles de proveedores.

### 17.4. Dependencias heredadas

Bootstrap 4, jQuery, Dropzone y el resto de plugins del ZIP deberán inventariarse y auditarse.

El hecho de que un componente forme parte de Alpino no lo aprueba automáticamente para producción.

## 18. Accesibilidad

Objetivo mínimo: WCAG 2.1 AA o estándar vigente aprobado por el proyecto.

Requisitos:

- navegación completa por teclado;
- foco visible;
- etiquetas asociadas a controles;
- errores vinculados a los campos;
- contraste suficiente;
- iconos acompañados de texto o nombre accesible;
- estados no dependientes solo del color;
- zonas Dropzone utilizables mediante teclado;
- mensajes de progreso anunciables;
- tablas y bandejas adaptables;
- modales con gestión correcta del foco.

## 19. Responsive

La interfaz deberá funcionar en:

- escritorio;
- portátil;
- tableta;
- móvil.

Las operaciones de selección, cifrado y descarga deberán validar memoria y capacidades del dispositivo. Un dispositivo no compatible deberá mostrar un mensaje explícito y no iniciar un flujo inseguro.

## 20. Estados vacíos y errores

Se definirán vistas específicas para:

- Inbox vacío;
- Outbox vacío;
- contactos vacíos;
- documento no encontrado;
- transferencia expirada;
- transferencia revocada;
- firma rechazada;
- firma inválida;
- fallo IPFS;
- sesión expirada;
- dispositivo no autorizado;
- clave no disponible;
- navegador no compatible;
- operación parcialmente completada.

Los errores técnicos no mostrarán stack traces, secretos o respuestas internas.

## 21. Terminología

| Término Alpino | Término MARAChain |
|---|---|
| Mail | Transferencia o documento, según contexto |
| Inbox | Inbox / Recibidos |
| Sent Mail | Outbox / Enviados |
| Compose | Nuevo envío |
| Attachment | Documento |
| Send | Firmar y enviar, o Enviar |
| Read | Accedido |
| Trash | No se reutiliza automáticamente; se distinguirá entre revocado, expirado y eliminado |

No se afirmará “leído” cuando solo se pueda probar acceso o descarga.

## 22. Criterios de aceptación

- Después de autenticarse, el usuario llega a Inbox.
- Inbox lista transferencias recibidas.
- Outbox lista transferencias enviadas.
- La vista de perfil utiliza el patrón de Alpino `profile.html`.
- Nuevo envío contiene todos los campos obligatorios aprobados.
- “A la atención de” solo es obligatorio para persona jurídica.
- El código postal conserva ceros iniciales.
- Los emails adicionales se validan individualmente y se deduplican.
- Los teléfonos se normalizan.
- Los canales de contacto no conceden acceso.
- WhatsApp y Telegram se presentan como avisos enviados desde cuentas globales de MARAChain.
- El remitente no puede introducir ni cargar sesiones o credenciales de mensajería.
- Los acuses de mensajería no se presentan como lectura o aceptación documental.
- Dropzone admite arrastrar y soltar y selección manual.
- Dropzone no envía automáticamente el fichero original.
- El fichero original no sale del navegador antes de cifrarse.
- El progreso diferencia validación, hash, firma, cifrado y transmisión.
- No se persisten documentos en `localStorage`.
- No quedan enlaces demo ni CDNs no aprobados.
- Los estados no dependen exclusivamente del color.
- La navegación principal es utilizable por teclado.
- Las acciones se ocultan o deshabilitan según estado y permisos.

## 23. Aspectos pendientes de validación

- tamaño máximo de documento;
- formatos adicionales a PDF;
- comportamiento definitivo en móviles con memoria limitada;
- mecanismo de hashing incremental;
- estrategia exacta de persistencia de claves;
- SDK, protocolo y activación real de las cuentas globales de Telegram, WhatsApp y SMS;
- branding definitivo, logotipo y paleta MARAChain;
- inventario de versiones y vulnerabilidades de los assets Alpino;
- soporte de varios documentos por transferencia;
- pruebas de accesibilidad y usabilidad con usuarios.
