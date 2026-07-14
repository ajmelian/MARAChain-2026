<?php

declare(strict_types=1);

namespace App\Notifications\Providers;

use App\Notifications\NotificationChannel;
use App\Notifications\NotificationMessage;
use App\Notifications\NotificationProviderInterface;
use App\Notifications\NotificationResult;
use App\Notifications\RecipientAddress;

/**
 * WhatsAppNotificationProvider — Stub para envio por WhatsApp.
 *
 * La implementacion real requiere PoC con la API de WhatsApp Business.
 * Este stub registra el intento y devuelve un resultado controlado.
 * El fallback por email esta garantizado en el worker.
 *
 * @package App\Notifications\Providers
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.5.0
 */
class WhatsAppNotificationProvider implements NotificationProviderInterface
{
    public function channel(): NotificationChannel
    {
        return NotificationChannel::WHATSAPP;
    }

    public function send(RecipientAddress $recipient, NotificationMessage $message): NotificationResult
    {
        // Stub: WhatsApp Business API pending PoC
        log_message('info', 'WhatsApp stub: notification to ' . $recipient->address . ' — ' . $message->subject);

        return NotificationResult::fail(
            'WHATSAPP_UNCONFIGURED',
            'WhatsApp channel is not yet configured. PoC pending.'
        );
    }

    public function health(): bool
    {
        return false;
    }
}
