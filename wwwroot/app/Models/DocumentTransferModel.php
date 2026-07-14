<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\DocumentTransfer;
use CodeIgniter\Model;

/**
 * DocumentTransferModel — persistence layer for DocumentTransfer entities.
 *
 * Manages the full lifecycle of document transfers (Inbox/Outbox metaphor):
 * creation, retrieval by sender/recipient/document/status, status transitions
 * via the entity's allowedTransitions() state machine, and revocation.
 *
 * @package App\Models
 * @author  Aythami
 * @since   1.1.1
 */
class DocumentTransferModel extends Model
{
    protected $table            = 'document_transfers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = DocumentTransfer::class;
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $skipValidation   = true;

    protected $allowedFields = [
        'id',
        'document_id',
        'sender_id',
        'recipient_id',
        'status',
        'requires_signature',
        'signature_completed',
        'signature_request_id',
        'requires_encryption',
        'encryption_envelope_id',
        'security_level',
        'idempotency_key',
        'expires_at',
        'available_at',
        'accessed_at',
        'downloaded_at',
        'accepted_at',
        'rejected_at',
        'revoked_at',
        'failed_at',
        'failure_reason',
    ];

    /**
     * Create a new document transfer.
     *
     * Requires an idempotency key for duplicate prevention.
     * Sets default status to 'PENDING_RECIPIENT'.
     *
     * @param array<string, mixed> $data Transfer data (camelCase keys)
     *
     * @return DocumentTransfer Persisted transfer entity
     *
     * @throws \RuntimeException When idempotencyKey is missing
     *
     * @since 1.1.1
     */
    public function create(array $data): DocumentTransfer
    {
        if (empty($data['idempotencyKey'] ?? '')) {
            throw new \RuntimeException('Idempotency key is required.');
        }

        $id = $this->generateUuidV4();

        $row = [
            'id'                  => $id,
            'document_id'         => $data['documentId'] ?? '',
            'sender_id'           => $data['senderId'] ?? '',
            'recipient_id'        => $data['recipientId'] ?? '',
            'status'              => $data['status'] ?? 'PENDING_RECIPIENT',
            'requires_signature'  => $data['requiresSignature'] ?? false,
            'signature_completed' => $data['signatureCompleted'] ?? false,
            'signature_request_id' => $data['signatureRequestId'] ?? null,
            'requires_encryption' => $data['requiresEncryption'] ?? true,
            'encryption_envelope_id' => $data['encryptionEnvelopeId'] ?? null,
            'security_level'      => $data['securityLevel'] ?? 'standard',
            'idempotency_key'     => $data['idempotencyKey'],
            'expires_at'          => $data['expiresAt'] ?? null,
        ];

        $this->insert($row);

        return $this->freshEntity($id);
    }

    /**
     * Find transfers sent by a specific user.
     *
     * @param string $senderId Sender user UUID
     *
     * @return DocumentTransfer[] List of transfers
     *
     * @since 1.1.1
     */
    public function findBySenderId(string $senderId): array
    {
        $rows = $this->db->table($this->table)
            ->where('sender_id', $senderId)
            ->get()
            ->getResultArray();

        return $this->freshEntities($rows);
    }

    /**
     * Find transfers received by a specific user.
     *
     * @param string $recipientId Recipient user UUID
     *
     * @return DocumentTransfer[] List of transfers
     *
     * @since 1.1.1
     */
    public function findByRecipientId(string $recipientId): array
    {
        $rows = $this->db->table($this->table)
            ->where('recipient_id', $recipientId)
            ->get()
            ->getResultArray();

        return $this->freshEntities($rows);
    }

    /**
     * Find transfers for a specific document.
     *
     * @param string $documentId Document UUID
     *
     * @return DocumentTransfer[] List of transfers
     *
     * @since 1.1.1
     */
    public function findByDocumentId(string $documentId): array
    {
        $rows = $this->db->table($this->table)
            ->where('document_id', $documentId)
            ->get()
            ->getResultArray();

        return $this->freshEntities($rows);
    }

    /**
     * Find transfers by status.
     *
     * @param string $status Status to filter by
     *
     * @return DocumentTransfer[] List of matching transfers
     *
     * @since 1.1.1
     */
    public function findByStatus(string $status): array
    {
        $rows = $this->db->table($this->table)
            ->where('status', $status)
            ->get()
            ->getResultArray();

        return $this->freshEntities($rows);
    }

    /**
     * Transition a transfer to a new status.
     *
     * Validates the transition against the entity's allowedTransitions()
     * state machine. Sets the appropriate timestamp for terminal statuses
     * (REVOKED, FAILED).
     *
     * @param DocumentTransfer $transfer  Transfer entity
     * @param string           $newStatus Target status
     *
     * @return DocumentTransfer Updated transfer entity
     *
     * @throws \RuntimeException When the transition is not allowed
     *
     * @since 1.1.1
     */
    public function transitionStatus(DocumentTransfer $transfer, string $newStatus): DocumentTransfer
    {
        $row = $this->db->table($this->table)
            ->where('id', $transfer->id)
            ->get()
            ->getRowArray();

        $tempEntity = new DocumentTransfer($row);
        $allowed    = $tempEntity->allowedTransitions();

        if (! in_array($newStatus, $allowed, true)) {
            throw new \RuntimeException(
                "Invalid transfer status transition from {$row['status']} to {$newStatus}."
            );
        }

        $updateData = [
            'status' => $newStatus,
        ];

        $now = date('Y-m-d H:i:s');

        match ($newStatus) {
            'REVOKED' => $updateData['revoked_at'] = $now,
            'FAILED'  => $updateData['failed_at']  = $now,
            default   => null,
        };

        // Atomic guard: only update if status hasn't changed concurrently
        $this->db->table($this->table)
            ->where('id', $transfer->id)
            ->where('status', $row['status'])
            ->update($updateData);

        return $this->freshEntity($transfer->id);
    }

    /**
     * Revoke a transfer.
     *
     * Sets status to 'REVOKED' and records the revocation timestamp.
     *
     * @param DocumentTransfer $transfer Transfer entity
     *
     * @return DocumentTransfer Updated transfer entity
     *
     * @since 1.1.1
     */
    public function revokeTransfer(DocumentTransfer $transfer): DocumentTransfer
    {
        $row = $this->db->table($this->table)->where('id', $transfer->id)->get()->getRowArray();
        $currentStatus = $row['status'] ?? $transfer->status;
        $entity = new DocumentTransfer($row);

        $allowed = $entity->allowedTransitions();
        if (! in_array('REVOKED', $allowed, true)) {
            throw new \RuntimeException(
                "Transfer cannot be revoked from status {$currentStatus}."
            );
        }

        $this->update($transfer->id, [
            'status'     => 'REVOKED',
            'revoked_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->freshEntity($transfer->id);
    }

    /**
     * Find a transfer by its idempotency key.
     *
     * @param string $key Idempotency key (64 characters)
     *
     * @return DocumentTransfer|null Transfer entity or null if not found
     *
     * @since 1.1.1
     */
    public function findByIdempotencyKey(string $key): ?DocumentTransfer
    {
        $row = $this->db->table($this->table)
            ->where('idempotency_key', $key)
            ->get()
            ->getRowArray();

        return $row ? new DocumentTransfer($row) : null;
    }

    /**
     * Refresh entity from database, bypassing CI4 result cache.
     *
     * @param string $id Transfer UUID
     *
     * @return DocumentTransfer Fresh DocumentTransfer entity
     *
     * @since 1.1.1
     */
    private function freshEntity(string $id): DocumentTransfer
    {
        $row = $this->db->table($this->table)
            ->where('id', $id)
            ->get()
            ->getRowArray();

        return new DocumentTransfer($row);
    }

    /**
     * Build fresh entities from raw DB rows, bypassing CI4 entity cache.
     *
     * @param array<int, array<string, mixed>> $rows Raw database rows
     *
     * @return DocumentTransfer[]
     *
     * @since 1.1.1
     */
    private function freshEntities(array $rows): array
    {
        return array_map(static fn (array $row): DocumentTransfer => new DocumentTransfer($row), $rows);
    }

    /**
     * Generate a UUID v4 compatible with RFC 4122.
     *
     * @return string UUID v4 in canonical 8-4-4-4-12 format
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
