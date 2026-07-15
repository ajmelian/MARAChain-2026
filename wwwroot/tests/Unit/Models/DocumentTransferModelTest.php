<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Entities\Document;
use App\Entities\DocumentTransfer;
use App\Entities\User;
use App\Models\DocumentModel;
use App\Models\DocumentTransferModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Unit tests for DocumentTransferModel.
 *
 * Tests the DocumentTransfer persistence layer covering creation,
 * retrieval by sender/recipient/document, status transitions,
 * invalid transition guarding, and revocation.
 *
 * @coversNothing (RED phase — model class does not exist yet)
 *
 * @since   1.1.1
 * @author  Aythami
 *
 * @internal
 */
final class DocumentTransferModelTest extends CIUnitTestCase
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

    /**
     * DocumentTransferModel instance under test.
     *
     * @var DocumentTransferModel
     */
    private DocumentTransferModel $model;

    /**
     * Pre-created sender user.
     *
     * @var User
     */
    private User $sender;

    /**
     * Pre-created recipient user.
     *
     * @var User
     */
    private User $recipient;

    /**
     * Pre-created document for transfers.
     *
     * @var Document
     */
    private Document $document;

    /**
     * Prepare test environment before each test.
     *
     * In RED phase, DocumentTransferModel does not exist yet — the setUp
     * will fail with a fatal error. This is expected TDD behaviour.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // RED PHASE: These lines fail because the models do not exist yet.
        $this->model = new DocumentTransferModel();

        $userModel = new UserModel();
        $this->sender = $userModel->create([
            'firstName'    => 'Transfer',
            'lastName'     => 'Sender',
            'email'        => 'transfer.sender@example.com',
            'identityType' => 'physical',
        ]);

        $this->recipient = $userModel->create([
            'firstName'    => 'Transfer',
            'lastName'     => 'Recipient',
            'email'        => 'transfer.recipient@example.com',
            'identityType' => 'physical',
        ]);

        $documentModel = new DocumentModel();
        $this->document = $documentModel->create([
            'title'          => 'Transferable Document',
            'ownerId'        => $this->sender->id,
            'mimeType'       => 'application/pdf',
            'fileSize'       => 8192,
            'fileHashSha256' => str_repeat('t', 64),
        ]);
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->model, $this->sender, $this->recipient, $this->document);
    }

    // ──────────────────────────────────────────────────────────────
    // CREATE
    // ──────────────────────────────────────────────────────────────

    /**
     * Creates a transfer with valid data and expects a DocumentTransfer entity.
     *
     * @test
     */
    public function testCreateTransfer(): void
    {
        $idempotencyKey = str_repeat('a', 64);

        $transfer = $this->model->create([
            'documentId'      => $this->document->id,
            'senderId'        => $this->sender->id,
            'recipientId'     => $this->recipient->id,
            'idempotencyKey'  => $idempotencyKey,
            'securityLevel'   => 'standard',
        ]);

        $this->assertInstanceOf(DocumentTransfer::class, $transfer);
        $this->assertNotEmpty($transfer->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $transfer->id,
        );
        $this->assertSame($this->document->id, $transfer->documentId);
        $this->assertSame($this->sender->id, $transfer->senderId);
        $this->assertSame($this->recipient->id, $transfer->recipientId);
        $this->assertSame($idempotencyKey, $transfer->idempotencyKey);
        $this->assertSame('PENDING_RECIPIENT', $transfer->status);
    }

    /**
     * Attempting to create a transfer without an idempotency key throws an exception.
     *
     * @test
     */
    public function testCreateTransferWithoutIdempotencyKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->model->create([
            'documentId'    => $this->document->id,
            'senderId'      => $this->sender->id,
            'recipientId'   => $this->recipient->id,
            'securityLevel' => 'standard',
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // FIND
    // ──────────────────────────────────────────────────────────────

    /**
     * Finding transfers by sender ID returns all sent transfers.
     *
     * @test
     */
    public function testFindBySenderId(): void
    {
        $this->model->create([
            'documentId'      => $this->document->id,
            'senderId'        => $this->sender->id,
            'recipientId'     => $this->recipient->id,
            'idempotencyKey'  => str_repeat('b', 64),
            'securityLevel'   => 'standard',
        ]);

        $this->model->create([
            'documentId'      => $this->document->id,
            'senderId'        => $this->sender->id,
            'recipientId'     => $this->recipient->id,
            'idempotencyKey'  => str_repeat('c', 64),
            'securityLevel'   => 'signed',
        ]);

        $transfers = $this->model->findBySenderId($this->sender->id);

        $this->assertIsArray($transfers);
        $this->assertCount(2, $transfers);
        $this->assertContainsOnlyInstancesOf(DocumentTransfer::class, $transfers);

        foreach ($transfers as $t) {
            $this->assertSame($this->sender->id, $t->senderId);
        }
    }

    /**
     * Finding transfers by recipient ID returns all received transfers.
     *
     * @test
     */
    public function testFindByRecipientId(): void
    {
        $this->model->create([
            'documentId'      => $this->document->id,
            'senderId'        => $this->sender->id,
            'recipientId'     => $this->recipient->id,
            'idempotencyKey'  => str_repeat('d', 64),
            'securityLevel'   => 'standard',
        ]);

        $transfers = $this->model->findByRecipientId($this->recipient->id);

        $this->assertIsArray($transfers);
        $this->assertCount(1, $transfers);
        $this->assertSame($this->recipient->id, $transfers[0]->recipientId);
    }

    /**
     * Finding transfers by document ID returns all transfers for that document.
     *
     * @test
     */
    public function testFindByDocumentId(): void
    {
        $this->model->create([
            'documentId'      => $this->document->id,
            'senderId'        => $this->sender->id,
            'recipientId'     => $this->recipient->id,
            'idempotencyKey'  => str_repeat('e', 64),
            'securityLevel'   => 'standard',
        ]);

        // Create a second recipient for the same document.
        $recipient2 = (new UserModel())->create([
            'firstName'    => 'Second',
            'lastName'     => 'Recipient',
            'email'        => 'recipient2@example.com',
            'identityType' => 'physical',
        ]);

        $this->model->create([
            'documentId'      => $this->document->id,
            'senderId'        => $this->sender->id,
            'recipientId'     => $recipient2->id,
            'idempotencyKey'  => str_repeat('f', 64),
            'securityLevel'   => 'standard',
        ]);

        $transfers = $this->model->findByDocumentId($this->document->id);

        $this->assertIsArray($transfers);
        $this->assertCount(2, $transfers);

        foreach ($transfers as $t) {
            $this->assertSame($this->document->id, $t->documentId);
        }
    }

    /**
     * Finding transfers by status filters correctly.
     *
     * @test
     */
    public function testFindByStatus(): void
    {
        // Create a transfer and advance it to READY.
        $transfer = $this->model->create([
            'documentId'      => $this->document->id,
            'senderId'        => $this->sender->id,
            'recipientId'     => $this->recipient->id,
            'idempotencyKey'  => str_repeat('g', 64),
            'securityLevel'   => 'standard',
        ]);

        $this->model->transitionStatus($transfer, 'READY');

        $pending = $this->model->findByStatus('PENDING_RECIPIENT');
        $ready   = $this->model->findByStatus('READY');

        $this->assertCount(0, $pending);
        $this->assertCount(1, $ready);
        $this->assertSame('READY', $ready[0]->status);
    }

    // ──────────────────────────────────────────────────────────────
    // STATUS TRANSITIONS
    // ──────────────────────────────────────────────────────────────

    /**
     * A valid status transition updates the status and related timestamps.
     *
     * @test
     */
    public function testTransitionStatus(): void
    {
        $transfer = $this->model->create([
            'documentId'      => $this->document->id,
            'senderId'        => $this->sender->id,
            'recipientId'     => $this->recipient->id,
            'idempotencyKey'  => str_repeat('h', 64),
            'securityLevel'   => 'standard',
        ]);

        // PENDING_RECIPIENT → READY (valid transition).
        $result = $this->model->transitionStatus($transfer, 'READY');

        $this->assertSame('READY', $result->status);
    }

    /**
     * An invalid status transition throws an exception.
     *
     * @test
     */
    public function testInvalidTransition(): void
    {
        $transfer = $this->model->create([
            'documentId'      => $this->document->id,
            'senderId'        => $this->sender->id,
            'recipientId'     => $this->recipient->id,
            'idempotencyKey'  => str_repeat('i', 64),
            'securityLevel'   => 'standard',
        ]);

        // PENDING_RECIPIENT → ACCEPTED is NOT a valid transition.
        $this->expectException(\RuntimeException::class);
        $this->model->transitionStatus($transfer, 'ACCEPTED');
    }

    // ──────────────────────────────────────────────────────────────
    // REVOKE
    // ──────────────────────────────────────────────────────────────

    /**
     * Revoking a transfer sets status to REVOKED and records revokedAt.
     *
     * @test
     */
    public function testRevokeTransfer(): void
    {
        $transfer = $this->model->create([
            'documentId'      => $this->document->id,
            'senderId'        => $this->sender->id,
            'recipientId'     => $this->recipient->id,
            'idempotencyKey'  => str_repeat('j', 64),
            'securityLevel'   => 'standard',
        ]);

        // Advance to a revocable state first.
        $this->model->transitionStatus($transfer, 'READY');
        $this->model->transitionStatus($transfer, 'SENDING');
        $this->model->transitionStatus($transfer, 'SENT');
        $this->model->transitionStatus($transfer, 'AVAILABLE');

        $result = $this->model->revokeTransfer($transfer);

        $this->assertSame('REVOKED', $result->status);
        $this->assertNotNull($result->revokedAt);
    }

    // ──────────────────────────────────────────────────────────────
    // FULL TRANSITION CHAIN
    // ──────────────────────────────────────────────────────────────

    /**
     * Tests the complete valid transition chain end-to-end.
     *
     * PENDING_RECIPIENT → READY → SENDING → SENT → AVAILABLE →
     * ACCESSED → DOWNLOADED → ACCEPTED
     *
     * @test
     */
    public function testFullTransitionChain(): void
    {
        $transfer = $this->model->create([
            'documentId'      => $this->document->id,
            'senderId'        => $this->sender->id,
            'recipientId'     => $this->recipient->id,
            'idempotencyKey'  => str_repeat('k', 64),
            'securityLevel'   => 'standard',
        ]);

        $this->assertSame('PENDING_RECIPIENT', $transfer->status);

        // PENDING_RECIPIENT → READY
        $transfer = $this->model->transitionStatus($transfer, 'READY');
        $this->assertSame('READY', $transfer->status);

        // READY → SENDING
        $transfer = $this->model->transitionStatus($transfer, 'SENDING');
        $this->assertSame('SENDING', $transfer->status);

        // SENDING → SENT
        $transfer = $this->model->transitionStatus($transfer, 'SENT');
        $this->assertSame('SENT', $transfer->status);

        // SENT → AVAILABLE
        $transfer = $this->model->transitionStatus($transfer, 'AVAILABLE');
        $this->assertSame('AVAILABLE', $transfer->status);

        // AVAILABLE → ACCESSED
        $transfer = $this->model->transitionStatus($transfer, 'ACCESSED');
        $this->assertSame('ACCESSED', $transfer->status);

        // ACCESSED → DOWNLOADED
        $transfer = $this->model->transitionStatus($transfer, 'DOWNLOADED');
        $this->assertSame('DOWNLOADED', $transfer->status);

        // DOWNLOADED → ACCEPTED
        $transfer = $this->model->transitionStatus($transfer, 'ACCEPTED');
        $this->assertSame('ACCEPTED', $transfer->status);

        $this->assertTrue($transfer->isTerminal());
    }

    /**
     * Transitioning from ACCEPTED (terminal) to any status fails.
     *
     * @test
     */
    public function testCannotTransitionFromAccepted(): void
    {
        $transfer = $this->model->create([
            'documentId'      => $this->document->id,
            'senderId'        => $this->sender->id,
            'recipientId'     => $this->recipient->id,
            'idempotencyKey'  => str_repeat('l', 64),
            'securityLevel'   => 'standard',
        ]);

        // Advance to ACCEPTED
        $transfer = $this->model->transitionStatus($transfer, 'READY');
        $transfer = $this->model->transitionStatus($transfer, 'SENDING');
        $transfer = $this->model->transitionStatus($transfer, 'SENT');
        $transfer = $this->model->transitionStatus($transfer, 'AVAILABLE');
        $transfer = $this->model->transitionStatus($transfer, 'ACCESSED');
        $transfer = $this->model->transitionStatus($transfer, 'DOWNLOADED');
        $transfer = $this->model->transitionStatus($transfer, 'ACCEPTED');

        $this->expectException(\RuntimeException::class);
        $this->model->transitionStatus($transfer, 'DOWNLOADED');
    }

    /**
     * Transitioning from REVOKED (terminal) to any status fails.
     *
     * @test
     */
    public function testCannotTransitionFromRevoked(): void
    {
        $transfer = $this->model->create([
            'documentId'      => $this->document->id,
            'senderId'        => $this->sender->id,
            'recipientId'     => $this->recipient->id,
            'idempotencyKey'  => str_repeat('m', 64),
            'securityLevel'   => 'standard',
        ]);

        // Advance and revoke.
        $this->model->transitionStatus($transfer, 'READY');

        // Re-read fresh from DB to pass to revokeTransfer (which re-reads again internally).
        $transfer = $this->model->revokeTransfer($transfer);

        $this->assertSame('REVOKED', $transfer->status);

        $this->expectException(\RuntimeException::class);
        $this->model->transitionStatus($transfer, 'READY');
    }

    /**
     * FAILED status can be retried by transitioning back to PENDING_RECIPIENT.
     *
     * @test
     */
    public function testRetryFromFailed(): void
    {
        $transfer = $this->model->create([
            'documentId'      => $this->document->id,
            'senderId'        => $this->sender->id,
            'recipientId'     => $this->recipient->id,
            'idempotencyKey'  => str_repeat('n', 64),
            'securityLevel'   => 'standard',
        ]);

        // Move to FAILED via PENDING_RECIPIENT -> READY, then fail from READY.
        $this->model->transitionStatus($transfer, 'READY');

        // Insert FAILED directly via DB to simulate a failed state.
        $this->model->db->table('document_transfers')
            ->where('id', $transfer->id)
            ->update(['status' => 'FAILED', 'failed_at' => date('Y-m-d H:i:s')]);

        // Re-read the transfer with the new FAILED status.
        $row = $this->model->db->table('document_transfers')
            ->where('id', $transfer->id)
            ->get()
            ->getRowArray();
        $failedTransfer = new DocumentTransfer($row);
        $this->assertSame('FAILED', $failedTransfer->status);

        // FAILED → PENDING_RECIPIENT (retry)
        $retried = $this->model->transitionStatus($failedTransfer, 'PENDING_RECIPIENT');
        $this->assertSame('PENDING_RECIPIENT', $retried->status);
    }

    // ──────────────────────────────────────────────────────────────
    // IDEMPOTENCY KEY LOOKUP
    // ──────────────────────────────────────────────────────────────

    /**
     * Finding a transfer by its idempotency key returns the correct entity.
     *
     * @test
     */
    public function testFindByIdempotencyKey(): void
    {
        $key = str_repeat('o', 64);

        $created = $this->model->create([
            'documentId'      => $this->document->id,
            'senderId'        => $this->sender->id,
            'recipientId'     => $this->recipient->id,
            'idempotencyKey'  => $key,
            'securityLevel'   => 'standard',
        ]);

        $found = $this->model->findByIdempotencyKey($key);

        $this->assertInstanceOf(DocumentTransfer::class, $found);
        $this->assertSame($created->id, $found->id);
        $this->assertSame($key, $found->idempotencyKey);
    }

    /**
     * Finding a transfer by a non-existing idempotency key returns null.
     *
     * @test
     */
    public function testFindByIdempotencyKeyNonExisting(): void
    {
        $found = $this->model->findByIdempotencyKey(str_repeat('z', 64));

        $this->assertNull($found);
    }

}
