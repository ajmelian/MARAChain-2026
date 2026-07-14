<?php

declare(strict_types=1);

namespace App\Notifications;

/**
 * RecipientAddress — Direccion de un destinatario para una notificacion.
 *
 * Value object inmutable. El canal determina el formato del address:
 *   - EMAIL: direccion de correo valida
 *   - WHATSAPP: numero en formato E.164
 *   - TELEGRAM: username o chat ID
 *   - SMS: numero en formato E.164
 *
 * @package App\Notifications
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.5.0
 */
final readonly class RecipientAddress
{
    public function __construct(
        public NotificationChannel $channel,
        public string $address,
    ) {}
}
