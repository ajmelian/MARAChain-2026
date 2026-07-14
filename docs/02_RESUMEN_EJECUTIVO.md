# MARAChain — Resumen Ejecutivo

**Versión:** 1.1.1  
**Fecha:** 13 de julio de 2026  
**Estado:** Baseline ejecutiva aprobada  
**Clasificación:** Fuente de verdad

## 1. Visión

MARAChain es una plataforma SaaS para gestionar, transmitir y custodiar documentos de forma segura, trazable y verificable.

Su objetivo es que el intercambio documental no dependa únicamente de un email, un enlace o una cuenta con contraseña, sino de una identidad verificada, una autorización reforzada, una firma electrónica cuando proceda y una cadena de evidencias resistente a manipulaciones.

## 2. Problema

Las herramientas convencionales no demuestran de forma integrada:

- quién realizó la operación;
- quién era el destinatario;
- qué bytes exactos se enviaron;
- cuándo se firmó;
- cuándo quedó disponible;
- cuándo se descargó;
- si el contenido se alteró;
- qué evidencias pueden verificarse después.

## 3. Solución

MARAChain combinará:

- identidad verificada;
- autenticación multifactor;
- firma electrónica delegada;
- cifrado extremo a extremo;
- IPFS privado;
- ledger criptográfico;
- sellado de tiempo futuro;
- auditoría y exportación de evidencias.

El proveedor de firma recibirá exclusivamente el hash o digest necesario. No recibirá el documento.

## 4. Diferenciadores

### Confidencialidad

El documento se cifra en el navegador. Ni los administradores ni el backend ni los proveedores externos reciben el contenido en claro.

### Identidad

El MVP utilizará certificado FNMT y TOTP. La segunda fase delegará la identidad en FirmaProfesional, Cl@ve u otro proveedor compatible con eIDAS.

### Firma eficiente

La firma se realizará sobre digest. Esto evita límites de tamaño y reduce transferencia y latencia.

### Evidencia

Cada operación relevante generará un evento canónico e inmutable.

### Almacenamiento

El ciphertext se almacenará en una red IPFS privada.

### Blockchain

El ledger podrá anclar checkpoints agregados en una DLT externa sin publicar documentos, CIDs ni datos personales.

## 5. MVP

El MVP incluirá:

- acceso por certificado FNMT;
- TOTP;
- sesión Shield;
- documentos PDF;
- cifrado WebCrypto;
- envío individual;
- destinatarios enrolados;
- email;
- IPFS privado;
- ledger interno;
- evidencias;
- auditoría;
- revocación y expiración;
- eliminación controlada.

Quedarán fuera inicialmente:

- pagos;
- API pública;
- organizaciones verificadas;
- grupos masivos;
- dispositivos temporales;
- canales distintos del email.

## 6. Segunda fase

- identidad delegada;
- firma delegada;
- step-up del proveedor;
- sello de tiempo;
- API REST/OpenAPI;
- integraciones empresariales;
- representación;
- anclaje blockchain externo;
- planes comerciales.

## 7. Tecnología

MARAChain será una solución PHP-first:

- PHP 8.5;
- CodeIgniter 4;
- CodeIgniter Shield;
- MySQL;
- Nginx y PHP-FPM;
- IPFS privado;
- WebCrypto;
- monolito modular;
- plantilla Alpino original conservada fuera del runtime en `resources/frontend/alpino/original/`;
- vistas y assets adaptados desplegados únicamente desde `wwwroot`;
- OpenSpec y TDD.

## 8. Experiencia de usuario

La interfaz utilizará Alpino Bootstrap 4 en su variante horizontal. Tras autenticarse, el usuario accederá directamente a Inbox. Cada transferencia documental se presentará como un mensaje, con bandejas Inbox y Outbox, creación de nuevos envíos, contactos, evidencias y perfil.

La subida empleará el componente Drag & Drop o Click & Choose de Alpino, pero el fichero original no se enviará automáticamente. El navegador realizará validación, hashing, firma cuando proceda y cifrado antes de la transmisión.

## 9. Seguridad

No existirá clave maestra de recuperación.

La pérdida de todas las claves autorizadas puede hacer inaccesibles documentos anteriores.

La supresión combinará destrucción criptográfica, retirada de referencias, unpin, garbage collection y expiración de backups.

La blockchain conservará checkpoints, no contenido ni identificadores documentales directos.

## 10. Modelo de confianza

```text
Identidad externa
    +
Firma sobre digest
    +
Cifrado extremo a extremo
    +
IPFS privado
    +
Evidencias
    +
Ledger
    +
Sellado temporal
```

## 11. Posicionamiento regulatorio

MARAChain integrará proveedores compatibles con eIDAS. No se presentará inicialmente como prestador cualificado. La calificación exacta de sus servicios propios deberá validarse antes de realizar afirmaciones regulatorias.

## 12. Estado

La definición funcional y arquitectónica está suficientemente cerrada para redactar especificaciones OpenSpec, construir PoC y comenzar el desarrollo de módulos no bloqueados.

Las cuestiones restantes son validaciones técnicas, contractuales, operativas y jurídicas, no indefiniciones esenciales del producto.
