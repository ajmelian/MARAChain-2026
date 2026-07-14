<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EvidenceModel;

/**
 * EvidenceService — automated business event recording.
 *
 * Provides a high-level API for recording domain events as
 * immutable evidence records. Called by controllers and services
 * whenever a business-significant operation occurs.
 *
 * Evidence records are append-only and eventually incorporated
 * into the cryptographic ledger via LedgerService::sealBlock().
 *
 * @package App\Services
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.4.0
 */
class EvidenceService
{
    private EvidenceModel $evidenceModel;

    public function __construct()
    {
        $this->evidenceModel = model(EvidenceModel::class);
    }

    /**
     * Record a document sent event.
     */
    public function documentSent(string $transferId, string $senderId, string $recipientId, string $documentId): void
    {
        $this->record('DocumentSent', 'DocumentTransfer', $transferId, [
            'transferId'  => $transferId,
            'senderId'    => $senderId,
            'recipientId' => $recipientId,
            'documentId'  => $documentId,
        ]);
    }

    /**
     * Record a transfer accessed event.
     */
    public function transferAccessed(string $transferId, string $recipientId): void
    {
        $this->record('TransferAccessed', 'DocumentTransfer', $transferId, [
            'transferId'  => $transferId,
            'recipientId' => $recipientId,
        ]);
    }

    /**
     * Record a transfer downloaded event.
     */
    public function transferDownloaded(string $transferId, string $recipientId): void
    {
        $this->record('TransferDownloaded', 'DocumentTransfer', $transferId, [
            'transferId'  => $transferId,
            'recipientId' => $recipientId,
        ]);
    }

    /**
     * Record a transfer accepted event.
     */
    public function transferAccepted(string $transferId, string $recipientId): void
    {
        $this->record('TransferAccepted', 'DocumentTransfer', $transferId, [
            'transferId'  => $transferId,
            'recipientId' => $recipientId,
        ]);
    }

    /**
     * Record a transfer rejected event.
     */
    public function transferRejected(string $transferId, string $recipientId, string $reason = ''): void
    {
        $this->record('TransferRejected', 'DocumentTransfer', $transferId, [
            'transferId'  => $transferId,
            'recipientId' => $recipientId,
            'reason'      => $reason,
        ]);
    }

    /**
     * Record a transfer revoked event.
     */
    public function transferRevoked(string $transferId, string $senderId): void
    {
        $this->record('TransferRevoked', 'DocumentTransfer', $transferId, [
            'transferId' => $transferId,
            'senderId'   => $senderId,
        ]);
    }

    /**
     * Record a document sealed event.
     */
    public function documentSealed(string $documentId, string $ownerId): void
    {
        $this->record('DocumentSealed', 'Document', $documentId, [
            'documentId' => $documentId,
            'ownerId'    => $ownerId,
        ]);
    }

    /**
     * Internal: persist an evidence record atomically.
     */
    private function record(string $eventType, string $aggregateType, string $aggregateId, array $payload): void
    {
        try {
            $this->evidenceModel->createEvidence([
                'eventId'       => \App\Helpers\Uuid::v4(),
                'eventType'     => $eventType,
                'occurredAt'    => date('Y-m-d H:i:s'),
                'aggregateType' => $aggregateType,
                'aggregateId'   => $aggregateId,
                'actorType'     => 'system',
                'payloadJson'   => json_encode($payload, JSON_UNESCAPED_SLASHES),
                'payloadHash'   => hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES)),
            ]);
        } catch (\Throwable $e) {
            log_message('critical', "EVIDENCE_LOST: {$eventType} for {$aggregateId}: " . $e->getMessage());
        }
    }
}
