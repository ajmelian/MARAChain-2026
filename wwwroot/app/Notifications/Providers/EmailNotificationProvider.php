<?php

declare(strict_types=1);

namespace App\Notifications\Providers;

use App\Notifications\NotificationChannel;
use App\Notifications\NotificationMessage;
use App\Notifications\NotificationProviderInterface;
use App\Notifications\NotificationResult;
use App\Notifications\RecipientAddress;

/**
 * EmailNotificationProvider — Envio de notificaciones por correo electronico.
 *
 * Utiliza la configuracion SMTP del entorno para enviar correos desde
 * la cuenta global de MARAChain. Nunca incluye datos sensibles.
 *
 * @package App\Notifications\Providers
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.5.0
 */
class EmailNotificationProvider implements NotificationProviderInterface
{
    public function channel(): NotificationChannel
    {
        return NotificationChannel::EMAIL;
    }

    public function send(RecipientAddress $recipient, NotificationMessage $message): NotificationResult
    {
        if (! filter_var($recipient->address, FILTER_VALIDATE_EMAIL)) {
            return NotificationResult::fail('INVALID_EMAIL', 'La direccion de correo no es valida.');
        }

        $to        = $recipient->address;
        $subject   = $message->subject;
        $fromEmail = env('email.fromEmail') ?? 'noreply@marachain.local';
        $fromName  = env('email.fromName') ?? 'MARAChain';

        $headers  = 'From: ' . $fromName . ' <' . $fromEmail . '>' . "\r\n";
        $headers .= 'X-Mailer: MARAChain/1.5.0' . "\r\n";

        if ($message->bodyHtml !== null) {
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body     = $message->bodyHtml;
        } else {
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $body     = $message->bodyText;
        }

        $success = mail($to, $subject, $body, $headers);

        if (! $success) {
            $lastError = error_get_last();

            return NotificationResult::fail(
                'MAIL_FAILED',
                $lastError['message'] ?? 'Error desconocido al enviar el correo.'
            );
        }

        return NotificationResult::ok('local-' . uniqid('mail_', true));
    }

    public function health(): bool
    {
        $fromEmail = env('email.fromEmail');

        return $fromEmail !== null && $fromEmail !== '';
    }
}
