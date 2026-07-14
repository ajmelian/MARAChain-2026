<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Models\DocumentModel;
use App\Models\DocumentTransferModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * HTTP integration tests for TransferController.
 *
 * <p>Tests document transfer operations: listing, sent/received folders,
 * creation, retrieval by ID, and revocation.</p>
 *
 * @coversNothing (integration test)
 *
 * @since   1.1.1
 * @author  Aythami
 *
 * @internal
 */
final class TransferControllerTest extends CIUnitTestCase
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
     * Authenticated user (sender) UUID.
     *
     * @var string
     */
    private string $testUserId;

    /**
     * Recipient user UUID.
     *
     * @var string
     */
    private string $testRecipientId;

    /**
     * Document UUID to transfer.
     *
     * @var string
     */
    private string $testDocumentId;

    /**
     * Pre-created transfer UUID for show/revoke tests.
     *
     * @var string
     */
    private string $testTransferId;

    /**
     * Prepare test environment before each test.
     *
     * Creates sender user, recipient user, a document, and a pending transfer
     * so that show/revoke tests have a real entity to fetch.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $userModel       = new UserModel();
        $documentModel   = new DocumentModel();
        $transferModel   = new DocumentTransferModel();

        // Create the authenticated user (sender)
        $sender = $userModel->create([
            'firstName'    => 'Transfer',
            'lastName'     => 'Sender',
            'email'        => 'transfer-sender.' . bin2hex(random_bytes(4)) . '@test.com',
            'identityType' => 'physical',
        ]);
        $this->testUserId = $sender->id;

        // Create a recipient user
        $recipient = $userModel->create([
            'firstName'    => 'Transfer',
            'lastName'     => 'Recipient',
            'email'        => 'transfer-recipient.' . bin2hex(random_bytes(4)) . '@test.com',
            'identityType' => 'physical',
        ]);
        $this->testRecipientId = $recipient->id;

        // Create a document owned by the sender
        $doc = $documentModel->create([
            'ownerId' => $this->testUserId,
            'title'   => 'Test Document for Transfer',
        ]);
        $this->testDocumentId = $doc->id;

        // Create a transfer used by testShow / testRevoke
        $transfer = $transferModel->create([
            'senderId'        => $this->testUserId,
            'recipientId'     => $this->testRecipientId,
            'documentId'      => $this->testDocumentId,
            'securityLevel'   => 'standard',
            'idempotencyKey'  => bin2hex(random_bytes(32)),
        ]);
        $this->testTransferId = $transfer->id;

        $this->withSession([
            'user_id' => $this->testUserId,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // INDEX — GET /transfers
    // ──────────────────────────────────────────────────────────────

    /**
     * Listing all transfers returns HTTP 200.
     *
     * @test
     */
    public function testIndex(): void
    {
        $result = $this->get('/transfers');

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // OUTBOX — GET /transfers/sent
    // ──────────────────────────────────────────────────────────────

    /**
     * Listing sent transfers (outbox) returns HTTP 200.
     *
     * @test
     */
    public function testOutbox(): void
    {
        $result = $this->get('/transfers/sent');

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // INBOX — GET /transfers/received
    // ──────────────────────────────────────────────────────────────

    /**
     * Listing received transfers (inbox) returns HTTP 200.
     *
     * @test
     */
    public function testInbox(): void
    {
        $result = $this->get('/transfers/received');

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // CREATE — POST /transfers
    // ──────────────────────────────────────────────────────────────

    /**
     * Creating a document transfer returns HTTP 201 with the transfer JSON.
     *
     * Covers multiple security levels. Creates the POST body with dynamic
     * IDs and idempotency keys that satisfy the 64-character rule.
     *
     * @test
     */
    public function testCreate(): void
    {
        $scenarios = [
            'standard' => [
                'securityLevel'  => 'standard',
                'idempotencyKey' => 'std-' . bin2hex(random_bytes(30)),
            ],
            'signed' => [
                'securityLevel'    => 'signed',
                'requiresSignature' => true,
                'idempotencyKey'   => 'sig-' . bin2hex(random_bytes(30)),
            ],
            'signed_sealed' => [
                'securityLevel'      => 'signed_sealed',
                'requiresSignature'  => true,
                'idempotencyKey'     => 'sse-' . bin2hex(random_bytes(30)),
            ],
        ];

        foreach ($scenarios as $name => $data) {
            $data['documentId']  = $this->testDocumentId;
            $data['recipientId'] = $this->testRecipientId;

            $result = $this->withBodyFormat('json')->post('/transfers', $data);

            $this->assertSame(201, $result->response()->getStatusCode(), "Scenario: {$name}");
        }
    }

    /**
     * Creating a transfer without a document ID returns HTTP 400.
     *
     * @test
     */
    public function testCreateWithoutDocumentId(): void
    {
        $result = $this->withBodyFormat('json')->post('/transfers', [
            'recipientId'    => $this->testRecipientId,
            'securityLevel'  => 'standard',
            'idempotencyKey' => bin2hex(random_bytes(32)),
        ]);

        $this->assertSame(400, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // SHOW — GET /transfers/{id}
    // ──────────────────────────────────────────────────────────────

    /**
     * Fetching a single transfer by UUID returns HTTP 200 with transfer JSON.
     *
     * @test
     */
    public function testShow(): void
    {
        $result = $this->get('/transfers/' . $this->testTransferId);

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    /**
     * Fetching a non-existent transfer returns HTTP 404.
     *
     * @test
     */
    public function testShowReturns404ForUnknownTransfer(): void
    {
        $result = $this->get('/transfers/00000000-0000-4000-a000-000000000000');

        $this->assertSame(404, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // REVOKE — POST /transfers/{id}/revoke
    // ──────────────────────────────────────────────────────────────

    /**
     * Revoking a transfer returns HTTP 200 with the updated transfer JSON.
     *
     * @test
     */
    public function testRevoke(): void
    {
        $result = $this->withBodyFormat('json')->post('/transfers/' . $this->testTransferId . '/revoke');

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    /**
     * Revoking a non-existent transfer returns HTTP 404.
     *
     * @test
     */
    public function testRevokeReturns404ForUnknownTransfer(): void
    {
        $result = $this->withBodyFormat('json')->post('/transfers/00000000-0000-4000-a000-000000000000/revoke');

        $this->assertSame(404, $result->response()->getStatusCode());
    }
}
