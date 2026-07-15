<?php

declare(strict_types=1);

namespace App\Models;

use App\Helpers\Uuid;
use CodeIgniter\Model;
use App\Notifications\NotificationChannel;

/**
 * NotificationRequestedModel — Transactional outbox for notifications.
 *
 * Persists notification delivery requests atomically with the
 * business operation that triggers them. The NotificationsCommand
 * worker picks up QUEUED rows and delivers them.
 *
 * @package App\Models
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.5.0
 */
class NotificationRequestedModel extends Model
{
    protected $table            = 'notification_requested';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $skipValidation   = true;

    protected $allowedFields = [
        'id',
        'transfer_id',
        'channel',
        'recipient_address',
        'subject',
        'body_text',
        'body_html',
        'status',
        'priority',
        'attempt_count',
        'max_attempts',
        'provider_message_id',
        'error_code',
        'error_message',
        'scheduled_at',
        'sent_at',
        'last_attempt_at',
        'idempotency_key',
    ];

    /**
     * Queue a notification for delivery.
     *
     * Inserts a QUEUED row into the transactional outbox.
     * The caller should wrap this in a database transaction
     * with the business operation.
     *
     * @param NotificationChannel $channel          Delivery channel
     * @param string              $recipientAddress Email/phone/username
     * @param string              $subject          Subject line
     * @param string              $bodyText         Plain text body
     * @param string|null         $transferId       Associated transfer UUID
     * @param string              $priority         low|normal|high|critical
     *
     * @return string The UUID of the queued notification
     */
    public function queue(
        NotificationChannel $channel,
        string $recipientAddress,
        string $subject,
        string $bodyText,
        ?string $transferId = null,
        string $priority = 'normal'
    ): string {
        $id = Uuid::v4();

        $this->insert([
            'id'                => $id,
            'transfer_id'       => $transferId,
            'channel'           => $channel->value,
            'recipient_address' => $recipientAddress,
            'subject'           => $subject,
            'body_text'         => $bodyText,
            'status'            => 'QUEUED',
            'priority'          => $priority,
            'idempotency_key'   => hash('sha256', 'queue_' . $id),
        ]);

        return $id;
    }
}
