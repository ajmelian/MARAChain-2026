<?php

declare(strict_types=1);

namespace App\Notifications\Providers;

use App\Notifications\NotificationChannel;
use App\Notifications\NotificationMessage;
use App\Notifications\NotificationProviderInterface;
use App\Notifications\NotificationResult;
use App\Notifications\RecipientAddress;
use CodeIgniter\Email\Email;

/**
 * EmailNotificationProvider — Envio de notificaciones via SMTP.
 *
 * Utiliza la libreria Email de CodeIgniter 4 con la configuracion
 * SMTP del entorno (.env). Nunca incluye datos sensibles en los
 * mensajes (documento, CID, NIF, claves, tokens).
 *
 * @package App\Notifications\Providers
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.7.0
 */
class EmailNotificationProvider implements NotificationProviderInterface
{
    public function channel(): NotificationChannel
    {
        return NotificationChannel::EMAIL;
    }

    /**
     * Envia un correo electronico via SMTP usando la libreria Email de CI4.
     *
     * Configuracion SMTP tomada de .env:
     *   email.SMTPHost, email.SMTPPort, email.SMTPCrypto,
     *   email.SMTPUser, email.SMTPPass, email.fromEmail, email.fromName
     *
     * @param RecipientAddress    $recipient Direccion de correo del destinatario
     * @param NotificationMessage $message   Contenido del mensaje sin datos sensibles
     *
     * @return NotificationResult Resultado del envio
     */
    public function send(RecipientAddress $recipient, NotificationMessage $message): NotificationResult
    {
        if (! filter_var($recipient->address, FILTER_VALIDATE_EMAIL)) {
            return NotificationResult::fail('INVALID_EMAIL', 'La direccion de correo no es valida.');
        }

        $email = new Email();
        $email->initialize([
            'protocol'   => env('email.protocol') ?? 'smtp',
            'SMTPHost'   => env('email.SMTPHost') ?? 'localhost',
            'SMTPPort'   => (int) (env('email.SMTPPort') ?? 25),
            'SMTPCrypto' => env('email.SMTPCrypto') ?? 'tls',
            'SMTPUser'   => env('email.SMTPUser') ?? '',
            'SMTPPass'   => env('email.SMTPPass') ?? '',
            'mailType'   => $message->bodyHtml !== null ? 'html' : 'text',
            'charset'    => 'UTF-8',
            'wordWrap'   => true,
            'wrapChars'  => 76,
            'newline'    => "\r\n",
            'CRLF'       => "\r\n",
        ]);

        $email->setFrom(
            env('email.fromEmail') ?? 'noreply@marachain.local',
            env('email.fromName') ?? 'MARAChain'
        );
        $email->setTo($recipient->address);
        $email->setSubject($message->subject);

        if ($message->bodyHtml !== null) {
            $email->setMessage($message->bodyHtml);
        } else {
            $email->setMessage($message->bodyText);
        }

        $email->setHeader('X-Mailer', 'MARAChain/1.7.0');

        try {
            $success = $email->send(false);
        } catch (\Throwable $e) {
            log_message('error', 'Email send exception: ' . $e->getMessage());

            return NotificationResult::fail('MAIL_EXCEPTION', $e->getMessage());
        }

        if (! $success) {
            $debugger = $email->printDebugger(['headers', 'subject', 'body']);

            return NotificationResult::fail(
                'MAIL_FAILED',
                'Error al enviar el correo via SMTP. ' . substr((string) $debugger, 0, 500)
            );
        }

        return NotificationResult::ok('smtp-' . uniqid('', true));
    }

    /**
     * Verifica que la configuracion SMTP minima esta presente.
     *
     * @return bool True si el proveedor esta configurado
     */
    public function health(): bool
    {
        $host = env('email.SMTPHost');
        $user = env('email.SMTPUser');
        $from = env('email.fromEmail');

        return $host !== null && $host !== ''
            && $user !== null && $user !== ''
            && $from !== null && $from !== '';
    }
}
