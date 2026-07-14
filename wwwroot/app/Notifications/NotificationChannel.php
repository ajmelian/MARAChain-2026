<?php

declare(strict_types=1);

namespace App\Notifications;

/**
 * NotificationChannel — Canales de notificacion disponibles.
 *
 * Cada canal tiene su propio adaptador (NotificationProviderInterface)
 * y su propia cuenta global (GlobalMessagingAccount).
 *
 * @package App\Notifications
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.5.0
 */
enum NotificationChannel: string
{
    case EMAIL    = 'email';
    case WHATSAPP = 'whatsapp';
    case TELEGRAM = 'telegram';
    case SMS      = 'sms';

    /**
     * Label in Spanish for UI display.
     */
    public function label(): string
    {
        return match ($this) {
            self::EMAIL    => 'Correo electronico',
            self::WHATSAPP => 'WhatsApp',
            self::TELEGRAM => 'Telegram',
            self::SMS      => 'SMS',
        };
    }
}
