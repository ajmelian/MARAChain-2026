<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * Notification entity — outbox-based email notifications.
 *
 * In MVP, email is the only channel. Notifications never include
 * document content, CID, full NIF, keys, reusable tokens, or
 * sensitive data. Managed via transactional outbox with retries
 * and dead-letter queue.
 *
 * @property string      $id                   UUID v4
 * @property string|null $transferId           Related transfer UUID
 * @property string|null $recipientUserId      Recipient user UUID
 * @property string      $recipientEmail       Recipient email
 * @property string      $notificationType     Notification type enum
 * @property string      $subject              Email subject
 * @property string|null $bodyText             Plain text body
 * @property string|null $bodyHtml             HTML body
 * @property string      $status               PENDING|SENDING|SENT|FAILED|DEAD_LETTER
 * @property string      $priority             low|normal|high|critical
 * @property int         $attemptCount         Retry attempt counter
 * @property int         $maxAttempts          Max retry attempts
 * @property string|null $lastAttemptAt        Last attempt timestamp
 * @property string|null $sentAt               Successful send timestamp
 * @property string|null $providerMessageId    Provider message ID
 * @property string|null $providerResponse     Provider response
 * @property string|null $errorMessage         Error message
 * @property string|null $scheduledAt          Scheduled send time
 * @property string      $createdAt            Creation timestamp
 * @property string      $updatedAt            Last update timestamp
 *
 * @since 1.1.1
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
class Notification extends Entity
{
    protected $casts = [
        'id'                 => 'string',
        'transferId'         => '?string',
        'recipientUserId'    => '?string',
        'recipientEmail'     => 'string',
        'notificationType'   => 'string',
        'subject'            => 'string',
        'bodyText'           => '?string',
        'bodyHtml'           => '?string',
        'status'             => 'string',
        'priority'           => 'string',
        'attemptCount'       => 'int',
        'maxAttempts'        => 'int',
        'lastAttemptAt'      => '?datetime',
        'sentAt'             => '?datetime',
        'providerMessageId'  => '?string',
        'providerResponse'   => '?string',
        'errorMessage'       => '?string',
        'scheduledAt'        => '?datetime',
        'createdAt'          => 'datetime',
        'updatedAt'          => 'datetime',
    ];

    protected $datamap = [
        'transfer_id'          => 'transferId',
        'recipient_user_id'    => 'recipientUserId',
        'recipient_email'      => 'recipientEmail',
        'notification_type'    => 'notificationType',
        'body_text'            => 'bodyText',
        'body_html'            => 'bodyHtml',
        'attempt_count'        => 'attemptCount',
        'max_attempts'         => 'maxAttempts',
        'last_attempt_at'      => 'lastAttemptAt',
        'sent_at'              => 'sentAt',
        'provider_message_id'  => 'providerMessageId',
        'provider_response'    => 'providerResponse',
        'error_message'        => 'errorMessage',
        'scheduled_at'         => 'scheduledAt',
        'created_at'           => 'createdAt',
        'updated_at'           => 'updatedAt',
    ];

    /**
     * Check if the notification is pending to be sent.
     */
    public function isPending(): bool
    {
        return $this->status === 'PENDING';
    }

    /**
     * Check if the notification has been sent successfully.
     */
    public function isSent(): bool
    {
        return $this->status === 'SENT';
    }

    /**
     * Check if the notification has failed permanently (dead letter).
     */
    public function isDeadLetter(): bool
    {
        return $this->status === 'DEAD_LETTER';
    }

    /**
     * Check if the notification has exceeded the maximum retry attempts.
     */
    public function hasExceededMaxAttempts(): bool
    {
        return $this->attemptCount >= $this->maxAttempts;
    }

    /**
     * Check if the notification can be retried.
     */
    public function canRetry(): bool
    {
        if ($this->status === 'DEAD_LETTER' || $this->status === 'SENT') {
            return false;
        }

        return $this->attemptCount < $this->maxAttempts;
    }

    /**
     * Check if the notification is of high or critical priority.
     */
    public function isUrgent(): bool
    {
        return in_array($this->priority, ['high', 'critical'], true);
    }
}
