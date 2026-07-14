<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Entities\LedgerBlock;
use App\Models\LedgerBlockModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use InvalidArgumentException;

/**
 * Unit tests for LedgerBlockModel.
 *
 * <p>RED phase: LedgerBlockModel does not exist yet.
 * These tests define the expected contract and MUST FAIL until
 * the model is implemented.</p>
 *
 * <p>Blocks are append-only and immutable. Once sealed, a block
 * can never be updated or deleted. The chain integrity is maintained
 * via previousBlockHash chaining.</p>
 *
 * @coversNothing (model does not exist yet)
 *
 * @since   1.1.1
 * @author  Aythami
 */
final class LedgerBlockModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    /** @var bool */
    protected $refresh = true;

    /** @var string */
    protected $namespace = 'App';

    private LedgerBlockModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        // This will fail because LedgerBlockModel does not exist (RED phase)
        $this->model = new LedgerBlockModel();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    // ────────────────────────────────────────────────
    //  CREATE
    // ────────────────────────────────────────────────

    /**
     * Creates a ledger block with all required fields.
     *
     * @test
     */
    public function testCreateBlock(): void
    {
        $data = [
            'blockNumber'          => 2,
            'periodStart'          => '2026-07-13 10:00:00',
            'periodEnd'            => '2026-07-13 11:00:00',
            'eventCount'           => 5,
            'eventsJson'           => json_encode([
                ['eventId' => 'evt-1', 'type' => 'LoginSuccess'],
                ['eventId' => 'evt-2', 'type' => 'DocumentCreated'],
            ]),
            'merkleRoot'           => 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6',
            'previousBlockHash'    => 'b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1',
            'blockHash'            => 'c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2',
            'blockSignature'       => 'MEUCIQDx...base64signature...',
            'signingKeyFingerprint' => 'd4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3',
            'sealedAt'             => '2026-07-13 11:05:00',
        ];

        $block = $this->model->createBlock($data);

        $this->assertInstanceOf(LedgerBlock::class, $block);
        $this->assertNotEmpty($block->id);
        $this->assertSame(2, $block->blockNumber);
        $this->assertSame(5, $block->eventCount);
        $this->assertSame($data['merkleRoot'], $block->merkleRoot);
        $this->assertSame($data['blockHash'], $block->blockHash);
        $this->assertSame($data['previousBlockHash'], $block->previousBlockHash);
        $this->assertFalse($block->isGenesisBlock());
        $this->assertTrue($block->hasValidMerkleRoot());
        $this->assertTrue($block->hasValidBlockHash());
    }

    /**
     * Creates a genesis block (first block) where previousBlockHash is null.
     *
     * @test
     */
    public function testCreateGenesisBlock(): void
    {
        $data = [
            'blockNumber'          => 1,
            'periodStart'          => '2026-07-13 00:00:00',
            'periodEnd'            => '2026-07-13 01:00:00',
            'eventCount'           => 0,
            'eventsJson'           => json_encode([]),
            'merkleRoot'           => '0000000000000000000000000000000000000000000000000000000000000000',
            'previousBlockHash'    => null, // null for genesis
            'blockHash'            => 'e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4',
            'blockSignature'       => 'MEQCIE...genesisSignature...',
            'signingKeyFingerprint' => 'f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5',
            'sealedAt'             => '2026-07-13 01:05:00',
        ];

        $block = $this->model->createBlock($data);

        $this->assertInstanceOf(LedgerBlock::class, $block);
        $this->assertSame(1, $block->blockNumber);
        $this->assertNull($block->previousBlockHash);
        $this->assertTrue($block->isGenesisBlock());
    }

    /**
     * Creating a block without a merkleRoot must throw an exception.
     *
     * @test
     */
    public function testCreateBlockWithoutMerkleRoot(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('merkleRoot');

        $data = [
            'blockNumber'          => 3,
            'periodStart'          => '2026-07-13 11:00:00',
            'periodEnd'            => '2026-07-13 12:00:00',
            'eventCount'           => 3,
            'eventsJson'           => json_encode([['eventId' => 'evt-3']]),
            // merkleRoot omitted intentionally
            'previousBlockHash'    => 'c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2',
            'blockHash'            => 'a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6',
            'blockSignature'       => 'MEYCIQD...signature...',
            'signingKeyFingerprint' => 'b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7',
            'sealedAt'             => '2026-07-13 12:05:00',
        ];

        $this->model->createBlock($data);
    }

    // ────────────────────────────────────────────────
    //  FIND
    // ────────────────────────────────────────────────

    /**
     * Finds a block by its sequential block number.
     *
     * @test
     */
    public function testFindByBlockNumber(): void
    {
        $block = $this->model->createBlock([
            'blockNumber'          => 1,
            'periodStart'          => '2026-07-13 10:00:00',
            'periodEnd'            => '2026-07-13 12:00:00',
            'eventCount'           => 1,
            'eventsJson'           => json_encode([]),
            'merkleRoot'           => 'c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2',
            'blockHash'            => 'b1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7',
            'blockSignature'       => 'MEYCIQD...signature...',
            'signingKeyFingerprint' => 'b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7',
            'sealedAt'             => '2026-07-13 12:05:00',
        ]);

        $result = $this->model->findByBlockNumber(1);

        $this->assertNotNull($result);
        $this->assertInstanceOf(LedgerBlock::class, $result);
        $this->assertSame(1, $result->blockNumber);
    }

    /**
     * Finds a block by its SHA-256 block hash.
     *
     * @test
     */
    public function testFindByBlockHash(): void
    {
        $blockHash = 'c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2';
        $this->model->createBlock([
            'blockNumber'          => 2,
            'periodStart'          => '2026-07-13 10:00:00',
            'periodEnd'            => '2026-07-13 12:00:00',
            'eventCount'           => 1,
            'eventsJson'           => json_encode([]),
            'merkleRoot'           => 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7',
            'blockHash'            => $blockHash,
            'blockSignature'       => 'MEYCIQD...signature...',
            'signingKeyFingerprint' => 'b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7',
            'sealedAt'             => '2026-07-13 12:05:00',
        ]);

        $result = $this->model->findByBlockHash($blockHash);

        $this->assertNotNull($result);
        $this->assertInstanceOf(LedgerBlock::class, $result);
        $this->assertSame($blockHash, $result->blockHash);
    }

    /**
     * Finds the latest (most recent) block with the highest block_number.
     *
     * @test
     */
    public function testFindLatestBlock(): void
    {
        $this->model->createBlock([
            'blockNumber'          => 3,
            'periodStart'          => '2026-07-13 10:00:00',
            'periodEnd'            => '2026-07-13 12:00:00',
            'eventCount'           => 1,
            'eventsJson'           => json_encode([]),
            'merkleRoot'           => 'd4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4',
            'blockHash'            => 'e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4',
            'blockSignature'       => 'MEYCIQD...signature...',
            'signingKeyFingerprint' => 'b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7',
            'sealedAt'             => '2026-07-13 12:05:00',
        ]);

        $result = $this->model->findLatestBlock();

        $this->assertNotNull($result);
        $this->assertInstanceOf(LedgerBlock::class, $result);
        $this->assertGreaterThan(0, $result->blockNumber);
    }

    /**
     * Finds blocks sealed within a date range.
     *
     * @test
     */
    public function testFindByDateRange(): void
    {
        $start = '2026-07-01 00:00:00';
        $end   = '2026-07-31 23:59:59';

        $results = $this->model->findByDateRange($start, $end);

        $this->assertIsArray($results);
    }

    // ────────────────────────────────────────────────
    //  CHAIN INTEGRITY
    // ────────────────────────────────────────────────

    /**
     * Verifies that the chain is consistent: previousBlockHash of block N+1
     * must equal blockHash of block N.
     *
     * @test
     */
    public function testChainIntegrity(): void
    {
        // Create two chained blocks
        $block1 = $this->model->createBlock([
            'blockNumber'          => 10,
            'periodStart'          => '2026-07-13 10:00:00',
            'periodEnd'            => '2026-07-13 11:00:00',
            'eventCount'           => 1,
            'eventsJson'           => json_encode([['eventId' => 'evt-10']]),
            'merkleRoot'           => 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6',
            'previousBlockHash'    => '0000000000000000000000000000000000000000000000000000000000000000',
            'blockHash'            => 'b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6a7b8c9d0a1b2c3d4e5f6a7b8c9d0e1f2',
            'blockSignature'       => 'MEUCIQD...sig1...',
            'signingKeyFingerprint' => 'c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8',
            'sealedAt'             => '2026-07-13 11:05:00',
        ]);

        $block2 = $this->model->createBlock([
            'blockNumber'          => 11,
            'periodStart'          => '2026-07-13 11:00:00',
            'periodEnd'            => '2026-07-13 12:00:00',
            'eventCount'           => 1,
            'eventsJson'           => json_encode([['eventId' => 'evt-11']]),
            'merkleRoot'           => 'd0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9',
            'previousBlockHash'    => $block1->blockHash,
            'blockHash'            => 'e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0',
            'blockSignature'       => 'MEQCIQD...sig2...',
            'signingKeyFingerprint' => 'f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1',
            'sealedAt'             => '2026-07-13 12:05:00',
        ]);

        // Chain integrity: block2's previousBlockHash must equal block1's blockHash
        $this->assertSame($block1->blockHash, $block2->previousBlockHash);
        $this->assertSame(11, $block2->blockNumber);
        $this->assertSame(10, $block1->blockNumber);
    }

    // ────────────────────────────────────────────────
    //  IMMUTABILITY
    // ────────────────────────────────────────────────

    /**
     * Blocks are append-only and cannot be updated after sealing.
     *
     * @test
     */
    public function testBlockIsImmutable(): void
    {
        $block = $this->model->createBlock([
            'blockNumber'          => 20,
            'periodStart'          => '2026-07-13 10:00:00',
            'periodEnd'            => '2026-07-13 11:00:00',
            'eventCount'           => 1,
            'eventsJson'           => json_encode([['eventId' => 'evt-20']]),
            'merkleRoot'           => 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6',
            'previousBlockHash'    => '0000000000000000000000000000000000000000000000000000000000000000',
            'blockHash'            => 'f3a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4',
            'blockSignature'       => 'MEYCIQD...sigImmutable...',
            'signingKeyFingerprint' => 'a4b5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0d1e2f3a4b5',
            'sealedAt'             => '2026-07-13 11:05:00',
        ]);

        // Attempting to update a sealed block must throw
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('immutable');

        $this->model->update($block->id, ['merkleRoot' => 'tampered']);
    }
}
