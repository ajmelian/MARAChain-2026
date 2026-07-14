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
     * Finds all signature requests belonging to a document.
     *
     * @test
     */
    public function testFindByDocumentId(): void
    {
        $documentId = $this->testDocumentId;

        $results = $this->model->findByDocumentId($documentId);

        $this->assertIsArray($results);
    }

    /**
     * Finds all signature requests for a given user.
     *
     * @test
     */
    public function testFindByUserId(): void
    {
        $userId = $this->testUserId;

        $results = $this->model->findByUserId($userId);

        $this->assertIsArray($results);
    }

    /**
     * Finds signature requests filtered by status.
     *
     * @test
     */
    public function testFindByStatus(): void
    {
        $results = $this->model->findByStatus('CREATED');

        $this->assertIsArray($results);
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
}
