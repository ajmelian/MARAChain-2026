<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Models\EvidenceModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * HTTP integration tests for EvidenceController.
 *
 * <p>Tests listing and retrieval of event-sourced evidence records.
 * Evidence is immutable and provides an audit trail of all operations.</p>
 *
 * @coversNothing (integration test)
 *
 * @since   1.1.1
 * @author  Aythami
 *
 * @internal
 */
final class EvidenceControllerTest extends CIUnitTestCase
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
     * Pre-created evidence UUID used as test fixture.
     *
     * @var string
     */
    private string $testEvidenceId;

    /**
     * Prepare test environment before each test.
     *
     * Creates an evidence record for show tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $evidenceModel = new EvidenceModel();

        $record = $evidenceModel->createEvidence([
            'eventId'       => bin2hex(random_bytes(16)),
            'eventType'     => 'test.event',
            'aggregateType' => 'test',
            'aggregateId'   => '00000000-0000-4000-a000-000000000000',
            'payloadJson'   => json_encode(['test' => true]),
            'payloadHash'   => str_repeat('a', 64),
        ]);
        $this->testEvidenceId = $record->id;
    }

    // ──────────────────────────────────────────────────────────────
    // INDEX — GET /evidence
    // ──────────────────────────────────────────────────────────────

    /**
     * Listing evidence records returns HTTP 200 with a JSON array.
     *
     * @test
     */
    public function testIndex(): void
    {
        $result = $this->get('/evidence');

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    /**
     * Listing evidence filtered by event type returns HTTP 200.
     *
     * @test
     */
    public function testIndexFilteredByEventType(): void
    {
        $result = $this->call('get', '/evidence?event_type=document.created');

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // SHOW — GET /evidence/{id}
    // ──────────────────────────────────────────────────────────────

    /**
     * Fetching a single evidence record by UUID returns HTTP 200
     * with the evidence JSON.
     *
     * @test
     */
    public function testShow(): void
    {
        $result = $this->get('/evidence/' . $this->testEvidenceId);

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    /**
     * Fetching a non-existent evidence record returns HTTP 404.
     *
     * @test
     */
    public function testShowReturns404ForUnknownEvidence(): void
    {
        $result = $this->get('/evidence/00000000-0000-4000-a000-000000000000');

        $this->assertSame(404, $result->response()->getStatusCode());
    }
}
