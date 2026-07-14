<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Entities\Evidence;
use App\Models\EvidenceModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use InvalidArgumentException;

/**
 * Unit tests for EvidenceModel.
 *
 * <p>RED phase: EvidenceModel does not exist yet.
 * These tests define the expected contract and MUST FAIL until
 * the model is implemented.</p>
 *
 * <p>Evidence is append-only and immutable. Records are never
 * updated or deleted once created.</p>
 *
 * @coversNothing (model does not exist yet)
 *
 * @since   1.1.1
 * @author  Aythami
 */
final class EvidenceModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    /** @var bool */
    protected $refresh = true;

    /** @var string */
    protected $namespace = 'App';

    private EvidenceModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        // This will fail because EvidenceModel does not exist (RED phase)
        $this->model = new EvidenceModel();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    // ────────────────────────────────────────────────
    //  CREATE
    // ────────────────────────────────────────────────

    /**
     * Creates an evidence record with all required fields.
     *
     * @test
     */
    public function testCreateEvidence(): void
    {
        $data = [
            'eventId'            => 'e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6',
            'eventType'          => 'LoginSuccess',
            'schemaVersion'      => '1.0',
            'occurredAt'         => '2026-07-13 10:30:00',
            'aggregateType'      => 'User',
            'aggregateId'        => 'f1e2d3c4b5a6f7e8d9c0b1a2f3e4d5c6',
            'actorId'            => 'f1e2d3c4b5a6f7e8d9c0b1a2f3e4d5c6',
            'actorType'          => 'user',
            'payloadJson'        => json_encode(['action' => 'login', 'method' => 'fnmt']),
            'payloadHash'        => 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6',
            'ipAddressTruncated' => '192.168.1.0',
        ];

        $evidence = $this->model->createEvidence($data);

        $this->assertInstanceOf(Evidence::class, $evidence);
        $this->assertNotEmpty($evidence->id);
        $this->assertSame('LoginSuccess', $evidence->eventType);
        $this->assertSame('1.0', $evidence->schemaVersion);
        $this->assertSame('User', $evidence->aggregateType);
        $this->assertSame($data['eventId'], $evidence->eventId);
        $this->assertSame($data['payloadHash'], $evidence->payloadHash);
        $this->assertNull($evidence->ledgerBlockNumber);
        $this->assertTrue($evidence->isUserAction());
    }

    /**
     * Creating evidence without an eventId must throw an exception.
     *
     * @test
     */
    public function testCreateWithoutEventId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('eventId');

        $data = [
            // eventId omitted intentionally
            'eventType'     => 'LoginSuccess',
            'schemaVersion' => '1.0',
            'occurredAt'    => '2026-07-13 10:30:00',
            'aggregateType' => 'User',
            'aggregateId'   => 'f1e2d3c4b5a6f7e8d9c0b1a2f3e4d5c6',
            'actorId'       => 'f1e2d3c4b5a6f7e8d9c0b1a2f3e4d5c6',
            'actorType'     => 'user',
            'payloadJson'   => json_encode(['action' => 'login']),
            'payloadHash'   => 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6',
        ];

        $this->model->createEvidence($data);
    }

    /**
     * Creating evidence without a payloadHash must throw an exception.
     *
     * @test
     */
    public function testCreateWithoutPayloadHash(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('payloadHash');

        $data = [
            'eventId'       => 'e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6',
            'eventType'     => 'LoginSuccess',
            'schemaVersion' => '1.0',
            'occurredAt'    => '2026-07-13 10:30:00',
            'aggregateType' => 'User',
            'aggregateId'   => 'f1e2d3c4b5a6f7e8d9c0b1a2f3e4d5c6',
            'actorId'       => 'f1e2d3c4b5a6f7e8d9c0b1a2f3e4d5c6',
            'actorType'     => 'user',
            'payloadJson'   => json_encode(['action' => 'login']),
            // payloadHash omitted intentionally
        ];

        $this->model->createEvidence($data);
    }

    // ────────────────────────────────────────────────
    //  FIND
    // ────────────────────────────────────────────────

    /**
     * Finds a single evidence record by its unique event_id.
     *
     * @test
     */
    public function testFindByEventId(): void
    {
        $eventId = bin2hex(random_bytes(16));
        $ev = $this->model->createEvidence([
            'eventId'       => $eventId,
            'eventType'     => 'LoginSuccess',
            'aggregateType' => 'User',
            'aggregateId'   => bin2hex(random_bytes(16)),
            'payloadJson'   => json_encode(['action' => 'login']),
            'payloadHash'   => str_repeat('a', 64),
            'occurredAt'    => date('Y-m-d H:i:s'),
            'actorType'     => 'user',
        ]);

        $result = $this->model->findByEventId($eventId);

        $this->assertNotNull($result);
        $this->assertInstanceOf(Evidence::class, $result);
        $this->assertSame($eventId, $result->eventId);
    }

    /**
     * Finds all evidence records for a given aggregate (e.g., a User or Document).
     *
     * @test
     */
    public function testFindByAggregateId(): void
    {
        $aggregateId = 'f1e2d3c4b5a6f7e8d9c0b1a2f3e4d5c6';

        $results = $this->model->findByAggregateId($aggregateId);

        $this->assertIsArray($results);
    }

    /**
     * Finds evidence records filtered by event type.
     *
     * @test
     */
    public function testFindByEventType(): void
    {
        $results = $this->model->findByEventType('LoginSuccess');

        $this->assertIsArray($results);

        foreach ($results as $evidence) {
            $this->assertInstanceOf(Evidence::class, $evidence);
            $this->assertSame('LoginSuccess', $evidence->eventType);
        }
    }

    /**
     * Finds evidence records within a date range.
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

    /**
     * Finds evidence records not yet incorporated into the ledger.
     *
     * @test
     */
    public function testFindNotInLedger(): void
    {
        $results = $this->model->findNotInLedger();

        $this->assertIsArray($results);

        foreach ($results as $evidence) {
            $this->assertInstanceOf(Evidence::class, $evidence);
            $this->assertNull($evidence->ledgerBlockNumber);
            $this->assertFalse($evidence->isInLedger());
        }
    }

    // ────────────────────────────────────────────────
    //  IMMUTABILITY
    // ────────────────────────────────────────────────

    /**
     * Evidence records are append-only and cannot be updated or deleted.
     *
     * @test
     */
    public function testEvidenceIsImmutable(): void
    {
        // Create evidence
        $data = [
            'eventId'       => 'e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6',
            'eventType'     => 'LoginSuccess',
            'schemaVersion' => '1.0',
            'occurredAt'    => '2026-07-13 10:30:00',
            'aggregateType' => 'User',
            'aggregateId'   => 'f1e2d3c4b5a6f7e8d9c0b1a2f3e4d5c6',
            'actorId'       => 'f1e2d3c4b5a6f7e8d9c0b1a2f3e4d5c6',
            'actorType'     => 'user',
            'payloadJson'   => json_encode(['action' => 'login']),
            'payloadHash'   => 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6',
        ];

        $evidence = $this->model->createEvidence($data);

        // Update must throw — evidence is immutable
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('immutable');

        $this->model->update($evidence->id, ['eventType' => 'TamperedEvent']);
    }
}
