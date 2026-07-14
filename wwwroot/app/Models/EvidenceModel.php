<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Evidence;
use CodeIgniter\Model;
use InvalidArgumentException;
use RuntimeException;

/**
 * EvidenceModel — append-only, immutable evidence records.
 *
 * Evidence records are canonicalized, append-only event logs. Once created,
 * they can never be updated or deleted. They are incorporated into the
 * internal ledger via ledgerBlockNumber assignment.
 *
 * @since  1.1.1
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
class EvidenceModel extends Model
{
    protected $table            = 'evidences';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = Evidence::class;
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = false;
    protected $skipValidation   = true;

    protected $allowedFields = [
        'id', 'event_id', 'event_type', 'schema_version', 'occurred_at',
        'aggregate_type', 'aggregate_id', 'correlation_id', 'causation_id',
        'actor_id', 'actor_type', 'payload_json', 'payload_hash',
        'ip_address_truncated', 'user_agent_truncated', 'ledger_block_number',
        'created_at',
    ];

    /**
     * Creates a new evidence record (append-only).
     *
     * Required fields: eventId, payloadJson, payloadHash.
     *
     * @param array $data Evidence data in camelCase keys.
     *
     * @return Evidence The newly created evidence entity.
     *
     * @throws InvalidArgumentException If required fields are missing.
     *
     * @since 1.1.1
     */
    public function createEvidence(array $data): Evidence
    {
        if (empty($data['eventId'])) {
            throw new InvalidArgumentException(
                'The eventId field is required to create evidence.'
            );
        }

        if (empty($data['payloadJson'])) {
            throw new InvalidArgumentException(
                'The payloadJson field is required to create evidence.'
            );
        }

        if (empty($data['payloadHash'])) {
            throw new InvalidArgumentException(
                'The payloadHash field is required to create evidence.'
            );
        }

        $id = $this->generateUuidV4();

        $row = [
            'id'                   => $id,
            'event_id'             => $data['eventId'],
            'event_type'           => $data['eventType'] ?? '',
            'schema_version'       => $data['schemaVersion'] ?? '1.0',
            'occurred_at'          => $data['occurredAt'] ?? date('Y-m-d H:i:s'),
            'aggregate_type'       => $data['aggregateType'] ?? '',
            'aggregate_id'         => $data['aggregateId'] ?? '',
            'correlation_id'       => $data['correlationId'] ?? null,
            'causation_id'         => $data['causationId'] ?? null,
            'actor_id'             => $data['actorId'] ?? null,
            'actor_type'           => $data['actorType'] ?? null,
            'payload_json'         => $data['payloadJson'],
            'payload_hash'         => $data['payloadHash'],
            'ip_address_truncated' => $data['ipAddressTruncated'] ?? null,
            'user_agent_truncated' => $data['userAgentTruncated'] ?? null,
            'created_at'           => date('Y-m-d H:i:s'),
        ];

        $this->insert($row);

        return $this->freshEntity($id);
    }

    /**
     * Finds a single evidence record by its unique event ID.
     *
     * @param string $eventId The event UUID.
     *
     * @return Evidence|null The entity if found, null otherwise.
     *
     * @since 1.1.1
     */
    public function findByEventId(string $eventId): ?Evidence
    {
        $row = $this->db->table($this->table)
            ->where('event_id', $eventId)
            ->get()
            ->getRowArray();

        return $row ? new Evidence($row) : null;
    }

    /**
     * Finds all evidence records for a given aggregate root.
     *
     * @param string $aggregateId The aggregate root UUID.
     *
     * @return Evidence[] Array of matching entities.
     *
     * @since 1.1.1
     */
    public function findByAggregateId(string $aggregateId): array
    {
        $rows = $this->db->table($this->table)
            ->where('aggregate_id', $aggregateId)
            ->get()
            ->getResultArray();

        return $this->freshEntities($rows);
    }

    /**
     * Finds evidence records filtered by event type.
     *
     * @param string $eventType The event type (e.g. LoginSuccess).
     *
     * @return Evidence[] Array of matching entities.
     *
     * @since 1.1.1
     */
    public function findByEventType(string $eventType): array
    {
        $rows = $this->db->table($this->table)
            ->where('event_type', $eventType)
            ->get()
            ->getResultArray();

        return $this->freshEntities($rows);
    }

    /**
     * Finds evidence records within a date range by occurredAt.
     *
     * @param string $start Start datetime (inclusive).
     * @param string $end   End datetime (inclusive).
     *
     * @return Evidence[] Array of matching entities.
     *
     * @since 1.1.1
     */
    public function findByDateRange(string $start, string $end): array
    {
        $rows = $this->db->table($this->table)
            ->where('occurred_at >=', $start)
            ->where('occurred_at <=', $end)
            ->get()
            ->getResultArray();

        return $this->freshEntities($rows);
    }

    /**
     * Finds evidence records not yet incorporated into the ledger.
     *
     * @return Evidence[] Array of matching entities with null ledgerBlockNumber.
     *
     * @since 1.1.1
     */
    public function findNotInLedger(): array
    {
        $rows = $this->db->table($this->table)
            ->where('ledger_block_number', null)
            ->get()
            ->getResultArray();

        return $this->freshEntities($rows);
    }

    /**
     * Assign evidence records to a ledger block.
     *
     * This is the ONLY mutation allowed on evidence records.
     * Sets the ledger_block_number and block UUID.
     *
     * @param string $evidenceId      Evidence UUID
     * @param int    $blockNumber     Ledger block number
     * @param string $blockId         Ledger block UUID
     *
     * @since 1.4.0
     */
    public function assignToLedger(string $evidenceId, int $blockNumber, string $blockId): void
    {
        $this->db->table($this->table)
            ->where('id', $evidenceId)
            ->update([
                'ledger_block_number' => $blockNumber,
            ]);
    }

    /**
     * Evidence is immutable — updates are not allowed.
     *
     * @param int|string|null $id   The primary key value.
     * @param array|null      $data Data to update.
     *
     * @return bool Always throws RuntimeException.
     *
     * @throws RuntimeException Always — evidence is append-only.
     *
     * @since 1.1.1
     */
    public function update($id = null, $data = null): bool
    {
        throw new RuntimeException(
            'Evidence records are immutable and cannot be updated.'
        );
    }

    /**
     * Refresh entity from database, bypassing CI4 result cache.
     *
     * @param string $id The entity UUID.
     *
     * @return Evidence Fresh Evidence entity.
     *
     * @since 1.1.1
     */
    private function freshEntity(string $id): Evidence
    {
        $row = $this->db->table($this->table)->where('id', $id)->get()->getRowArray();

        return new Evidence($row);
    }

    /**
     * Build fresh entities from raw DB rows, bypassing CI4 entity cache.
     *
     * @param array<int, array<string, mixed>> $rows Raw database rows
     *
     * @return Evidence[]
     *
     * @since 1.1.1
     */
    private function freshEntities(array $rows): array
    {
        return array_map(static fn (array $row): Evidence => new Evidence($row), $rows);
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
