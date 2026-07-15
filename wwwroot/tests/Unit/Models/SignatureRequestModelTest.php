<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Entities\SignatureRequest;
use App\Models\SignatureRequestModel;
use App\Models\UserModel;
use App\Models\DocumentModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use InvalidArgumentException;

/**
 * Unit tests for SignatureRequestModel.
 *
 * <p>RED phase: SignatureRequestModel does not exist yet.
 * These tests define the expected contract and MUST FAIL until
 * the model is implemented.</p>
 *
 * @coversNothing (model does not exist yet)
 *
 * @since   1.1.1
 * @author  Aythami
 */
final class SignatureRequestModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    /** @var bool */
    protected $refresh = true;

    /** @var string */
    protected $namespace = 'App';

    private SignatureRequestModel $model;
    private UserModel $userModel;
    private DocumentModel $documentModel;
    private string $testUserId;
    private string $testDocumentId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userModel = new UserModel();
        $this->documentModel = new DocumentModel();
        $this->model = new SignatureRequestModel();

        $user = $this->userModel->create([
            'firstName'    => 'Signer',
            'lastName'     => 'User',
            'email'        => 'signer' . bin2hex(random_bytes(4)) . '@example.com',
            'identityType' => 'physical',
        ]);
        $this->testUserId = $user->id;

        $doc = $this->documentModel->create([
            'title'           => 'Test Document',
            'ownerId'         => $user->id,
            'mimeType'        => 'application/pdf',
            'fileSize'        => 1024,
            'fileHashSha256'  => str_repeat('a', 64),
        ]);
        $this->testDocumentId = $doc->id;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    // ────────────────────────────────────────────────
    //  CREATE
    // ────────────────────────────────────────────────

    /**
     * Creates a signature request with all required fields.
     *
     * @test
     */
    public function testCreateSignatureRequest(): void
    {
        $data = [
            'documentId'        => $this->testDocumentId,
            'userId'            => $this->testUserId,
            'signatureIntent'   => 'document_send',
            'signatureProvider' => 'fnmt',
            'manifestJson'      => json_encode(['document_hash' => 'abc123']),
            'manifestHash'      => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
            'nonce'             => 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6',
            'issuedAt'          => '2026-07-13 10:00:00',
            'expiresAt'         => '2026-07-13 12:00:00',
        ];

        $signatureRequest = $this->model->createSignatureRequest($data);

        $this->assertInstanceOf(SignatureRequest::class, $signatureRequest);
        $this->assertNotEmpty($signatureRequest->id);
        $this->assertSame('document_send', $signatureRequest->signatureIntent);
        $this->assertSame('fnmt', $signatureRequest->signatureProvider);
        $this->assertSame('CREATED', $signatureRequest->status);
        $this->assertSame($data['manifestHash'], $signatureRequest->manifestHash);
        $this->assertSame($data['nonce'], $signatureRequest->nonce);
    }

    /**
     * Creating a signature request without a nonce must throw an exception.
     *
     * @test
     */
    public function testCreateWithoutNonce(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('nonce');

        $data = [
            'documentId'        => $this->testDocumentId,
            'userId'            => $this->testUserId,
            'signatureIntent'   => 'document_send',
            'signatureProvider' => 'fnmt',
            'manifestJson'      => json_encode(['document_hash' => 'abc123']),
            'manifestHash'      => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
            // nonce omitted intentionally
            'issuedAt'          => '2026-07-13 10:00:00',
            'expiresAt'         => '2026-07-13 12:00:00',
        ];

        $this->model->createSignatureRequest($data);
    }

    // ────────────────────────────────────────────────
    //  FIND
    // ────────────────────────────────────────────────

    /**
     * Finds all signature requests belonging to a document with actual data.
     *
     * @test
     */
    public function testFindByDocumentId(): void
    {
        $documentId = $this->testDocumentId;

        // Create two signature requests for the same document.
        $this->model->createSignatureRequest([
            'documentId'        => $documentId,
            'userId'            => $this->testUserId,
            'signatureIntent'   => 'document_send',
            'signatureProvider' => 'fnmt',
            'manifestJson'      => json_encode(['hash' => '1']),
            'manifestHash'      => str_repeat('a', 64),
            'nonce'             => str_repeat('1', 64),
            'issuedAt'          => '2026-07-13 10:00:00',
            'expiresAt'         => '2026-07-13 12:00:00',
        ]);

        $this->model->createSignatureRequest([
            'documentId'        => $documentId,
            'userId'            => $this->testUserId,
            'signatureIntent'   => 'document_send',
            'signatureProvider' => 'fnmt',
            'manifestJson'      => json_encode(['hash' => '2']),
            'manifestHash'      => str_repeat('b', 64),
            'nonce'             => str_repeat('2', 64),
            'issuedAt'          => '2026-07-13 10:00:00',
            'expiresAt'         => '2026-07-13 12:00:00',
        ]);

        $results = $this->model->findByDocumentId($documentId);

        $this->assertIsArray($results);
        $this->assertCount(2, $results);

        foreach ($results as $sr) {
            $this->assertInstanceOf(SignatureRequest::class, $sr);
            $this->assertSame($documentId, $sr->documentId);
        }
    }

    /**
     * Finds all signature requests for a given user with actual data.
     *
     * @test
     */
    public function testFindByUserId(): void
    {
        $userId = $this->testUserId;

        $this->model->createSignatureRequest([
            'documentId'        => $this->testDocumentId,
            'userId'            => $userId,
            'signatureIntent'   => 'document_send',
            'signatureProvider' => 'fnmt',
            'manifestJson'      => json_encode(['hash' => '1']),
            'manifestHash'      => str_repeat('c', 64),
            'nonce'             => str_repeat('3', 64),
            'issuedAt'          => '2026-07-13 10:00:00',
            'expiresAt'         => '2026-07-13 12:00:00',
        ]);

        $results = $this->model->findByUserId($userId);

        $this->assertIsArray($results);
        $this->assertCount(1, $results);

        foreach ($results as $sr) {
            $this->assertInstanceOf(SignatureRequest::class, $sr);
            $this->assertSame($userId, $sr->userId);
        }
    }

    /**
     * Finds signature requests filtered by status with actual data.
     *
     * @test
     */
    public function testFindByStatus(): void
    {
        // Create a CREATED request (default)
        $this->model->createSignatureRequest([
            'documentId'        => $this->testDocumentId,
            'userId'            => $this->testUserId,
            'signatureIntent'   => 'document_send',
            'signatureProvider' => 'fnmt',
            'manifestJson'      => json_encode(['hash' => '1']),
            'manifestHash'      => str_repeat('d', 64),
            'nonce'             => str_repeat('4', 64),
            'issuedAt'          => '2026-07-13 10:00:00',
            'expiresAt'         => '2026-07-13 12:00:00',
        ]);

        // Create and consume another (CONSUMED)
        $consumed = $this->model->createSignatureRequest([
            'documentId'        => $this->testDocumentId,
            'userId'            => $this->testUserId,
            'signatureIntent'   => 'document_send',
            'signatureProvider' => 'fnmt',
            'manifestJson'      => json_encode(['hash' => '2']),
            'manifestHash'      => str_repeat('e', 64),
            'nonce'             => str_repeat('5', 64),
            'issuedAt'          => '2026-07-13 10:00:00',
            'expiresAt'         => '2026-07-13 12:00:00',
        ]);
        $this->model->consumeSignature($consumed->id);

        $createdResults  = $this->model->findByStatus('CREATED');
        $consumedResults = $this->model->findByStatus('CONSUMED');

        $this->assertIsArray($createdResults);
        $this->assertCount(1, $createdResults);
        $this->assertSame('CREATED', $createdResults[0]->status);

        $this->assertIsArray($consumedResults);
        $this->assertCount(1, $consumedResults);
        $this->assertSame('CONSUMED', $consumedResults[0]->status);
    }

    /**
     * Finds a single signature request by its unique nonce.
     *
     * @test
     */
    public function testFindByNonce(): void
    {
        $nonce = 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6';
        $this->model->createSignatureRequest([
            'documentId'        => $this->testDocumentId,
            'userId'            => $this->testUserId,
            'signatureIntent'   => 'document_send',
            'signatureProvider' => 'fnmt',
            'manifestJson'      => json_encode(['hash' => 'abc']),
            'manifestHash'      => str_repeat('e', 64),
            'nonce'             => $nonce,
            'issuedAt'          => '2026-07-13 10:00:00',
            'expiresAt'         => '2026-07-13 12:00:00',
        ]);

        $result = $this->model->findByNonce($nonce);

        $this->assertNotNull($result);
        $this->assertInstanceOf(SignatureRequest::class, $result);
        $this->assertSame($nonce, $result->nonce);
    }

    // ────────────────────────────────────────────────
    //  STATE TRANSITIONS
    // ────────────────────────────────────────────────

    /**
     * Consuming a signature request sets its status to CONSUMED.
     *
     * @test
     */
    public function testConsumeSignature(): void
    {
        // First create a request
        $data = [
            'documentId'        => $this->testDocumentId,
            'userId'            => $this->testUserId,
            'signatureIntent'   => 'document_send',
            'signatureProvider' => 'fnmt',
            'manifestJson'      => json_encode(['document_hash' => 'abc123']),
            'manifestHash'      => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
            'nonce'             => 'b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1',
            'issuedAt'          => '2026-07-13 10:00:00',
            'expiresAt'         => '2026-07-13 12:00:00',
        ];

        $created = $this->model->createSignatureRequest($data);
        $this->assertSame('CREATED', $created->status);

        $consumed = $this->model->consumeSignature($created->id);

        $this->assertSame('CONSUMED', $consumed->status);
        $this->assertTrue($consumed->isConsumed());
    }

    /**
     * An expired signature request with expiresAt in the past reports as expired.
     *
     * @test
     */
    public function testExpiredSignature(): void
    {
        $data = [
            'documentId'        => $this->testDocumentId,
            'userId'            => $this->testUserId,
            'signatureIntent'   => 'document_send',
            'signatureProvider' => 'fnmt',
            'manifestJson'      => json_encode(['document_hash' => 'abc123']),
            'manifestHash'      => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
            'nonce'             => 'c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2',
            'issuedAt'          => '2026-07-13 08:00:00',
            'expiresAt'         => '2026-07-13 09:00:00', // already expired
        ];

        $signatureRequest = $this->model->createSignatureRequest($data);

        $this->assertTrue($signatureRequest->isExpired());
    }

    /**
     * Validates the full signature request lifecycle through
     * CREATED -> PROVIDER_REQUESTED -> PROVIDER_COMPLETED -> VALIDATED.
     *
     * @test
     */
    public function testValidateSignature(): void
    {
        // Create
        $data = [
            'documentId'        => $this->testDocumentId,
            'userId'            => $this->testUserId,
            'signatureIntent'   => 'document_send',
            'signatureProvider' => 'fnmt',
            'manifestJson'      => json_encode(['document_hash' => 'abc123']),
            'manifestHash'      => 'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855',
            'nonce'             => 'd4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6a1b2c3',
            'issuedAt'          => '2026-07-13 10:00:00',
            'expiresAt'         => '2026-07-13 12:00:00',
        ];

        $sr = $this->model->createSignatureRequest($data);
        $this->assertSame('CREATED', $sr->status);

        // Transition to PROVIDER_REQUESTED
        $sr = $this->model->updateStatus($sr->id, 'PROVIDER_REQUESTED');
        $this->assertSame('PROVIDER_REQUESTED', $sr->status);

        // Transition to PROVIDER_COMPLETED
        $sr = $this->model->updateStatus($sr->id, 'PROVIDER_COMPLETED');
        $this->assertSame('PROVIDER_COMPLETED', $sr->status);

        // Transition to VALIDATED
        $sr = $this->model->updateStatus($sr->id, 'VALIDATED');
        $this->assertSame('VALIDATED', $sr->status);

        $this->assertTrue($sr->isCompleted());
        $this->assertFalse($sr->isPending());
    }

    // ────────────────────────────────────────────────
    //  MARK AS FAILED
    // ────────────────────────────────────────────────

    /**
     * Marking a signature request as failed records status, timestamp, and reason.
     *
     * @test
     */
    public function testMarkAsFailed(): void
    {
        $data = [
            'documentId'        => $this->testDocumentId,
            'userId'            => $this->testUserId,
            'signatureIntent'   => 'document_send',
            'signatureProvider' => 'fnmt',
            'manifestJson'      => json_encode(['document_hash' => 'abc123']),
            'manifestHash'      => str_repeat('f', 64),
            'nonce'             => str_repeat('6', 64),
            'issuedAt'          => '2026-07-13 10:00:00',
            'expiresAt'         => '2026-07-13 12:00:00',
        ];

        $sr = $this->model->createSignatureRequest($data);
        $this->assertSame('CREATED', $sr->status);

        $failed = $this->model->markAsFailed($sr, 'Provider rejected the signature: invalid certificate');

        $this->assertSame('FAILED', $failed->status);
        $this->assertTrue($failed->isFailed());
        $this->assertNotNull($failed->failedAt);
        $this->assertSame('Provider rejected the signature: invalid certificate', $failed->failureReason);
    }

    // ────────────────────────────────────────────────
    //  FIND BY NONCE — EDGE CASES
    // ────────────────────────────────────────────────

    /**
     * Finding a signature request by a non-existing nonce returns null.
     *
     * @test
     */
    public function testFindByNonceNonExisting(): void
    {
        $result = $this->model->findByNonce(str_repeat('z', 64));

        $this->assertNull($result);
    }

    // ────────────────────────────────────────────────
    //  UPDATE STATUS EDGE CASES
    // ────────────────────────────────────────────────

    /**
     * updateStatus sets completedAt when transitioning to VALIDATED.
     *
     * @test
     */
    public function testUpdateStatusToValidatedSetsCompletedAt(): void
    {
        $data = [
            'documentId'        => $this->testDocumentId,
            'userId'            => $this->testUserId,
            'signatureIntent'   => 'document_send',
            'signatureProvider' => 'fnmt',
            'manifestJson'      => json_encode(['document_hash' => 'test']),
            'manifestHash'      => str_repeat('g', 64),
            'nonce'             => str_repeat('7', 64),
            'issuedAt'          => '2026-07-13 10:00:00',
            'expiresAt'         => '2026-07-13 12:00:00',
        ];

        $sr = $this->model->createSignatureRequest($data);
        $this->assertNull($sr->completedAt);

        $sr = $this->model->updateStatus($sr->id, 'PROVIDER_REQUESTED');
        $this->assertNull($sr->completedAt);

        $sr = $this->model->updateStatus($sr->id, 'PROVIDER_COMPLETED');
        $this->assertNull($sr->completedAt);

        $sr = $this->model->updateStatus($sr->id, 'VALIDATED');
        $this->assertNotNull($sr->completedAt);
        $this->assertTrue($sr->isCompleted());
    }

    /**
     * A consumed signature cannot be consumed again.
     *
     * @test
     */
    public function testConsumedSignatureIsNotPending(): void
    {
        $data = [
            'documentId'        => $this->testDocumentId,
            'userId'            => $this->testUserId,
            'signatureIntent'   => 'document_send',
            'signatureProvider' => 'fnmt',
            'manifestJson'      => json_encode(['document_hash' => 'test']),
            'manifestHash'      => str_repeat('h', 64),
            'nonce'             => str_repeat('8', 64),
            'issuedAt'          => '2026-07-13 10:00:00',
            'expiresAt'         => '2026-07-13 12:00:00',
        ];

        $sr = $this->model->createSignatureRequest($data);
        $this->assertTrue($sr->isPending());

        $consumed = $this->model->consumeSignature($sr->id);
        $this->assertFalse($consumed->isPending());
        $this->assertTrue($consumed->isConsumed());
        $this->assertTrue($consumed->isCompleted());
    }
}
