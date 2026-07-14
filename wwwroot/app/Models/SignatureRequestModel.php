<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\SignatureRequest;
use CodeIgniter\Model;
use InvalidArgumentException;

/**
 * SignatureRequestModel — persistence layer for SignatureRequest entities.
 *
 * Manages the full lifecycle of delegated electronic signature requests:
 * CREATED → PROVIDER_REQUESTED → PROVIDER_COMPLETED → VALIDATED → CONSUMED.
 * Supports failure and expiration states with nonce-based replay prevention.
 *
 * @package App\Models
 * @author  Aythami
 * @since   1.1.1
 */
class SignatureRequestModel extends Model
{
    protected $table            = 'signature_requests';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = SignatureRequest::class;
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $skipValidation   = true;

    protected $allowedFields = [
        'id',
        'document_id',
        'user_id',
        'signature_intent',
        'status',
        'manifest_version',
        'manifest_json',
        'manifest_hash',
        'digest_algorithm',
        'signature_provider',
        'provider_request_id',
        'provider_response_json',
        'signed_digest',
        'signer_identity',
        'signature_algorithm',
        'nonce',
        'issued_at',
        'expires_at',
        'completed_at',
        'failed_at',
        'failure_reason',
    ];

    /**
     * Create a new signature request.
     *
     * Requires a nonce for replay prevention. Sets default status to 'CREATED'.
     *
     * @param array<string, mixed> $data Signature request data (camelCase keys)
     *
     * @return SignatureRequest Persisted signature request entity
     *
     * @throws InvalidArgumentException When nonce is missing
     *
     * @since 1.1.1
     */
    public function createSignatureRequest(array $data): SignatureRequest
    {
        if (empty($data['nonce'] ?? '')) {
            throw new InvalidArgumentException(
                'The nonce field is required to create a signature request.'
            );
        }

        $id = \App\Helpers\Uuid::v4();

        $row = [
            'id'                   => $id,
            'document_id'          => $data['documentId'] ?? '',
            'user_id'              => $data['userId'] ?? '',
            'signature_intent'     => $data['signatureIntent'] ?? '',
            'status'               => 'CREATED',
            'manifest_version'     => $data['manifestVersion'] ?? 1,
            'manifest_json'        => $data['manifestJson'] ?? '',
            'manifest_hash'        => $data['manifestHash'] ?? '',
            'digest_algorithm'     => $data['digestAlgorithm'] ?? 'sha256',
            'signature_provider'   => $data['signatureProvider'] ?? '',
            'nonce'                => $data['nonce'],
            'issued_at'            => $data['issuedAt'] ?? date('Y-m-d H:i:s'),
            'expires_at'           => $data['expiresAt'] ?? date('Y-m-d H:i:s', strtotime('+2 hours')),
        ];

        $this->insert($row);

        return $this->freshEntity($id);
    }

    /**
     * Find signature requests for a specific document.
     *
     * @param string $documentId Document UUID
     *
     * @return SignatureRequest[] List of signature requests
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
     * Find signature requests for a specific user.
     *
     * @param string $userId User UUID
     *
     * @return SignatureRequest[] List of signature requests
     *
     * @since 1.1.1
     */
    public function findByUserId(string $userId): array
    {
        $rows = $this->db->table($this->table)
            ->where('user_id', $userId)
            ->get()
            ->getResultArray();

        return $this->freshEntities($rows);
    }

    /**
     * Find signature requests by status.
     *
     * @param string $status Status to filter by
     *
     * @return SignatureRequest[] List of matching signature requests
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
     * Find a signature request by its unique nonce.
     *
     * @param string $nonce The 64-character replay-prevention nonce
     *
     * @return SignatureRequest|null Signature request entity or null if not found
     *
     * @since 1.1.1
     */
    public function findByNonce(string $nonce): ?SignatureRequest
    {
        $row = $this->db->table($this->table)
            ->where('nonce', $nonce)
            ->get()
            ->getRowArray();

        return $row ? new SignatureRequest($row) : null;
    }

    /**
     * Consume a signature request.
     *
     * Sets status to 'CONSUMED', marking the nonce as used.
     *
     * @param string $id Signature request UUID
     *
     * @return SignatureRequest Updated signature request entity
     *
     * @since 1.1.1
     */
    public function consumeSignature(string $id): SignatureRequest
    {
        $this->update($id, [
            'status' => 'CONSUMED',
        ]);

        return $this->freshEntity($id);
    }

    /**
     * Update the status of a signature request through its lifecycle.
     *
     * Transitions: CREATED → PROVIDER_REQUESTED → PROVIDER_COMPLETED → VALIDATED.
     * Sets completed_at when reaching VALIDATED status.
     *
     * @param string $id        Signature request UUID
     * @param string $newStatus Target status
     *
     * @return SignatureRequest Updated signature request entity
     *
     * @since 1.1.1
     */
    public function updateStatus(string $id, string $newStatus): SignatureRequest
    {
        $updateData = [
            'status' => $newStatus,
        ];

        if ($newStatus === 'VALIDATED') {
            $updateData['completed_at'] = date('Y-m-d H:i:s');
        }

        $this->update($id, $updateData);

        return $this->freshEntity($id);
    }

    /**
     * Mark a signature request as failed.
     *
     * Sets status to 'FAILED' and records the failure timestamp and reason.
     *
     * @param SignatureRequest $sr     Signature request entity
     * @param string           $reason Failure reason (max 500 characters)
     *
     * @return SignatureRequest Updated signature request entity
     *
     * @since 1.1.1
     */
    public function markAsFailed(SignatureRequest $sr, string $reason): SignatureRequest
    {
        $this->update($sr->id, [
            'status'         => 'FAILED',
            'failed_at'      => date('Y-m-d H:i:s'),
            'failure_reason' => $reason,
        ]);

        return $this->freshEntity($sr->id);
    }

    /**
     * Refresh entity from database, bypassing CI4 result cache.
     *
     * @param string $id Signature request UUID
     *
     * @return SignatureRequest Fresh SignatureRequest entity
     *
     * @since 1.1.1
     */
    private function freshEntity(string $id): SignatureRequest
    {
        $row = $this->db->table($this->table)
            ->where('id', $id)
            ->get()
            ->getRowArray();

        return new SignatureRequest($row);
    }

    /**
     * Build fresh entities from raw DB rows, bypassing CI4 entity cache.
     *
     * @param array<int, array<string, mixed>> $rows Raw database rows
     *
     * @return SignatureRequest[]
     *
     * @since 1.1.1
     */
    private function freshEntities(array $rows): array
    {
        return array_map(static fn (array $row): SignatureRequest => new SignatureRequest($row), $rows);
    }

}
