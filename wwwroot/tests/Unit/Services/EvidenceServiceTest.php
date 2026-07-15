<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\EvidenceModel;
use App\Services\EvidenceService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Unit tests for EvidenceService.
 *
 * Tests automated business event recording for all event types:
 * DocumentSent, TransferAccessed, TransferDownloaded, TransferAccepted,
 * TransferRejected, TransferRevoked, and DocumentSealed.
 *
 * @since 1.4.0
 * @author Aythami
 */
final class EvidenceServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

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

    private EvidenceService $service;

    private EvidenceModel $evidenceModel;

    private string $transferId;

    private string $senderId;

    private string $recipientId;

    private string $documentId;

    /**
     * Prepare test environment before each test.
     *
     * Creates UUIDs used as aggregate/actor identifiers.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->service       = new EvidenceService();
        $this->evidenceModel = model(EvidenceModel::class);

        $this->transferId  = \App\Helpers\Uuid::v4();
        $this->senderId    = \App\Helpers\Uuid::v4();
        $this->recipientId = \App\Helpers\Uuid::v4();
        $this->documentId  = \App\Helpers\Uuid::v4();
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->service, $this->evidenceModel);
    }

    /**
     * Asserts that exactly one evidence record exists with the given event type.
     *
     * @param string $expectedEventType The expected event type string.
     *
     * @return \App\Entities\Evidence The matching evidence entity.
     */
    private function assertSingleEvidenceOfType(string $expectedEventType): \App\Entities\Evidence
    {
        $records = $this->evidenceModel->findByEventType($expectedEventType);

        $this->assertCount(1, $records, "Expected exactly one '{$expectedEventType}' evidence record.");
        $this->assertNotEmpty($records[0]->eventId);
        $this->assertNotEmpty($records[0]->payloadHash);

        return $records[0];
    }

    // ──────────────────────────────────────────────────────────────
    // DocumentSent
    // ──────────────────────────────────────────────────────────────

    /**
     * documentSent creates an evidence record with eventType DocumentSent.
     *
     * @test
     */
    public function testDocumentSent(): void
    {
        $this->service->documentSent(
            $this->transferId,
            $this->senderId,
            $this->recipientId,
            $this->documentId,
        );

        $evidence = $this->assertSingleEvidenceOfType('DocumentSent');

        $this->assertSame('DocumentTransfer', $evidence->aggregateType);
        $this->assertSame($this->transferId, $evidence->aggregateId);
    }

    // ──────────────────────────────────────────────────────────────
    // TransferAccessed
    // ──────────────────────────────────────────────────────────────

    /**
     * transferAccessed creates an evidence record for access events.
     *
     * @test
     */
    public function testTransferAccessed(): void
    {
        $this->service->transferAccessed($this->transferId, $this->recipientId);

        $evidence = $this->assertSingleEvidenceOfType('TransferAccessed');

        $this->assertSame('DocumentTransfer', $evidence->aggregateType);
        $this->assertSame($this->transferId, $evidence->aggregateId);
    }

    // ──────────────────────────────────────────────────────────────
    // TransferDownloaded
    // ──────────────────────────────────────────────────────────────

    /**
     * transferDownloaded creates an evidence record for download events.
     *
     * @test
     */
    public function testTransferDownloaded(): void
    {
        $this->service->transferDownloaded($this->transferId, $this->recipientId);

        $evidence = $this->assertSingleEvidenceOfType('TransferDownloaded');

        $this->assertSame('DocumentTransfer', $evidence->aggregateType);
        $this->assertSame($this->transferId, $evidence->aggregateId);
    }

    // ──────────────────────────────────────────────────────────────
    // TransferAccepted
    // ──────────────────────────────────────────────────────────────

    /**
     * transferAccepted creates an evidence record for accept events.
     *
     * @test
     */
    public function testTransferAccepted(): void
    {
        $this->service->transferAccepted($this->transferId, $this->recipientId);

        $evidence = $this->assertSingleEvidenceOfType('TransferAccepted');

        $this->assertSame('DocumentTransfer', $evidence->aggregateType);
        $this->assertSame($this->transferId, $evidence->aggregateId);
    }

    // ──────────────────────────────────────────────────────────────
    // TransferRejected
    // ──────────────────────────────────────────────────────────────

    /**
     * transferRejected creates an evidence record with a reason.
     *
     * @test
     */
    public function testTransferRejected(): void
    {
        $reason = 'Document content does not match expected version.';

        $this->service->transferRejected($this->transferId, $this->recipientId, $reason);

        $evidence = $this->assertSingleEvidenceOfType('TransferRejected');

        $this->assertSame('DocumentTransfer', $evidence->aggregateType);
        $this->assertSame($this->transferId, $evidence->aggregateId);

        // Verify the reason is embedded in the payload
        $payload = json_decode($evidence->payloadJson, true);
        $this->assertIsArray($payload);
        $this->assertSame($reason, $payload['reason']);
    }

    // ──────────────────────────────────────────────────────────────
    // TransferRevoked
    // ──────────────────────────────────────────────────────────────

    /**
     * transferRevoked creates an evidence record for revoke events.
     *
     * @test
     */
    public function testTransferRevoked(): void
    {
        $this->service->transferRevoked($this->transferId, $this->senderId);

        $evidence = $this->assertSingleEvidenceOfType('TransferRevoked');

        $this->assertSame('DocumentTransfer', $evidence->aggregateType);
        $this->assertSame($this->transferId, $evidence->aggregateId);
    }

    // ──────────────────────────────────────────────────────────────
    // DocumentSealed
    // ──────────────────────────────────────────────────────────────

    /**
     * documentSealed creates an evidence record for seal events.
     *
     * @test
     */
    public function testDocumentSealed(): void
    {
        $this->service->documentSealed($this->documentId, $this->senderId);

        $evidence = $this->assertSingleEvidenceOfType('DocumentSealed');

        $this->assertSame('Document', $evidence->aggregateType);
        $this->assertSame($this->documentId, $evidence->aggregateId);
    }

    // ──────────────────────────────────────────────────────────────
    // Multiple events
    // ──────────────────────────────────────────────────────────────

    /**
     * Recording multiple events creates separate evidence records
     * each with its own eventId.
     *
     * @test
     */
    public function testMultipleEventsCreateDistinctRecords(): void
    {
        $this->service->documentSent(
            $this->transferId, $this->senderId, $this->recipientId, $this->documentId,
        );
        $this->service->transferAccessed($this->transferId, $this->recipientId);
        $this->service->transferAccepted($this->transferId, $this->recipientId);

        $allForTransfer = $this->evidenceModel->findByAggregateId($this->transferId);

        $this->assertCount(3, $allForTransfer);

        $eventTypes = array_map(static fn ($e) => $e->eventType, $allForTransfer);
        $this->assertContains('DocumentSent', $eventTypes);
        $this->assertContains('TransferAccessed', $eventTypes);
        $this->assertContains('TransferAccepted', $eventTypes);
    }
}
