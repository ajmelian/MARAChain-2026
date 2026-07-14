<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\EvidenceModel;
use App\Models\LedgerBlockModel;
use App\Services\LedgerService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Unit tests for LedgerService.
 *
 * Tests Merkle tree computation, block sealing, chain verification,
 * and genesis block creation.
 *
 * @since 1.4.0
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
final class LedgerServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    private LedgerService $service;

    private EvidenceModel $evidenceModel;

    private string $fingerprint = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    protected function setUp(): void
    {
        parent::setUp();

        $this->service       = new LedgerService();
        $this->evidenceModel = model(EvidenceModel::class);
    }

    // ──────────────────────────────────────────────────────────────
    //  Merkle Tree
    // ──────────────────────────────────────────────────────────────

    public function testMerkleRootSingleLeaf(): void
    {
        $hashes   = [hash('sha256', 'hello')];
        $merkleRoot = $this->service->computeMerkleRoot($hashes);

        $this->assertSame(64, strlen($merkleRoot));
    }

    public function testMerkleRootTwoLeaves(): void
    {
        $hash1 = hash('sha256', 'hello');
        $hash2 = hash('sha256', 'world');

        $root = $this->service->computeMerkleRoot([$hash1, $hash2]);

        $expected = hash('sha256', hex2bin($hash1) . hex2bin($hash2));
        $this->assertSame($expected, $root);
    }

    public function testMerkleRootThreeLeaves(): void
    {
        $hashes = [
            hash('sha256', 'a'),
            hash('sha256', 'b'),
            hash('sha256', 'c'),
        ];

        $root = $this->service->computeMerkleRoot($hashes);

        $this->assertSame(64, strlen($root));
    }

    public function testMerkleRootFourLeaves(): void
    {
        $hashes = [
            hash('sha256', 'a'),
            hash('sha256', 'b'),
            hash('sha256', 'c'),
            hash('sha256', 'd'),
        ];

        $root = $this->service->computeMerkleRoot($hashes);

        $this->assertSame(64, strlen($root));
    }

    public function testMerkleRootEmptyArray(): void
    {
        $root = $this->service->computeMerkleRoot([]);

        $this->assertSame(hash('sha256', ''), $root);
    }

    public function testMerkleRootDeterministic(): void
    {
        $hashes = [hash('sha256', 'x'), hash('sha256', 'y')];

        $root1 = $this->service->computeMerkleRoot($hashes);
        $root2 = $this->service->computeMerkleRoot($hashes);

        $this->assertSame($root1, $root2);
    }

    // ──────────────────────────────────────────────────────────────
    //  Genesis Block
    // ──────────────────────────────────────────────────────────────

    public function testCreateGenesisBlock(): void
    {
        $block = $this->service->createGenesisBlock($this->fingerprint);

        $this->assertSame(1, $block->blockNumber);
        $this->assertNull($block->previousBlockHash);
        $this->assertSame(64, strlen($block->blockHash));
        $this->assertSame(64, strlen($block->merkleRoot));
        $this->assertNotEmpty($block->blockSignature);
    }

    public function testCreateGenesisBlockTwiceThrowsException(): void
    {
        $this->service->createGenesisBlock($this->fingerprint);

        $this->expectException(\RuntimeException::class);
        $this->service->createGenesisBlock($this->fingerprint);
    }

    // ──────────────────────────────────────────────────────────────
    //  Block Sealing
    // ──────────────────────────────────────────────────────────────

    public function testSealBlockWithNoEvidenceReturnsNull(): void
    {
        $result = $this->service->sealBlock($this->fingerprint);

        $this->assertNull($result);
    }

    public function testSealBlockWithPendingEvidence(): void
    {
        // Create genesis first
        $this->service->createGenesisBlock($this->fingerprint);

        // Add some evidence
        $ev1 = $this->evidenceModel->createEvidence([
            'eventId'       => bin2hex(random_bytes(16)),
            'eventType'     => 'DocumentSent',
            'payloadJson'   => json_encode(['doc' => 'test1']),
            'payloadHash'   => hash('sha256', 'test1'),
            'occurredAt'    => date('Y-m-d H:i:s'),
            'aggregateType' => 'Document',
            'aggregateId'   => bin2hex(random_bytes(16)),
        ]);

        $ev2 = $this->evidenceModel->createEvidence([
            'eventId'       => bin2hex(random_bytes(16)),
            'eventType'     => 'TransferCreated',
            'payloadJson'   => json_encode(['doc' => 'test2']),
            'payloadHash'   => hash('sha256', 'test2'),
            'occurredAt'    => date('Y-m-d H:i:s'),
            'aggregateType' => 'Transfer',
            'aggregateId'   => bin2hex(random_bytes(16)),
        ]);

        $result = $this->service->sealBlock($this->fingerprint);

        $this->assertNotNull($result);
        $this->assertSame(2, $result['block']->blockNumber);
        $this->assertSame(2, $result['eventCount']);
        $this->assertSame(64, strlen($result['merkleRoot']));
        $this->assertNotNull($result['block']->previousBlockHash);
        $this->assertSame($result['block']->previousBlockHash, $result['block']->previousBlockHash);

        // Verify evidence was assigned to ledger
        $ev1after = $this->evidenceModel->findByEventId($ev1->eventId);
        $this->assertNotNull($ev1after->ledgerBlockNumber);
        $this->assertSame(2, $ev1after->ledgerBlockNumber);
    }

    // ──────────────────────────────────────────────────────────────
    //  Chain Verification
    // ──────────────────────────────────────────────────────────────

    public function testVerifyEmptyChain(): void
    {
        $report = $this->service->verifyChain();

        $this->assertTrue($report['valid']);
        $this->assertSame(0, $report['blockCount']);
    }

    public function testVerifyGenesisOnly(): void
    {
        $this->service->createGenesisBlock($this->fingerprint);

        $report = $this->service->verifyChain();

        $this->assertTrue($report['valid']);
        $this->assertSame(1, $report['blockCount']);
        $this->assertEmpty($report['errors']);
    }

    public function testVerifyChainAfterSealing(): void
    {
        // Genesis
        $this->service->createGenesisBlock($this->fingerprint);

        // Add evidence and seal
        $this->evidenceModel->createEvidence([
            'eventId'       => bin2hex(random_bytes(16)),
            'eventType'     => 'TestEvent',
            'payloadJson'   => json_encode(['x' => 1]),
            'payloadHash'   => hash('sha256', 'proof'),
            'occurredAt'    => date('Y-m-d H:i:s'),
            'aggregateType' => 'Test',
            'aggregateId'   => bin2hex(random_bytes(16)),
        ]);

        $this->service->sealBlock($this->fingerprint);

        $report = $this->service->verifyChain();

        $this->assertTrue($report['valid'], 'Chain verification failed: ' . implode(', ', $report['errors']));
        $this->assertSame(2, $report['blockCount']);
        $this->assertEmpty($report['errors']);
    }

    // ──────────────────────────────────────────────────────────────
    //  Tamper Detection
    // ──────────────────────────────────────────────────────────────

    public function testTamperedBlockHashDetected(): void
    {
        $this->service->createGenesisBlock($this->fingerprint);

        // Manually tamper with the block hash
        $model = model(LedgerBlockModel::class);
        $block = $model->findLatestBlock();

        $model->db->table($model->table)
            ->where('id', $block->id)
            ->update(['block_hash' => str_repeat('0', 64)]);

        $report = $this->service->verifyChain();

        $this->assertFalse($report['valid']);
        $this->assertNotEmpty($report['errors']);
        $this->assertStringContainsString('hash mismatch', $report['errors'][0]);
    }
}
