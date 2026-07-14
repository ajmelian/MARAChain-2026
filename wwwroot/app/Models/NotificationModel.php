<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Notification;
use CodeIgniter\Model;
use InvalidArgumentException;

/**
 * NotificationModel — outbox-based email notification management.
 *
 * Implements transactional outbox pattern with retries and dead-letter
 * queue. Notifications transition through:
 * PENDING -> SENDING -> SENT | FAILED -> DEAD_LETTER.
 *
 * @since  1.1.1
 * @author Aythami
 */
class NotificationModel extends Model
{
    protected $table            = 'notifications';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = Notification::class;
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $skipValidation   = true;

    protected $allowedFields = [
        'id', 'transfer_id', 'recipient_user_id', 'recipient_email',
        'notification_type', 'subject', 'body_text', 'body_html',
        'status', 'priority', 'attempt_count', 'max_attempts',
        'last_attempt_at', 'sent_at', 'provider_message_id',
        'provider_response', 'error_message', 'scheduled_at',
    ];

    /**
     * Creates a new notification with default status PENDING.
     *
     * Required fields: recipientEmail.
     * Defaults: status=PENDING, priority=normal, attemptCount=0,
     * maxAttempts=5.
     *
     * @param array $data Notification data in camelCase keys.
     *
     * @return Notification The newly created notification entity.
     *
     * @throws InvalidArgumentException If required fields are missing.
     *
     * @since 1.1.1
     */
    public function createNotification(array $data): Notification
    {
        if (empty($data['recipientEmail'])) {
            throw new InvalidArgumentException(
                'The recipientEmail field is required to create a notification.'
            );
        }

        $id = $this->generateUuidV4();

        $row = [
            'id'                 => $id,
            'transfer_id'        => $data['transferId'] ?? null,
            'recipient_user_id'  => $data['recipientUserId'] ?? null,
            'recipient_email'    => $data['recipientEmail'],
            'notification_type'  => $data['notificationType'] ?? '',
            'subject'            => $data['subject'] ?? '',
            'body_text'          => $data['bodyText'] ?? null,
            'body_html'          => $data['bodyHtml'] ?? null,
            'status'             => $data['status'] ?? 'PENDING',
            'priority'           => $data['priority'] ?? 'normal',
            'attempt_count'      => 0,
            'max_attempts'       => 5,
            'scheduled_at'       => $data['scheduledAt'] ?? null,
        ];

        $this->insert($row);

        return $this->freshEntity($id);
    }

    /**
     * Finds all notifications with PENDING status, ordered by priority descending.
     *
     * @return Notification[] Array of pending notification entities.
     *
     * @since 1.1.1
     */
    public function findPending(): array
    {
        $rows = $this->db->table($this->table)
            ->where('status', 'PENDING')
            ->orderBy('priority', 'DESC')
            ->get()
            ->getResultArray();

        return $this->freshEntities($rows);
    }

    /**
     * Finds notifications by recipient email address.
     *
     * @param string $email The recipient email address.
     *
     * @return Notification[] Array of matching entities.
     *
     * @since 1.1.1
     */
    public function findByRecipientEmail(string $email): array
    {
        $rows = $this->db->table($this->table)
            ->where('recipient_email', $email)
            ->get()
            ->getResultArray();

        return $this->freshEntities($rows);
    }

    /**
     * Finds notifications related to a specific transfer.
     *
     * @param string $transferId The transfer UUID.
     *
     * @return Notification[] Array of matching entities.
     *
     * @since 1.1.1
     */
    public function findByTransferId(string $transferId): array
    {
        $rows = $this->db->table($this->table)
            ->where('transfer_id', $transferId)
            ->get()
            ->getResultArray();

        return $this->freshEntities($rows);
    }

    /**
     * Marks a notification as SENDING (in transit to provider).
     *
     * Sets status to SENDING and records the attempt timestamp.
     *
     * @param string $id The notification UUID.
     *
     * @return Notification The updated entity.
     *
     * @since 1.1.1
     */
    public function markAsSending(string $id): Notification
    {
        $this->update($id, [
            'status'          => 'SENDING',
            'last_attempt_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->freshEntity($id);
    }

    /**
     * Marks a notification as SENT with provider message ID and timestamp.
     *
     * @param string      $id                The notification UUID.
     * @param string|null $providerMessageId The provider's message identifier.
     *
     * @return Notification The updated entity.
     *
     * @since 1.1.1
     */
    public function markAsSent(string $id, ?string $providerMessageId = null): Notification
    {
        $updateData = [
            'status'  => 'SENT',
            'sent_at' => date('Y-m-d H:i:s'),
        ];

        if ($providerMessageId !== null) {
            $updateData['provider_message_id'] = $providerMessageId;
        }

        $this->update($id, $updateData);

        return $this->freshEntity($id);
    }

    /**
     * Marks a notification as FAILED with an error message.
     *
     * @param string $id           The notification UUID.
     * @param string $errorMessage Description of the failure.
     *
     * @return Notification The updated entity.
     *
     * @since 1.1.1
     */
    public function markAsFailed(string $id, string $errorMessage): Notification
    {
        $this->update($id, [
            'status'        => 'FAILED',
            'error_message' => $errorMessage,
        ]);

        return $this->freshEntity($id);
    }

    /**
     * Increments the attempt count for a notification.
     *
     * Reads the current attempt count directly from the database,
     * increments it, and persists the new value.
     *
     * @param string $id The notification UUID.
     *
     * @return Notification The updated entity.
     *
     * @throws InvalidArgumentException If the notification is not found.
     *
     * @since 1.1.1
     */
    public function incrementAttemptCount(string $id): Notification
    {
        $row = $this->db->table($this->table)->where('id', $id)->get()->getRowArray();

        if ($row === false || $row === null) {
            throw new InvalidArgumentException(
                "Notification with ID {$id} not found."
            );
        }

        $newAttemptCount = (int) ($row['attempt_count'] ?? 0) + 1;

        $this->update($id, [
            'attempt_count' => $newAttemptCount,
        ]);

        return $this->freshEntity($id);
    }

    /**
     * Marks a notification as DEAD_LETTER (permanent failure).
     *
     * @param string $id The notification UUID.
     *
     * @return Notification The updated entity.
     *
     * @since 1.1.1
     */
    public function markAsDeadLetter(string $id): Notification
    {
        $this->update($id, [
            'status' => 'DEAD_LETTER',
        ]);

        return $this->freshEntity($id);
    }

    /**
     * Checks whether a notification can be retried.
     *
     * Reads raw DB state and returns true if status is not SENT,
     * not DEAD_LETTER, and attemptCount is less than maxAttempts.
     *
     * @param string $id The notification UUID.
     *
     * @return bool True if the notification can be retried.
     *
     * @since 1.1.1
     */
    public function canRetry(string $id): bool
    {
        $row = $this->db->table($this->table)->where('id', $id)->get()->getRowArray();

        if ($row === false || $row === null) {
            return false;
        }

        $status        = (string) ($row['status'] ?? '');
        $attemptCount  = (int) ($row['attempt_count'] ?? 0);
        $maxAttempts   = (int) ($row['max_attempts'] ?? 5);

        if ($status === 'SENT' || $status === 'DEAD_LETTER') {
            return false;
        }

        return $attemptCount < $maxAttempts;
    }

    /**
     * Refresh entity from database, bypassing CI4 result cache.
     *
     * @param string $id The entity UUID.
     *
     * @return Notification Fresh Notification entity.
     *
     * @since 1.1.1
     */
    private function freshEntity(string $id): Notification
    {
        $row = $this->db->table($this->table)->where('id', $id)->get()->getRowArray();

        return new Notification($row);
    }

    /**
     * Build fresh entities from raw DB rows, bypassing CI4 entity cache.
     *
     * @param array<int, array<string, mixed>> $rows Raw database rows
     *
     * @return Notification[]
     *
     * @since 1.1.1
     */
    private function freshEntities(array $rows): array
    {
        return array_map(static fn (array $row): Notification => new Notification($row), $rows);
    }

    /**
     * Generate a UUID v4 compatible with RFC 4122.
     *
     * @return string UUID v4 in canonical 8-4-4-4-12 format.
     *
     * @since 1.1.1
     */
    private function generateUuidV4(): string
    {
        $data = random_bytes(16);

        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
