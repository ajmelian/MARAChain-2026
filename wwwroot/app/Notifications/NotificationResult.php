<?php

declare(strict_types=1);

namespace App\Notifications;

/**
 * NotificationResult — Resultado del envio de una notificacion.
 *
 * @package App\Notifications
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.5.0
 */
final readonly class NotificationResult
{
    public function __construct(
        public bool $success,
        public ?string $providerMessageId = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
    ) {}

    public static function ok(string $providerMessageId): self
    {
        return new self(true, $providerMessageId);
    }

    public static function fail(string $errorCode, string $errorMessage): self
    {
        return new self(false, null, $errorCode, $errorMessage);
    }
}
