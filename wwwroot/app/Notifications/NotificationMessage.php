<?php

declare(strict_types=1);

namespace App\Notifications;

/**
 * NotificationMessage — Contenido de una notificacion.
 *
 * Value object inmutable con el titulo, cuerpo y metadatos
 * necesarios para construir el mensaje en cada canal.
 * Nunca contiene datos sensibles (documento, CID, claves, NIF).
 *
 * @package App\Notifications
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.5.0
 */
final readonly class NotificationMessage
{
    public function __construct(
        public string $subject,
        public string $bodyText,
        public ?string $bodyHtml = null,
        public ?string $templateKey = null,
        public array $templateData = [],
    ) {}
}
