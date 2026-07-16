<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EvidenceModel;
use App\Models\LedgerBlockModel;
use InvalidArgumentException;
use RuntimeException;

/**
 * LedgerService — Internal append-only ledger business logic.
 *
 * Handles Merkle tree construction, block sealing, chain verification,
 * and evidence incorporation. Each block collects pending evidence,
 * builds a canonicalized event array, computes the Merkle root,
 * chains the previous block hash, and seals with a digital signature.
 *
 * @package App\Services
 * @author  Aythami
 * @since   1.4.0
 */
class LedgerService
{
    private LedgerBlockModel $ledgerBlockModel;

    private EvidenceModel $evidenceModel;

    private string $keystorePath;

    private ?string $signingSeed = null;

    private ?string $signingPublicKey = null;

    public function __construct(?string $keystorePath = null)
    {
        $this->ledgerBlockModel = model(LedgerBlockModel::class);
        $this->evidenceModel    = model(EvidenceModel::class);
        $this->keystorePath     = $keystorePath ?? '/etc/marachain/keystore';
    }

    /**
     * Load or generate the Ed25519 signing key.
     *
     * Private key seed is stored in /etc/marachain/keystore/ledger.key.
     * If the file does not exist, a new key is generated and stored.
     * In testing/development, falls back to an in-memory key.
     *
     * @return string Ed25519 public key (hex-encoded, 64 chars)
     *
     * @throws RuntimeException If sodium is not available
     *
     * @since 1.9.0
     */
    public function loadSigningKey(): string
    {
        $keyFile = rtrim($this->keystorePath, '/') . '/ledger.key';

        if (is_file($keyFile)) {
            $seed = file_get_contents($keyFile);
        } elseif (ENVIRONMENT === 'testing') {
            if ($this->signingSeed === null) {
                $seed = random_bytes(32);
            } else {
                $seed = $this->signingSeed;
            }
        } elseif ($this->signingSeed !== null) {
            $seed = $this->signingSeed;
        } else {
            $seed = random_bytes(32);
            $dir  = dirname($keyFile);
            if (is_dir($dir) && is_writable($dir)) {
                file_put_contents($keyFile, $seed, LOCK_EX);
            }
        }

        $keyPair = sodium_crypto_sign_seed_keypair($seed);
        $this->signingSeed      = $seed;
        $this->signingPublicKey = sodium_bin2hex(
            sodium_crypto_sign_publickey($keyPair)
        );

        return $this->signingPublicKey;
    }

    /**
     * Sign a block hash with Ed25519.
     *
     * Uses sodium_crypto_sign_detached() for Ed25519 signatures.
     * The signing key must be loaded before calling this method.
     *
     * @param string $blockHash SHA-256 block hash (64 hex chars)
     *
     * @return string Base64-encoded Ed25519 signature
     *
     * @throws RuntimeException If signing key is not loaded
     *
     * @since 1.9.0
     */
    private function signBlock(string $blockHash): string
    {
        if ($this->signingSeed === null) {
            $this->loadSigningKey();
        }

        $keyPair = sodium_crypto_sign_seed_keypair($this->signingSeed);
        $secretKey = sodium_crypto_sign_secretkey($keyPair);
        $signature = sodium_crypto_sign_detached($blockHash, $secretKey);

        return base64_encode($signature);
    }

    // ═════════════════════════════════════════════════════════════════
    //  Merkle Tree
    // ═════════════════════════════════════════════════════════════════

    /**
     * Build a Merkle tree from an array of hex-encoded SHA-256 hashes.
     *
     * Uses the standard binary Merkle tree algorithm:
     *   - Pad odd leaf sets by duplicating the last element.
     *   - Compute parent = SHA-256(left || right).
     *   - Return the root hash in hexadecimal.
     *
     * An empty array produces the hash of an empty string.
     *
     * @param string[] $hashes Array of hex-encoded SHA-256 hashes (payload_hash from evidence)
     *
     * @return string Merkle root in hexadecimal (64 characters)
     *
     * @since 1.4.0
     */
    public function computeMerkleRoot(array $hashes): string
    {
        if ($hashes === []) {
            return hash('sha256', '');
        }

        $leaves = array_map([$this, 'hexToBin'], $hashes);

        while (count($leaves) > 1) {
            if (count($leaves) % 2 !== 0) {
                $leaves[] = end($leaves);
            }

            $newLevel = [];

            for ($i = 0, $count = count($leaves); $i < $count; $i += 2) {
                $newLevel[] = hash('sha256', $leaves[$i] . $leaves[$i + 1], true);
            }

            $leaves = $newLevel;
        }

        return bin2hex($leaves[0]);
    }

    // ═════════════════════════════════════════════════════════════════
    //  Block Sealing
    // ═════════════════════════════════════════════════════════════════

    /**
     * Seal a new block collecting all pending evidence.
     *
     * Steps:
     *   1. Find all evidence not yet incorporated into the ledger.
     *   2. If none, return null (nothing to seal).
     *   3. Compute the Merkle root from evidence payload hashes.
     *   4. Determine the next block number and previous block hash.
     *   5. Canonicalize events as RFC 8785 JSON.
     *   6. Compute the block hash: SHA-256(canonicalized block data).
     *   7. Sign the block hash (placeholder signature for MVP).
     *   8. Insert the block.
     *   9. Update each evidence record with the ledger block number.
     *   10. Return the sealed block.
     *
     * @param string $signingKeyFingerprint Ed25519 signing key fingerprint (64 hex chars)
     *
     * @return array{block: \App\Entities\LedgerBlock, eventCount: int, merkleRoot: string}|null
     *
     * @since 1.4.0
     */
    public function sealBlock(): ?array
    {
        // ── Transaction: block insert + evidence updates are atomic ─
        $this->ledgerBlockModel->db->transStart();

        try {
            $pendingEvidence = $this->evidenceModel->findNotInLedger();

            if ($pendingEvidence === []) {
                $this->ledgerBlockModel->db->transComplete();

                return null;
            }

            $now = date('Y-m-d H:i:s');

            $leafHashes = array_map(
                static fn ($e) => $e->payloadHash,
                $pendingEvidence
            );
            $merkleRoot = $this->computeMerkleRoot($leafHashes);

            $latestBlock     = $this->ledgerBlockModel->findLatestBlock();
            $blockNumber     = $latestBlock ? $latestBlock->blockNumber + 1 : 1;
            $previousHash    = $latestBlock ? $latestBlock->blockHash : null;

            $periodStart = min(array_map(
                static fn ($e) => (string) ($e->occurredAt ?? $now),
                $pendingEvidence
            ));
            $periodEnd = max(array_map(
                static fn ($e) => (string) ($e->occurredAt ?? $now),
                $pendingEvidence
            ));

            $events = array_map(
                static fn ($e) => (object) [
                    'eventId'       => $e->eventId,
                    'eventType'     => $e->eventType,
                    'occurredAt'    => (string) $e->occurredAt,
                    'aggregateType' => $e->aggregateType,
                    'aggregateId'   => $e->aggregateId,
                    'payloadHash'   => $e->payloadHash,
                ],
                $pendingEvidence
            );
            $eventsJson = json_encode($events, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $blockData = json_encode([
                'blockNumber'       => $blockNumber,
                'previousBlockHash' => $previousHash,
                'merkleRoot'        => $merkleRoot,
                'events'            => json_decode($eventsJson, true),
                'sealedAt'          => $now,
                'schemaVersion'     => '1.0',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $blockHash = hash('sha256', $blockData);

            $blockSignature = $this->signBlock($blockHash);

            $block = $this->ledgerBlockModel->createBlock([
                'blockNumber'          => $blockNumber,
                'periodStart'          => $periodStart,
                'periodEnd'            => $periodEnd,
                'eventCount'           => count($pendingEvidence),
                'eventsJson'           => $eventsJson,
                'merkleRoot'           => $merkleRoot,
                'previousBlockHash'    => $previousHash,
                'blockHash'            => $blockHash,
                'blockSignature'       => $blockSignature,
                'signingKeyFingerprint' => $this->signingPublicKey,
                'sealedAt'             => $now,
            ]);

            foreach ($pendingEvidence as $ev) {
                $this->evidenceModel->assignToLedger($ev->id, $blockNumber, (string) $block->id);
            }

            $this->ledgerBlockModel->db->transComplete();
        } catch (\Throwable $e) {
            $this->ledgerBlockModel->db->transRollback();

            throw $e;
        }

        return [
            'block'      => $block,
            'eventCount' => count($pendingEvidence),
            'merkleRoot' => $merkleRoot,
        ];
    }

    // ═════════════════════════════════════════════════════════════════
    //  Chain Verification
    // ═════════════════════════════════════════════════════════════════

    /**
     * Verify the integrity of the entire ledger chain.
     *
     * Checks performed:
     *   1. Each block's blockHash matches its recomputed hash.
     *   2. Each block's previousBlockHash matches the previous block's blockHash.
     *   3. Each block's Merkle root matches the recomputed root from events.
     *   4. No duplicate block numbers.
     *
     * @return array{status: string, message: string, blockCount: int, blocksChecked: int, valid: bool, errors: string[]}
     *
     * @since 1.4.0
     */
    public function verifyChain(): array
    {
        $rows = $this->ledgerBlockModel->db->table($this->ledgerBlockModel->table)
            ->orderBy('block_number', 'ASC')
            ->get()
            ->getResultArray();

        if ($rows === []) {
            return [
                'status'        => 'success',
                'message'       => 'Ledger is empty. Nothing to verify.',
                'blockCount'    => 0,
                'blocksChecked' => 0,
                'valid'         => true,
                'errors'        => [],
            ];
        }

        $errors       = [];
        $previousHash = null;
        $seenNumbers  = [];

        foreach ($rows as $row) {
            $blockNumber      = (int) $row['block_number'];
            $blockId          = $row['id'];
            $storedHash       = $row['block_hash'];
            $storedMerkle     = $row['merkle_root'];
            $previousStored   = $row['previous_block_hash'];
            $eventsJson       = $row['events_json'];
            $sealedAt         = $row['sealed_at'];
            $schemaVersion    = $row['schema_version'] ?? '1.0';

            $events = json_decode($eventsJson, true);

            if (! is_array($events)) {
                $errors[] = sprintf(
                    'Invalid events JSON at block #%d (%s)',
                    $blockNumber,
                    $blockId
                );
                continue;
            }

            // ── Duplicate block number check ──────────────────────
            if (isset($seenNumbers[$blockNumber])) {
                $errors[] = sprintf(
                    'Duplicate block number %d at block %s',
                    $blockNumber,
                    $blockId
                );
                continue;
            }
            $seenNumbers[$blockNumber] = true;

            // ── Chain linking check ───────────────────────────────
            if ($previousHash !== null && $previousStored !== $previousHash) {
                $errors[] = sprintf(
                    'Chain broken at block #%d (%s): expected previous hash %s, got %s',
                    $blockNumber,
                    $blockId,
                    $previousHash,
                    $previousStored ?? 'null'
                );
            }

            // ── Recompute Merkle root first ─────────────────────────
            // Must be BEFORE block hash so hash verification is non-tautological
            $eventHashes = array_map(
                static fn (array $e) => $e['payloadHash'] ?? '',
                $events
            );
            $recomputedMerkle = $this->computeMerkleRoot($eventHashes);

            if ($recomputedMerkle !== $storedMerkle) {
                $errors[] = sprintf(
                    'Merkle root mismatch at block #%d (%s): stored %s, recomputed %s',
                    $blockNumber,
                    $blockId,
                    $storedMerkle,
                    $recomputedMerkle
                );
            }

            // ── Recompute block hash using RECOMPUTED merkle root ──
            $recomputedData = json_encode([
                'blockNumber'       => $blockNumber,
                'previousBlockHash' => $previousStored,
                'merkleRoot'        => $recomputedMerkle,
                'events'            => $events,
                'sealedAt'          => $sealedAt,
                'schemaVersion'     => $schemaVersion,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $recomputedHash = hash('sha256', $recomputedData);

            if ($recomputedHash !== $storedHash) {
                $errors[] = sprintf(
                    'Block hash mismatch at block #%d (%s): stored %s, recomputed %s',
                    $blockNumber,
                    $blockId,
                    $storedHash,
                    $recomputedHash
                );
            }

            $previousHash = $storedHash;
        }

        $valid = count($errors) === 0;

        return [
            'status'        => $valid ? 'success' : 'failure',
            'message'       => $valid ? 'Ledger integrity verified' : 'Ledger integrity check failed',
            'blockCount'    => count($rows),
            'blocksChecked' => count($rows),
            'valid'         => $valid,
            'errors'        => $errors,
        ];
    }

    // ═════════════════════════════════════════════════════════════════
    //  Genesis Block
    // ═════════════════════════════════════════════════════════════════

    /**
     * Create the genesis block (block #1).
     *
     * The genesis block has no previous hash and contains a single
     * placeholder event marking the creation of the ledger.
     *
     * @param string $signingKeyFingerprint Ed25519 signing key fingerprint
     *
     * @return \App\Entities\LedgerBlock The genesis block entity
     *
     * @throws RuntimeException If a genesis block already exists
     *
     * @since 1.4.0
     */
    public function createGenesisBlock(): \App\Entities\LedgerBlock
    {
        $existing = $this->ledgerBlockModel->findByBlockNumber(1);

        if ($existing !== null) {
            throw new RuntimeException('Genesis block already exists (block #1).');
        }

        $now          = date('Y-m-d H:i:s');
        $genesisEvent = [[
            'eventId'       => \App\Helpers\Uuid::v4(),
            'eventType'     => 'GenesisBlock',
            'occurredAt'    => $now,
            'aggregateType' => 'Ledger',
            'aggregateId'   => \App\Helpers\Uuid::v4(),
            'payloadHash'   => hash('sha256', 'MARAChain Genesis Block'),
        ]];

        $eventsJson  = json_encode($genesisEvent, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $leafHashes  = [$genesisEvent[0]['payloadHash']];
        $merkleRoot  = $this->computeMerkleRoot($leafHashes);

        $blockData = json_encode([
            'blockNumber'       => 1,
            'previousBlockHash' => null,
            'merkleRoot'        => $merkleRoot,
            'events'            => $genesisEvent,
            'sealedAt'          => $now,
            'schemaVersion'     => '1.0',
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $blockHash      = hash('sha256', $blockData);
        $blockSignature = $this->signBlock($blockHash);

        $block = $this->ledgerBlockModel->createBlock([
            'blockNumber'           => 1,
            'periodStart'           => $now,
            'periodEnd'             => $now,
            'eventCount'            => 1,
            'eventsJson'            => $eventsJson,
            'merkleRoot'            => $merkleRoot,
            'previousBlockHash'     => null,
            'blockHash'             => $blockHash,
            'blockSignature'        => $blockSignature,
                'signingKeyFingerprint' => $this->signingPublicKey,
            'sealedAt'              => $now,
        ]);

        return $block;
    }

    // ═════════════════════════════════════════════════════════════════
    //  Helpers
    // ═════════════════════════════════════════════════════════════════

    /**
     * Convert a hex string to raw binary.
     *
     * @param string $hex Hexadecimal string
     *
     * @return string Raw binary data
     *
     * @throws InvalidArgumentException If hex string is invalid
     *
     * @since 1.4.0
     */
    private function hexToBin(string $hex): string
    {
        $bin = hex2bin($hex);

        if ($bin === false) {
            throw new InvalidArgumentException("Invalid hex string: {$hex}");
        }

        return $bin;
    }

    /**
     * Generate a UUID v4 compatible with RFC 4122.
     *
     * @return string UUID v4 in canonical 8-4-4-4-12 format
     *
     * @since 1.4.0
     */
}
