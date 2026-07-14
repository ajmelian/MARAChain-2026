# MARAChain — Fuente de Verdad

**Versión:** 1.1.1  
**Fecha:** 13 de julio de 2026  
**Estado:** Baseline aprobada  
**Clasificación:** Fuente de verdad del proyecto

## 1. Finalidad

Este directorio consolida la definición vigente de MARAChain y debe utilizarse como referencia para especificaciones OpenSpec, decisiones ADR, desarrollo TDD, revisiones de seguridad, validaciones jurídicas, planificación y auditoría.

En caso de contradicción, prevalece este orden:

1. ADR aprobados.
2. Especificaciones OpenSpec aprobadas.
3. Documentos de esta fuente de verdad.
4. Documentación técnica derivada.
5. Documentación histórica.

## 2. Documentos incluidos

| Documento | Objeto |
|---|---|
| `01_RESUMEN_COMPLETO.md` | Definición consolidada y detallada del proyecto. |
| `02_RESUMEN_EJECUTIVO.md` | Síntesis para dirección, clientes, colaboradores e interlocutores externos. |
| `03_PROYECTO_TECNICO.md` | Baseline tecnológica, de seguridad, calidad, operación y despliegue. |
| `04_ARCHITECTURE.md` | Arquitectura de referencia, módulos, flujos y límites de confianza. |
| `05_CASOS_DE_USO.md` | Catálogo funcional del MVP y de la segunda fase. |
| `06_FRONTEND_DESIGN.md` | Baseline de diseño frontend, navegación, formularios, componentes y seguridad cliente. |

## 3. Decisiones rectoras

- MARAChain es un SaaS de gestión, transmisión y custodia segura de documentos.
- El backend se desarrollará principalmente con PHP 8.5.
- Se utilizará la última versión estable compatible de CodeIgniter 4.
- CodeIgniter Shield será el sistema oficial de usuarios, autenticación interna, autorización y sesiones.
- La arquitectura será un monolito modular.
- El frontend utilizará la plantilla comercial Alpino Bootstrap 4, variante horizontal.
- La vista inicial después de la autenticación será Inbox; no existirá un dashboard estadístico como pantalla principal del usuario estándar.
- Cada documento se representará en la interfaz mediante su `DocumentTransfer`, siguiendo una metáfora visual de correo con Inbox y Outbox.
- La vista de perfil se basará en `horizontal/profile.html`.
- La selección de documentos se basará en el componente Dropzone de `horizontal/form-upload.html`, con arrastrar y soltar o selección manual.
- La plantilla Alpino descomprimida y su documentación se conservarán en `resources/frontend/alpino/original/` como material fuente no desplegable.
- Las vistas adaptadas residirán en `wwwroot/app/Views/`; los assets seleccionados y saneados, en `wwwroot/public/assets/alpino/`; y la lógica JavaScript propia de MARAChain, en `wwwroot/public/assets/js/`.
- El proceso de despliegue excluirá `resources/frontend/alpino/` y solo publicará las vistas y assets expresamente incorporados a `wwwroot`.
- El componente de subida no enviará automáticamente el fichero original: primero se validará, hasheará, firmará cuando corresponda y cifrará localmente.
- El desarrollo se organizará mediante SDD, OpenSpec, TDD y documentación trazable.
- En la primera fase, la autenticación será mediante certificado FNMT, TOTP y sesión Shield.
- En la segunda fase, la identidad se delegará completamente en un tercero de confianza compatible con eIDAS, previsiblemente FirmaProfesional o Cl@ve.
- La firma electrónica también podrá delegarse en terceros.
- El proveedor de firma recibirá exclusivamente un hash o digest; nunca el documento en claro.
- El cifrado extremo a extremo se ejecutará prioritariamente en el navegador mediante WebCrypto.
- MARA Vault será opcional y solo se desarrollará si las PoC demuestran una limitación insalvable de la solución web.
- El backend no recibirá documentos ni claves documentales en claro.
- Los documentos cifrados se almacenarán en una red IPFS privada.
- MySQL almacenará metadatos, relaciones, ACL, estados, sobres criptográficos y evidencias operativas.
- El MVP dispondrá de un ledger interno implementado en PHP.
- La versión comercial podrá anclar checkpoints en una blockchain o DLT externa.
- La blockchain no almacenará documentos, CIDs, NIF/NIE ni datos personales directos.
- El modelo `only-4-your-eyes` no tendrá clave maestra ni recuperación administrativa.
- La pérdida de todo el material criptográfico autorizado puede provocar pérdida irreversible de acceso.
- La API REST será una segunda fase y se definirá mediante OpenAPI.
- La ICO, el token MARA y la tokenomics quedan archivados y excluidos del producto vigente.

## 4. Aspectos pendientes de validación

No impiden usar esta documentación, pero requieren PoC, ADR, contrato o revisión jurídica antes de producción:

- mecanismo asimétrico definitivo de encapsulación de claves;
- persistencia segura y portable de claves WebCrypto;
- ceremonia multidispositivo;
- compatibilidad real en navegadores y sistemas operativos soportados;
- APIs y condiciones contractuales de FirmaProfesional y Cl@ve/FIRe;
- formato final de firma y perfil de validación longeva;
- proveedor de sello de tiempo;
- matriz jurídica de retención;
- calificación regulatoria de las capacidades propias de MARAChain;
- SLA, RPO y RTO contractuales;
- proveedor y topología final de infraestructura;
- inventario, actualización o sustitución de dependencias heredadas de Alpino, Bootstrap 4, jQuery y Dropzone;
- límites definitivos de tamaño, formatos y número de documentos por transferencia;
- activación contractual y técnica de los canales Telegram, WhatsApp y SMS.

## 5. Gestión de cambios

Todo cambio que afecte a identidad, autenticación, firma, cifrado, recuperación, almacenamiento, ledger, retención o eliminación deberá:

1. originarse en una propuesta OpenSpec;
2. incluir análisis de impacto funcional, técnico y de seguridad;
3. incluir pruebas;
4. actualizar los ADR afectados;
5. actualizar esta fuente de verdad;
6. registrarse en `CHANGELOG.md`;
7. incrementar la versión conforme a la política del proyecto.

## 6. Terminología permitida

Hasta disponer de validación jurídica específica se utilizarán expresiones como:

- autenticación mediante certificado o proveedor de identidad;
- firma electrónica prestada por un tercero;
- sello de tiempo prestado por un tercero;
- cifrado extremo a extremo;
- transmisión segura;
- evidencia técnica verificable;
- ledger criptográfico interno;
- anclaje en DLT o blockchain externa.

No se utilizarán sin validación jurídica previa:

- prestador cualificado de servicios de confianza;
- entrega electrónica certificada cualificada;
- firma cualificada, salvo que el servicio concreto la produzca y pueda demostrarse;
- cumplimiento eIDAS completo;
- validez jurídica garantizada;
- certificación RGPD.
