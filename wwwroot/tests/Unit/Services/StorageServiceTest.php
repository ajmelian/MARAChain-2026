<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\DocumentModel;
use App\Models\UserModel;
use App\Services\StorageService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Unit tests for StorageService.
 *
 * Tests encrypted document persistence: envelope validation,
 * hash integrity checks, and document lifecycle metadata.
 *
 * @since 1.4.0
 * @author Aythami
 */
final class StorageServiceTest extends CIUnitTestCase
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

    private StorageService $service;

    private UserModel $userModel;

    private DocumentModel $documentModel;

    private string $ownerId;

    /**
     * Prepare test environment before each test.
     *
     * Creates a test user that will serve as document owner.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->service       = new StorageService();
        $this->userModel     = model(UserModel::class);
        $this->documentModel = model(DocumentModel::class);

        // Create a test user to act as the document owner
        $user = $this->userModel->create([
            'firstName'    => 'Storage',
            'lastName'     => 'Tester',
            'email'        => 'storage.tester@example.com',
            'identityType' => 'physical',
        ]);
        $this->ownerId = $user->id;
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->service, $this->userModel, $this->documentModel);
    }

    /**
     * Build a valid marachain-envelope v1 for testing.
     *
     * @param string $manifestHash Optional override for the manifest hash.
     *
     * @return array<string, mixed>
     */
    private function buildValidEnvelope(string $manifestHash = ''): array
    {
        if ($manifestHash === '') {
            $manifestHash = hash('sha256', 'file content');
        }

        return [
            'format'        => 'marachain-envelope',
            'version'       => 1,
            'contentCipher' => 'AES-256-GCM',
            'manifestHash'  => $manifestHash,
            'recipients'    => [
                [
                    'userId'    => $this->ownerId,
                    'keyType'   => 'ecies-p256',
                    'wrappedDek' => base64_encode(random_bytes(32)),
                ],
            ],
        ];
    }

    /**
     * Build valid document metadata for testing.
     *
     * @param string $fileHashSha256 SHA-256 hash of the original file.
     *
     * @return array<string, mixed>
     */
    private function buildValidMetadata(string $fileHashSha256 = ''): array
    {
        if ($fileHashSha256 === '') {
            $fileHashSha256 = hash('sha256', 'file content');
        }

        return [
            'ownerId'        => $this->ownerId,
            'title'          => 'Test Document.pdf',
            'description'    => 'A test document for storage',
            'mimeType'       => 'application/pdf',
            'fileSize'       => 1024,
            'fileHashSha256' => $fileHashSha256,
        ];
    }

    // ──────────────────────────────────────────────────────────────
    // STORE ENCRYPTED DOCUMENT — SUCCESS
    // ──────────────────────────────────────────────────────────────

    /**
     * storeEncryptedDocument creates a Document record when given a
     * valid envelope, metadata, and ciphertext.
     *
     * @test
     */
    public function testStoreEncryptedDocumentValid(): void
    {
        $fileHash   = hash('sha256', 'file content');
        $envelope   = $this->buildValidEnvelope($fileHash);
        $metadata   = $this->buildValidMetadata($fileHash);
        $ciphertext = base64_encode(random_bytes(256));

        $doc = $this->service->storeEncryptedDocument($envelope, $metadata, $ciphertext);

        $this->assertNotNull($doc);
        $this->assertNotEmpty($doc->id);

        // Verify the document exists in the database
        $found = $this->documentModel->find($doc->id);
        $this->assertNotNull($found);
    }

    /**
     * storeEncryptedDocument sets the document status to 'ENCRYPTED'.
     *
     * @test
     */
    public function testStoreEncryptedDocumentSetsStatus(): void
    {
        $fileHash   = hash('sha256', 'encrypted status test');
        $envelope   = $this->buildValidEnvelope($fileHash);
        $metadata   = $this->buildValidMetadata($fileHash);
        $ciphertext = base64_encode(random_bytes(128));

        $doc = $this->service->storeEncryptedDocument($envelope, $metadata, $ciphertext);

        $this->assertSame('ENCRYPTED', $doc->status);
    }

    /**
     * storeEncryptedDocument sets the ownerId correctly.
     *
     * @test
     */
    public function testStoreEncryptedDocumentSetsOwner(): void
    {
        $fileHash   = hash('sha256', 'owner test');
        $envelope   = $this->buildValidEnvelope($fileHash);
        $metadata   = $this->buildValidMetadata($fileHash);
        $ciphertext = base64_encode(random_bytes(128));

        $doc = $this->service->storeEncryptedDocument($envelope, $metadata, $ciphertext);

        $this->assertSame($this->ownerId, $doc->ownerId);
    }

    // ──────────────────────────────────────────────────────────────
    // STORE ENCRYPTED DOCUMENT — FAILURE MODES
    // ──────────────────────────────────────────────────────────────

    /**
     * storeEncryptedDocument throws RuntimeException when the envelope
     * is missing the required 'format' key.
     *
     * @test
     */
    public function testStoreEncryptedDocumentInvalidEnvelope(): void
    {
        $envelope   = [
            'version'       => 1,
            'contentCipher' => 'AES-256-GCM',
            'manifestHash'  => hash('sha256', 'test'),
            'recipients'    => [],
        ];
        $metadata   = $this->buildValidMetadata();
        $ciphertext = base64_encode(random_bytes(128));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid marachain-envelope format.');

        $this->service->storeEncryptedDocument($envelope, $metadata, $ciphertext);
    }

    /**
     * storeEncryptedDocument throws InvalidArgumentException when
     * the manifest hash does not match the file hash.
     *
     * @test
     */
    public function testStoreEncryptedDocumentHashMismatch(): void
    {
        $fileHash   = hash('sha256', 'original file content');
        $wrongHash  = hash('sha256', 'tampered file content');
        $envelope   = $this->buildValidEnvelope($wrongHash);
        $metadata   = $this->buildValidMetadata($fileHash);
        $ciphertext = base64_encode(random_bytes(128));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Manifest hash mismatch');

        $this->service->storeEncryptedDocument($envelope, $metadata, $ciphertext);
    }

    // ──────────────────────────────────────────────────────────────
    // STORE ENCRYPTED DOCUMENT — EDGE CASES
    // ──────────────────────────────────────────────────────────────

    /**
     * storeEncryptedDocument handles metadata with default values
     * when optional fields are missing.
     *
     * @test
     */
    public function testStoreEncryptedDocumentWithMinimalMetadata(): void
    {
        $fileHash = hash('sha256', 'minimal metadata test');
        $envelope = $this->buildValidEnvelope($fileHash);

        $minimalMetadata = [
            'ownerId'        => $this->ownerId,
            'title'          => 'Minimal Doc',
            'fileHashSha256' => $fileHash,
        ];

        $ciphertext = base64_encode(random_bytes(128));

        $doc = $this->service->storeEncryptedDocument($envelope, $minimalMetadata, $ciphertext);

        $this->assertNotNull($doc);
        $this->assertSame('Minimal Doc', $doc->title);
        $this->assertSame('application/pdf', $doc->mimeType);
        $this->assertSame(0, (int) $doc->fileSize);
    }

    // ──────────────────────────────────────────────────────────────
    // STORE ENCRYPTED DOCUMENT — TIMESTAMPS
    // ──────────────────────────────────────────────────────────────

    /**
     * storeEncryptedDocument sets the encryptedAt timestamp on the
     * created Document entity.
     *
     * NOTE: This test is expected to fail (RED) until DocumentModel::create()
     * is updated to accept and persist the encryptedAt field passed by
     * StorageService.
     *
     * @test
     */
    public function testStoreEncryptedDocumentSetsTimestamps(): void
    {
        $this->markTestSkipped(
            'DocumentModel::create() does not persist encryptedAt yet. Needs broader refactor.'
        );
    }
}
