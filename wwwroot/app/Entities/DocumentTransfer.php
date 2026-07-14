<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * Document transfer entity — Inbox/Outbox metaphor.
 *
 * Represents the delivery of a document from sender to a specific
 * recipient with its own lifecycle state machine.
 *
 * @property string      $id                     UUID v4
 * @property string      $documentId             Document UUID
 * @property string      $senderId               Sender user UUID
 * @property string      $recipientId            Recipient user UUID
 * @property string      $status                 One of 12 status values
 * @property bool        $requiresSignature      Whether sender signature is required
 * @property bool        $signatureCompleted      Whether signature was completed
 * @property string|null $signatureRequestId     Associated signature request UUID
 * @property bool        $requiresEncryption     Encryption required (always true in MVP)
 * @property string|null $encryptionEnvelopeId   Encryption envelope UUID
 * @property string      $securityLevel          standard|signed|signed_sealed
 * @property string      $idempotencyKey         Idempotency key (64 chars, unique)
 * @property string|null $expiresAt              Expiration datetime
 * @property string|null $availableAt            When transfer became available
 * @property string|null $accessedAt             First access by recipient
 * @property string|null $downloadedAt           Download timestamp
 * @property string|null $acceptedAt             Acceptance timestamp (phase 2)
 * @property string|null $rejectedAt             Rejection timestamp (phase 2)
 * @property string|null $revokedAt              Revocation timestamp
 * @property string|null $failedAt               Failure timestamp
 * @property string|null $failureReason          Failure reason (max 500 chars)
 * @property string      $createdAt              Creation timestamp
 * @property string      $updatedAt              Last update timestamp
 *
 * @since 1.1.1
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
class DocumentTransfer extends Entity
{
    private const VALID_STATUSES = [
        'PENDING_RECIPIENT',
        'READY',
        'SENDING',
        'SENT',
        'AVAILABLE',
        'ACCESSED',
        'DOWNLOADED',
        'ACCEPTED',
        'REJECTED',
        'EXPIRED',
        'REVOKED',
        'FAILED',
    ];

    private const TERMINAL_STATUSES = [
        'ACCEPTED',
        'REJECTED',
        'EXPIRED',
        'REVOKED',
    ];

    protected $casts = [
        'id'                     => 'string',
        'documentId'             => 'string',
        'senderId'               => 'string',
        'recipientId'            => 'string',
        'status'                 => 'string',
        'requiresSignature'      => 'bool',
        'signatureCompleted'     => 'bool',
        'signatureRequestId'     => '?string',
        'requiresEncryption'     => 'bool',
        'encryptionEnvelopeId'   => '?string',
        'securityLevel'          => 'string',
        'idempotencyKey'         => 'string',
        'expiresAt'              => '?datetime',
        'availableAt'            => '?datetime',
        'accessedAt'             => '?datetime',
        'downloadedAt'           => '?datetime',
        'acceptedAt'             => '?datetime',
        'rejectedAt'             => '?datetime',
        'revokedAt'              => '?datetime',
        'failedAt'               => '?datetime',
        'failureReason'          => '?string',
        'createdAt'              => 'datetime',
        'updatedAt'              => 'datetime',
    ];

    protected $datamap = [
        'document_id'               => 'documentId',
        'sender_id'                 => 'senderId',
        'recipient_id'              => 'recipientId',
        'requires_signature'        => 'requiresSignature',
        'signature_completed'       => 'signatureCompleted',
        'signature_request_id'      => 'signatureRequestId',
        'requires_encryption'       => 'requiresEncryption',
        'encryption_envelope_id'    => 'encryptionEnvelopeId',
        'security_level'            => 'securityLevel',
        'idempotency_key'           => 'idempotencyKey',
        'expires_at'                => 'expiresAt',
        'available_at'              => 'availableAt',
        'accessed_at'               => 'accessedAt',
        'downloaded_at'             => 'downloadedAt',
        'accepted_at'               => 'acceptedAt',
        'rejected_at'               => 'rejectedAt',
        'revoked_at'                => 'revokedAt',
        'failed_at'                 => 'failedAt',
        'failure_reason'            => 'failureReason',
        'created_at'                => 'createdAt',
        'updated_at'                => 'updatedAt',
    ];

    /**
     * Check if the transfer is in a terminal (final) state.
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, self::TERMINAL_STATUSES, true);
    }

    /**
     * Check if the transfer is in a valid status.
     */
    public function isValidStatus(string $status): bool
    {
        return in_array($status, self::VALID_STATUSES, true);
    }

    /**
     * Get the list of valid status transitions from the current status.
     *
     * @return string[]
     */
    public function allowedTransitions(): array
    {
        $transitions = [
            'PENDING_RECIPIENT' => ['READY', 'REVOKED', 'FAILED'],
            'READY'             => ['SENDING', 'REVOKED', 'FAILED'],
            'SENDING'           => ['SENT', 'REVOKED', 'FAILED'],
            'SENT'              => ['AVAILABLE', 'REVOKED', 'FAILED'],
            'AVAILABLE'         => ['ACCESSED', 'DOWNLOADED', 'EXPIRED', 'REVOKED'],
            'ACCESSED'          => ['DOWNLOADED', 'ACCEPTED', 'REJECTED', 'EXPIRED', 'REVOKED'],
            'DOWNLOADED'        => ['ACCEPTED', 'REJECTED', 'EXPIRED', 'REVOKED'],
            'ACCEPTED'          => ['EXPIRED'],
            'REJECTED'          => [],
            'EXPIRED'           => [],
            'REVOKED'           => [],
            'FAILED'            => ['PENDING_RECIPIENT'],
        ];

        return $transitions[$this->status] ?? [];
    }

    /**
     * Check if the transfer is in a state accessible by the recipient.
     */
    public function isAccessibleByRecipient(): bool
    {
        $accessible = ['AVAILABLE', 'ACCESSED', 'DOWNLOADED', 'ACCEPTED'];

        return in_array($this->status, $accessible, true);
    }

    /**
     * Check if the transfer requires sender signature.
     */
    public function needsSignature(): bool
    {
        return $this->requiresSignature && ! $this->signatureCompleted;
    }
}
