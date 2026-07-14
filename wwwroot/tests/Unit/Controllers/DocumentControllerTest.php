<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Models\DocumentModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * HTTP integration tests for DocumentController.
 *
 * <p>Tests CRUD operations for documents including sealing and lifecycle
 * management. Documents are scoped to their owner.</p>
 *
 * @coversNothing
 *
 * @since   1.1.1
 * @author  Aythami
 *
 * @internal
 */
final class DocumentControllerTest extends CIUnitTestCase
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

    private UserModel $userModel;

    private DocumentModel $documentModel;

    /**
     * UUID of the user created in setUp.
     *
     * @var string
     */
    private string $userId;

    /**
     * UUID of the document created in setUp for show/seal/destroy tests.
     *
     * @var string
     */
    private string $documentId;

    /**
     * Prepare test environment before each test.
     *
     * Creates a real user and document in the database so that
     * show/seal/destroy tests can operate on existing entities.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->userModel     = model(UserModel::class);
        $this->documentModel = model(DocumentModel::class);

        $user = $this->userModel->create([
            'email'        => 'document-test-user@marachain.test',
            'identityType' => 'physical',
            'firstName'    => 'Document',
            'lastName'     => 'Owner',
        ]);
        $this->userId = $user->id;

        $document = $this->documentModel->create([
            'ownerId'        => $this->userId,
            'title'          => 'Test Document',
            'mimeType'       => 'application/pdf',
            'fileSize'       => 102400,
            'fileHashSha256' => str_repeat('a', 64),
        ]);
        $this->documentId = $document->id;

        $this->withSession([
            'user_id' => $this->userId,
        ]);
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    // ──────────────────────────────────────────────────────────────
    // INDEX — GET /documents
    // ──────────────────────────────────────────────────────────────

    /**
     * Listing documents returns HTTP 200 with a JSON array of the
     * authenticated user's owned documents.
     *
     * @test
     */
    public function testIndex(): void
    {
        $result = $this->get('/documents');

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // SHOW — GET /documents/{id}
    // ──────────────────────────────────────────────────────────────

    /**
     * Fetching a single document by UUID returns HTTP 200 with document JSON.
     *
     * @test
     */
    public function testShow(): void
    {
        $result = $this->get('/documents/' . $this->documentId);

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    /**
     * Fetching a non-existent document returns HTTP 404.
     *
     * @test
     */
    public function testShowReturns404ForUnknownDocument(): void
    {
        $result = $this->get('/documents/00000000-0000-4000-a000-000000000000');

        $this->assertSame(404, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // CREATE — POST /documents
    // ──────────────────────────────────────────────────────────────

    /**
     * Creating a document with valid data returns HTTP 201 with the
     * document JSON.
     *
     * Covers multiple mime types via a data provider: PDF invoice,
     * PDF contract, and large report.
     *
     * @test
     *
     * @dataProvider provideValidDocumentData
     */
    public function testCreate(array $data): void
    {
        $result = $this->withBodyFormat('json')->post('/documents', $data);

        $this->assertSame(201, $result->response()->getStatusCode());
    }

    /**
     * Data provider for valid document creation scenarios.
     *
     * @return array<string, array<array<string, mixed>>>
     */
    public static function provideValidDocumentData(): array
    {
        return [
            'PDF invoice' => [[
                'title'          => 'Invoice 2026-001',
                'mimeType'       => 'application/pdf',
                'fileSize'       => 102400,
                'fileHashSha256' => str_repeat('a', 64),
            ]],
            'PDF contract' => [[
                'title'          => 'Service Agreement',
                'mimeType'       => 'application/pdf',
                'fileSize'       => 204800,
                'fileHashSha256' => str_repeat('b', 64),
            ]],
            'large PDF report' => [[
                'title'          => 'Annual Report 2025',
                'description'    => 'Compiled annual financial report',
                'mimeType'       => 'application/pdf',
                'fileSize'       => 5242880,
                'fileHashSha256' => str_repeat('c', 64),
            ]],
        ];
    }

    /**
     * Creating a document without a file hash returns HTTP 400.
     *
     * @test
     */
    public function testCreateWithoutFileHash(): void
    {
        $result = $this->withBodyFormat('json')->post('/documents', [
            'title'    => 'Invalid Doc',
            'mimeType' => 'application/pdf',
            'fileSize' => 5000,
            // fileHashSha256 intentionally omitted
        ]);

        $this->assertSame(400, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // SEAL — POST /documents/{id}/seal
    // ──────────────────────────────────────────────────────────────

    /**
     * Sealing a document returns HTTP 200 with the updated document JSON.
     *
     * @test
     */
    public function testSeal(): void
    {
        $result = $this->withBodyFormat('json')->post('/documents/' . $this->documentId . '/seal', [
            'manifestHash' => str_repeat('d', 64),
        ]);

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    /**
     * Sealing a non-existent document returns HTTP 404.
     *
     * @test
     */
    public function testSealReturns404ForUnknownDocument(): void
    {
        $result = $this->withBodyFormat('json')->post('/documents/00000000-0000-4000-a000-000000000000/seal', [
            'manifestHash' => str_repeat('d', 64),
        ]);

        $this->assertSame(404, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // DESTROY — DELETE /documents/{id}
    // ──────────────────────────────────────────────────────────────

    /**
     * Destroying a document (status transition to DESTROYED) returns HTTP 204.
     *
     * @test
     */
    public function testDestroy(): void
    {
        $result = $this->delete('/documents/' . $this->documentId);

        $this->assertSame(204, $result->response()->getStatusCode());
    }

    /**
     * Destroying a non-existent document returns HTTP 404.
     *
     * @test
     */
    public function testDestroyReturns404ForUnknownDocument(): void
    {
        $result = $this->delete('/documents/00000000-0000-4000-a000-000000000000');

        $this->assertSame(404, $result->response()->getStatusCode());
    }
}
