<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\LedgerBlock;
use CodeIgniter\Model;
use InvalidArgumentException;
use RuntimeException;

/**
 * LedgerBlockModel — append-only, immutable ledger blocks.
 *
 * Blocks are sealed and immutable. Once created with a blockNumber,
 * merkleRoot, blockHash, blockSignature, and sealedAt, they can never
 * be updated or deleted. Chain integrity is maintained via
 * previousBlockHash chaining.
 *
 * @since  1.1.1
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
class LedgerBlockModel extends Model
{
    protected $table            = 'ledger_blocks';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = LedgerBlock::class;
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = false;
    protected $skipValidation   = true;

    protected $allowedFields = [
        'id', 'block_number', 'period_start', 'period_end', 'event_count',
        'events_json', 'merkle_root', 'previous_block_hash', 'block_hash',
        'block_signature', 'signing_key_fingerprint', 'schema_version',
        'sealed_at', 'checkpoint_id', 'checkpoint_tx_hash', 'created_at',
    ];

    /**
     * Creates a new ledger block.
     *
     * Required fields: merkleRoot, blockHash, blockSignature,
     * signingKeyFingerprint, sealedAt.
     *
     * @param array $data Block data in camelCase keys.
     *
     * @return LedgerBlock The newly created block entity.
     *
     * @throws InvalidArgumentException If required fields are missing.
     *
     * @since 1.1.1
     */
    public function createBlock(array $data): LedgerBlock
    {
        if (empty($data['merkleRoot'])) {
            throw new InvalidArgumentException(
                'The merkleRoot field is required to create a ledger block.'
            );
        }

        $id = \App\Helpers\Uuid::v4();

        $row = [
            'id'                     => $id,
            'block_number'           => $data['blockNumber'] ?? 0,
            'period_start'           => $data['periodStart'] ?? date('Y-m-d H:i:s'),
            'period_end'             => $data['periodEnd'] ?? date('Y-m-d H:i:s'),
            'event_count'            => $data['eventCount'] ?? 0,
            'events_json'            => $data['eventsJson'] ?? '[]',
            'merkle_root'            => $data['merkleRoot'],
            'previous_block_hash'    => $data['previousBlockHash'] ?? null,
            'block_hash'             => $data['blockHash'] ?? '',
            'block_signature'        => $data['blockSignature'] ?? '',
            'signing_key_fingerprint' => $data['signingKeyFingerprint'] ?? '',
            'schema_version'         => $data['schemaVersion'] ?? '1.0',
            'sealed_at'              => $data['sealedAt'] ?? date('Y-m-d H:i:s'),
            'checkpoint_id'          => $data['checkpointId'] ?? null,
            'checkpoint_tx_hash'     => $data['checkpointTxHash'] ?? null,
            'created_at'             => date('Y-m-d H:i:s'),
        ];

        $this->insert($row);

        return $this->freshEntity($id);
    }

    /**
     * Finds a block by its sequential block number.
     *
     * @param int $blockNumber The sequential block number.
     *
     * @return LedgerBlock|null The entity if found, null otherwise.
     *
     * @since 1.1.1
     */
    public function findByBlockNumber(int $blockNumber): ?LedgerBlock
    {
        $row = $this->db->table($this->table)
            ->where('block_number', $blockNumber)
            ->get()
            ->getRowArray();

        return $row ? new LedgerBlock($row) : null;
    }

    /**
     * Finds a block by its SHA-256 block hash.
     *
     * @param string $blockHash The 64-character hex block hash.
     *
     * @return LedgerBlock|null The entity if found, null otherwise.
     *
     * @since 1.1.1
     */
    public function findByBlockHash(string $blockHash): ?LedgerBlock
    {
        $row = $this->db->table($this->table)
            ->where('block_hash', $blockHash)
            ->get()
            ->getRowArray();

        return $row ? new LedgerBlock($row) : null;
    }

    /**
     * Finds the latest (most recent) block with the highest block number.
     *
     * @return LedgerBlock|null The latest block entity, or null if none exist.
     *
     * @since 1.1.1
     */
    public function findLatestBlock(): ?LedgerBlock
    {
        $row = $this->db->table($this->table)
            ->orderBy('block_number', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        return $row ? new LedgerBlock($row) : null;
    }

    /**
     * Finds blocks sealed within a date range.
     *
     * @param string $start Start datetime (inclusive).
     * @param string $end   End datetime (inclusive).
     *
     * @return LedgerBlock[] Array of matching entities.
     *
     * @since 1.1.1
     */
    public function findByDateRange(string $start, string $end): array
    {
        $rows = $this->db->table($this->table)
            ->where('sealed_at >=', $start)
            ->where('sealed_at <=', $end)
            ->get()
            ->getResultArray();

        return $this->freshEntities($rows);
    }

    /**
     * Blocks are immutable — updates are not allowed after sealing.
     *
     * @param int|string|null $id   The primary key value.
     * @param array|null      $data Data to update.
     *
     * @return bool Always throws RuntimeException.
     *
     * @throws RuntimeException Always — blocks are append-only.
     *
     * @since 1.1.1
     */
    public function update($id = null, $data = null): bool
    {
        throw new RuntimeException(
            'Ledger blocks are immutable and cannot be updated after sealing.'
        );
    }

    /**
     * Refresh entity from database, bypassing CI4 result cache.
     *
     * @param string $id The entity UUID.
     *
     * @return LedgerBlock Fresh LedgerBlock entity.
     *
     * @since 1.1.1
     */
    private function freshEntity(string $id): LedgerBlock
    {
        $row = $this->db->table($this->table)->where('id', $id)->get()->getRowArray();

        return new LedgerBlock($row);
    }

    /**
     * Build fresh entities from raw DB rows, bypassing CI4 entity cache.
     *
     * @param array<int, array<string, mixed>> $rows Raw database rows
     *
     * @return LedgerBlock[]
     *
     * @since 1.1.1
     */
    private function freshEntities(array $rows): array
    {
        return array_map(static fn (array $row): LedgerBlock => new LedgerBlock($row), $rows);
    }

}
