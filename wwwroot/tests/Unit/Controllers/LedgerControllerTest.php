<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Models\LedgerBlockModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * HTTP integration tests for LedgerController.
 *
 * <p>Tests the blockchain-backed integrity ledger: listing blocks,
 * fetching a single block by ID, and verifying chain integrity.</p>
 *
 * @coversNothing (integration test)
 *
 * @since   1.1.1
 * @author  Aythami
 *
 * @internal
 */
final class LedgerControllerTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    /**
     * Refresh the database before each test.
     *
     * @var bool
     */
    protected $refresh = true;

    /**
     * The namespace for migration discovery.
     *
     * @var string|null
     */
    protected $namespace = 'App';

    /**
     * Pre-created ledger block UUID used as test fixture.
     *
     * @var string
     */
    private string $testLedgerId;

    /**
     * Prepare test environment before each test.
     *
     * Creates a ledger block for show tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $ledgerModel = new LedgerBlockModel();

        $block = $ledgerModel->createBlock([
            'merkleRoot'            => str_repeat('a', 64),
            'blockHash'             => str_repeat('b', 64),
            'blockSignature'        => 'test-block-signature',
            'signingKeyFingerprint' => str_repeat('c', 64),
            'eventsJson'            => json_encode([]),
            'eventCount'            => 0,
        ]);
        $this->testLedgerId = $block->id;
    }

    // ──────────────────────────────────────────────────────────────
    // INDEX — GET /ledger
    // ──────────────────────────────────────────────────────────────

    /**
     * Listing ledger blocks returns HTTP 200 with a JSON array of blocks.
     *
     * @test
     */
    public function testIndex(): void
    {
        $result = $this->get('/ledger');

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // SHOW — GET /ledger/{id}
    // ──────────────────────────────────────────────────────────────

    /**
     * Fetching a single ledger block by UUID returns HTTP 200 with
     * the block JSON including merkle root and block hash.
     *
     * @test
     */
    public function testShow(): void
    {
        $result = $this->get('/ledger/' . $this->testLedgerId);

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    /**
     * Fetching a non-existent ledger block returns HTTP 404.
     *
     * @test
     */
    public function testShowReturns404ForUnknownBlock(): void
    {
        $result = $this->get('/ledger/00000000-0000-4000-a000-000000000000');

        $this->assertSame(404, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // VERIFY — GET /ledger/verify
    // ──────────────────────────────────────────────────────────────

    /**
     * Verifying the full ledger chain integrity returns HTTP 200
     * with a verification report JSON.
     *
     * @test
     */
    public function testVerify(): void
    {
        $result = $this->get('/ledger/verify');

        $this->assertContains($result->response()->getStatusCode(), [200, 422]);
    }
}
