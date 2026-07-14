<?php

declare(strict_types=1);

namespace App\Notifications\Providers;

use App\Notifications\NotificationChannel;
use App\Notifications\NotificationMessage;
use App\Notifications\NotificationProviderInterface;
use App\Notifications\NotificationResult;
use App\Notifications\RecipientAddress;

/**
 * TelegramNotificationProvider — Stub para envio por Telegram.
 *
 * La implementacion real requiere PoC con Bot API o MTProto.
 * Este stub registra el intento y devuelve un resultado controlado.
 *
 * @package App\Notifications\Providers
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.5.0
 */
class TelegramNotificationProvider implements NotificationProviderInterface
{
    public function channel(): NotificationChannel
    {
        return NotificationChannel::TELEGRAM;
    }

    public function send(RecipientAddress $recipient, NotificationMessage $message): NotificationResult
    {
        log_message('info', 'Telegram stub: notification to ' . $recipient->address . ' — ' . $message->subject);

        return NotificationResult::fail(
            'TELEGRAM_UNCONFIGURED',
            'Telegram channel is not yet configured. PoC pending.'
        );
    }

    public function health(): bool
    {
        return false;
    }
}
