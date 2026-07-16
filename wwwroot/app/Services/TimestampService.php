<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Uuid;
use App\Models\EvidenceModel;
use RuntimeException;

/**
 * TimestampService — Sellado de tiempo via ledger interno.
 *
 * Implementa TimestampProviderInterface usando el ledger interno
 * como fuente de sellado de tiempo verificable.
 *
 * Cada timestamp genera:
 *   1. Evidencia en el ledger
 *   2. Bloque sellado (via LedgerService::sealBlock)
 *   3. Recibo verificable con Merkle proof + firma Ed25519
 *
 * @package App\Services
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.9.0
 */
class TimestampService
{
    private EvidenceModel $evidenceModel;

    private LedgerService $ledgerService;

    public function __construct()
    {
        $this->evidenceModel  = model(EvidenceModel::class);
        $this->ledgerService  = new LedgerService();
    }

    /**
     * Create a timestamp for a given content hash.
     *
     * @param string $hash    SHA-256 hash to timestamp (64 hex chars)
     * @param string $context Context description (e.g. "DocumentSent")
     *
     * @return array{receipt: array, block: array}
     *
     * @throws RuntimeException
     */
    public function timestamp(string $hash, string $context = 'timestamp'): array
    {
        // 1. Create evidence
        $evidence = $this->evidenceModel->createEvidence([
            'eventId'       => Uuid::v4(),
            'eventType'     => 'TimestampCreated',
            'schemaVersion' => '1.0',
            'occurredAt'    => date('Y-m-d H:i:s'),
            'aggregateType' => 'Timestamp',
            'aggregateId'   => $hash,
            'payloadJson'   => json_encode([
                'hash'     => $hash,
                'context'  => $context,
            ], JSON_UNESCAPED_SLASHES),
            'payloadHash'   => $hash,
        ]);

        // 2. Seal block (collects all pending evidence)
        $result = $this->ledgerService->sealBlock();

        if ($result === null) {
            throw new RuntimeException('Failed to seal timestamp block.');
        }

        $block = $result['block'];

        // 3. Generate Merkle proof for this event
        $proof = $this->ledgerService->generateProof($evidence->eventId);

        // 4. Build receipt
        $receipt = [
            'hash'          => $hash,
            'context'       => $context,
            'eventId'       => $evidence->eventId,
            'blockNumber'   => $block->blockNumber,
            'blockHash'     => $block->blockHash,
            'sealedAt'      => (string) $block->sealedAt,
            'signature'     => $block->blockSignature,
            'merkleProof'   => $proof,
        ];

        return [
            'receipt' => $receipt,
            'block'   => $block,
        ];
    }

    /**
     * Retrieve the timestamp receipt for a given event.
     *
     * @param string $eventId Event UUID
     *
     * @return array|null Receipt array or null if not found
     */
    public function getReceipt(string $eventId): ?array
    {
        $event = $this->evidenceModel->findByEventId($eventId);

        if ($event === null) {
            return null;
        }

        $proof = $this->ledgerService->generateProof($eventId);

        if ($proof === null) {
            return null;
        }

        return [
            'hash'        => $event->payloadHash,
            'eventId'     => $eventId,
            'eventType'   => $event->eventType,
            'occurredAt'  => (string) $event->occurredAt,
            'blockNumber' => $proof['blockNumber'],
            'blockHash'   => $proof['blockHash'],
            'sealedAt'    => $proof['sealedAt'],
            'signature'   => $proof['blockSignature'],
            'merkleProof'=> [
                'leafHash'   => $proof['leafHash'],
                'leafIndex'  => $proof['leafIndex'],
                'siblings'   => $proof['siblings'],
                'directions' => $proof['directions'],
                'merkleRoot' => $proof['merkleRoot'],
            ],
        ];
    }
}
