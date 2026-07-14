# MARAChain — Diseño de Notificaciones

**Versión:** 1.2.0  
**Fecha:** 14 de julio de 2026  
**Estado:** Baseline de notificaciones aprobada  
**Clasificación:** Fuente de verdad

## 1. Objeto

Este documento define la arquitectura, comportamiento, seguridad y semántica de los canales de notificación de MARAChain.

## 2. Decisión principal

MARAChain dispondrá de:

- una cuenta global corporativa de WhatsApp;
- una cuenta global corporativa de Telegram;
- una configuración global de email;
- una integración futura global de SMS.

Todos los avisos se enviarán desde cuentas propiedad de MARAChain.

Los usuarios remitentes no conectarán cuentas personales y no aportarán sesiones, cookies, códigos QR, tokens o credenciales de mensajería.

## 3. Datos aportados por el remitente

El remitente podrá facilitar:

- email principal del destinatario;
- emails adicionales;
- número de WhatsApp;
- cuenta o alias de Telegram;
- teléfono móvil para SMS.

Estos valores:

- identifican el destino del aviso;
- no identifican jurídicamente al destinatario;
- no conceden acceso al documento;
- no son credenciales de la cuenta emisora;
- no sustituyen la autenticación en MARAChain.

## 4. Modelo de envío

```text
Usuario remitente
        ↓
DocumentTransfer AVAILABLE
        ↓
NotificationRequested
        ↓
Outbox transaccional
        ↓
Worker PHP
        ├── Email global
        ├── WhatsApp global
        ├── Telegram global
        └── SMS global futuro
        ↓
Destinatario
```

## 5. Contrato de proveedor

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

Adaptadores:

```text
EmailNotificationProvider
GlobalWhatsAppNotificationProvider
GlobalTelegramNotificationProvider
SmsNotificationProvider
```

La selección de SDK, protocolo o gateway pertenece a infraestructura y requerirá PoC y ADR.

## 6. Cuentas globales

Modelo conceptual:

```text
global_messaging_accounts
├── id
├── environment
├── channel
├── account_reference
├── credential_reference
├── status
├── connected_at
├── last_health_check_at
├── last_error_at
├── disabled_at
└── version
```

Solo habrá una cuenta activa por canal y entorno, salvo que un ADR posterior apruebe redundancia o particionado.

Estados:

- `PENDING_CONFIGURATION`;
- `CONNECTED`;
- `DEGRADED`;
- `DISCONNECTED`;
- `DISABLED`;
- `ERROR`.

## 7. Secretos

Las sesiones y credenciales no se almacenarán en `wwwroot`.

Ubicación de referencia:

```text
/var/lib/marachain/integrations/
├── whatsapp/
│   └── global/
└── telegram/
    └── global/
```

También podrá utilizarse un secret manager o volumen cifrado equivalente.

Requisitos:

- separación por entorno;
- cifrado en reposo;
- permisos mínimos;
- referencia opaca desde MySQL;
- ausencia en Git;
- ausencia en logs;
- ausencia en ledger;
- backups cifrados;
- rotación y revocación;
- acceso administrativo auditado.

## 8. Contenido

Ejemplo:

```text
MARAChain

[Aythami Melián / Empresa X] le ha enviado una transmisión documental.

Título: [título permitido por política]

Acceda a MARAChain para identificarse y consultar el documento:
https://marachain.example/...
```

El enlace no concederá acceso directo. El destinatario deberá autenticarse.

Nunca se enviarán:

- el documento;
- el CID;
- claves;
- sobres criptográficos;
- NIF/NIE/CIF completo;
- hash documental;
- token duradero;
- descripción sensible;
- evidencias completas.

## 9. Estados

- `QUEUED`;
- `SENDING`;
- `SENT`;
- `DELIVERED`, solo cuando el proveedor lo informe;
- `FAILED`;
- `RETRYING`;
- `DEAD_LETTER`;
- `CANCELLED`.

## 10. Semántica probatoria

```text
Mensaje enviado ≠ documento entregado jurídicamente
Mensaje entregado ≠ documento accedido
Mensaje leído ≠ documento leído
Mensaje leído ≠ documento aceptado
```

La evidencia documental válida se generará dentro de MARAChain cuando el destinatario se autentique y ejecute una acción verificable.

## 11. Evidencias

Eventos permitidos:

- `notification.requested`;
- `notification.queued`;
- `notification.sent`;
- `notification.delivered`;
- `notification.failed`;
- `notification.dead_lettered`;
- `notification.cancelled`.

El evento incluirá:

- canal;
- proveedor;
- identificador opaco de cuenta global;
- identificador de transferencia;
- identificador técnico del proveedor;
- timestamps;
- resultado;
- código de error normalizado;
- número de intento.

No incluirá la credencial, sesión ni contenido sensible.

## 12. Resiliencia

- outbox transaccional;
- idempotencia;
- reintentos con backoff y jitter;
- circuit breaker;
- dead-letter;
- health checks;
- fallback por email;
- desactivación por canal;
- reconciliación de estados;
- rate limiting.

El fallo de un canal complementario no revertirá una transferencia ya confirmada.

## 13. WhatsApp

La cuenta emisora será global y propiedad de MARAChain.

La implementación concreta queda pendiente de PoC.

Si se utiliza un SDK no oficial:

- no se considerará canal con SLA garantizado;
- podrá existir riesgo de bloqueo o incompatibilidad;
- deberá poder desactivarse sin afectar al núcleo;
- no tendrá valor de entrega certificada;
- se limitará frecuencia y contenido;
- se mantendrá fallback por email.

## 14. Telegram

La cuenta emisora será global y propiedad de MARAChain.

El mecanismo definitivo podrá depender de las capacidades necesarias:

- Bot API, cuando el destinatario haya iniciado o autorizado la interacción;
- MTProto con cuenta global, cuando sea necesario y jurídicamente aceptable.

La elección se resolverá mediante PoC y ADR.

No se garantizará el envío a cualquier usuario hasta validar las restricciones de privacidad y resolución de destinatarios.

## 15. Consentimiento y abuso

Antes de producción se definirá:

- base jurídica o consentimiento;
- política de opt-out;
- listas de bloqueo;
- frecuencia máxima;
- ventanas horarias;
- prevención de spam;
- tratamiento de números reciclados;
- respuesta a denuncias;
- suspensión automática por abuso.

## 16. Observabilidad

Métricas:

- mensajes en cola;
- latencia por canal;
- tasa de éxito;
- tasa de reintento;
- dead-letter;
- health de cuenta global;
- rate limits;
- desconexiones;
- errores por categoría.

Las métricas no incluirán contenido del mensaje ni direcciones completas.

## 17. Criterios de aceptación

- Todos los mensajes salen desde cuentas globales de MARAChain.
- El remitente no proporciona sesiones ni credenciales.
- Los datos del formulario son direcciones del destinatario.
- El documento nunca se envía por WhatsApp o Telegram.
- El enlace exige autenticación.
- Las credenciales permanecen fuera de `wwwroot`.
- Un fallo de mensajería no revierte la transferencia.
- Los acuses no se presentan como lectura o aceptación.
- Los proveedores son sustituibles.
- Los canales pueden desactivarse por configuración.
