<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * Ledger block entity — internal append-only ledger.
 *
 * Contains canonicalized events (RFC 8785), SHA-256 hashed. Each block
 * includes: sequence, period, events, Merkle root, previous hash,
 * Ed25519 signature, and version.
 *
 * @property string      $id                     UUID v4
 * @property int         $blockNumber            Sequential block number (unique)
 * @property string      $periodStart            Period start timestamp
 * @property string      $periodEnd              Period end timestamp
 * @property int         $eventCount             Number of events in the block
 * @property string      $eventsJson             Canonicalized events JSON array
 * @property string      $merkleRoot             Merkle root of events (SHA-256)
 * @property string|null $previousBlockHash      Previous block hash (null for genesis)
 * @property string      $blockHash              SHA-256 of full canonicalized block
 * @property string      $blockSignature         Ed25519 block signature
 * @property string      $signingKeyFingerprint  Ed25519 signing key fingerprint
 * @property string      $schemaVersion          Block schema version
 * @property string      $sealedAt               Block seal timestamp
 * @property string|null $checkpointId           DLT checkpoint ID (phase 2)
 * @property string|null $checkpointTxHash       DLT anchor tx hash (phase 2)
 * @property string      $createdAt              Creation timestamp
 *
 * @since 1.1.1
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
class LedgerBlock extends Entity
{
    protected $casts = [
        'id'                    => 'string',
        'blockNumber'           => 'int',
        'periodStart'           => 'datetime',
        'periodEnd'             => 'datetime',
        'eventCount'            => 'int',
        'eventsJson'            => 'string',
        'merkleRoot'            => 'string',
        'previousBlockHash'     => '?string',
        'blockHash'             => 'string',
        'blockSignature'        => 'string',
        'signingKeyFingerprint' => 'string',
        'schemaVersion'         => 'string',
        'sealedAt'              => 'datetime',
        'checkpointId'          => '?string',
        'checkpointTxHash'      => '?string',
        'createdAt'             => 'datetime',
    ];

    protected $datamap = [
        'block_number'             => 'blockNumber',
        'period_start'             => 'periodStart',
        'period_end'               => 'periodEnd',
        'event_count'              => 'eventCount',
        'events_json'              => 'eventsJson',
        'merkle_root'              => 'merkleRoot',
        'previous_block_hash'      => 'previousBlockHash',
        'block_hash'               => 'blockHash',
        'block_signature'          => 'blockSignature',
        'signing_key_fingerprint'  => 'signingKeyFingerprint',
        'schema_version'           => 'schemaVersion',
        'sealed_at'                => 'sealedAt',
        'checkpoint_id'            => 'checkpointId',
        'checkpoint_tx_hash'       => 'checkpointTxHash',
        'created_at'               => 'createdAt',
    ];

    /**
     * Check if this is the genesis block (no previous hash).
     */
    public function isGenesisBlock(): bool
    {
        return $this->previousBlockHash === null;
    }

    /**
     * Check if this block has been anchored to an external DLT.
     */
    public function hasCheckpoint(): bool
    {
        return $this->checkpointId !== null && $this->checkpointTxHash !== null;
    }

    /**
     * Check if the block has a valid Merkle root length (SHA-256 = 64 hex chars).
     */
    public function hasValidMerkleRoot(): bool
    {
        return strlen($this->merkleRoot) === 64 && ctype_xdigit($this->merkleRoot);
    }

    /**
     * Check if the block has a valid block hash (SHA-256 = 64 hex chars).
     */
    public function hasValidBlockHash(): bool
    {
        return strlen($this->blockHash) === 64 && ctype_xdigit($this->blockHash);
    }
}
