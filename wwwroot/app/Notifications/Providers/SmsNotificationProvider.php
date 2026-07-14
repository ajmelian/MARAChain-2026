<?php

declare(strict_types=1);

namespace App\Notifications\Providers;

use App\Notifications\NotificationChannel;
use App\Notifications\NotificationMessage;
use App\Notifications\NotificationProviderInterface;
use App\Notifications\NotificationResult;
use App\Notifications\RecipientAddress;

/**
 * SmsNotificationProvider — Stub para envio por SMS.
 *
 * La implementacion real requiere un gateway SMS (Twilio, etc.).
 * Este stub registra el intento y devuelve un resultado controlado.
 *
 * @package App\Notifications\Providers
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.5.0
 */
class SmsNotificationProvider implements NotificationProviderInterface
{
    public function channel(): NotificationChannel
    {
        return NotificationChannel::SMS;
    }

    public function send(RecipientAddress $recipient, NotificationMessage $message): NotificationResult
    {
        log_message('info', 'SMS stub: notification to ' . $recipient->address . ' — ' . $message->subject);

        return NotificationResult::fail(
            'SMS_UNCONFIGURED',
            'SMS channel is not yet configured.'
        );
    }

    public function health(): bool
    {
        return false;
    }
}
