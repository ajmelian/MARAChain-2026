<?php

declare(strict_types=1);

namespace App\Notifications;

/**
 * NotificationProviderInterface — Contrato de proveedor de notificaciones.
 *
 * Cada canal (Email, WhatsApp, Telegram, SMS) implementa esta interfaz.
 * La cuenta emisora es global y propiedad de MARAChain.
 *
 * @package App\Notifications
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.5.0
 */
interface NotificationProviderInterface
{
    /**
     * Canal de notificacion que gestiona este proveedor.
     */
    public function channel(): NotificationChannel;

    /**
     * Envia una notificacion al destinatario.
     *
     * @param RecipientAddress    $recipient Destinatario con canal y direccion
     * @param NotificationMessage $message   Contenido del mensaje sin datos sensibles
     *
     * @return NotificationResult Resultado del envio (OK o FAIL con codigo de error)
     */
    public function send(
        RecipientAddress $recipient,
        NotificationMessage $message
    ): NotificationResult;

    /**
     * Verifica el estado de salud del proveedor.
     *
     * @return bool True si el proveedor esta operativo
     */
    public function health(): bool;
}
