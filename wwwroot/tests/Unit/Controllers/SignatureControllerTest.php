<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Models\DocumentModel;
use App\Models\SignatureRequestModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * HTTP integration tests for SignatureController.
 *
 * <p>Tests signature request creation and retrieval. Signature requests
 * are associated with a document and a user, and flow through a provider
 * for cryptographic signing.</p>
 *
 * @coversNothing (integration test)
 *
 * @since   1.1.1
 * @author  Aythami
 *
 * @internal
 */
final class SignatureControllerTest extends CIUnitTestCase
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
     * Authenticated user UUID.
     *
     * @var string
     */
    private string $testUserId;

    /**
     * Document UUID to request signature for.
     *
     * @var string
     */
    private string $testDocumentId;

    /**
     * Pre-created signature request UUID for testShow.
     *
     * @var string
     */
    private string $testSignatureId;

    /**
     * Prepare test environment before each test.
     *
     * Creates a user and a document used by all signature tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $userModel     = new UserModel();
        $documentModel = new DocumentModel();

        // Create the authenticated user
        $user = $userModel->create([
            'firstName'    => 'Signature',
            'lastName'     => 'TestUser',
            'email'        => 'sig.' . bin2hex(random_bytes(4)) . '@test.com',
            'identityType' => 'physical',
        ]);
        $this->testUserId = $user->id;

        // Create a document to request signatures for
        $doc = $documentModel->create([
            'ownerId' => $this->testUserId,
            'title'   => 'Test Document for Signature',
        ]);
        $this->testDocumentId = $doc->id;

        // Create a signature request for testShow
        $signatureModel = new SignatureRequestModel();
        $sr = $signatureModel->createSignatureRequest([
            'documentId'        => $this->testDocumentId,
            'userId'            => $this->testUserId,
            'signatureIntent'   => 'approve',
            'digestAlgorithm'   => 'SHA-256',
            'signatureProvider' => 'AUTOFIRMA',
            'manifestHash'      => str_repeat('a', 64),
            'manifestJson'      => json_encode(['action' => 'approve', 'scope' => 'full']),
            'nonce'             => bin2hex(random_bytes(32)),
        ]);
        $this->testSignatureId = $sr->id;

        $this->withSession([
            'user_id' => $this->testUserId,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // REQUEST SIGNATURE — POST /signatures
    // ──────────────────────────────────────────────────────────────

    /**
     * Requesting a signature for a document returns HTTP 201 with the
     * signature request JSON.
     *
     * Covers multiple intent types.
     *
     * @test
     */
    public function testRequestSignature(): void
    {
        $scenarios = [
            'document approval' => [
                'signatureIntent'   => 'approve',
                'digestAlgorithm'   => 'SHA-256',
                'signatureProvider' => 'AUTOFIRMA',
                'manifestHash'      => str_repeat('a', 64),
                'manifestJson'      => json_encode(['action' => 'approve', 'scope' => 'full']),
            ],
            'document review' => [
                'signatureIntent'   => 'review',
                'digestAlgorithm'   => 'SHA-512',
                'signatureProvider' => 'VALIDe',
                'manifestHash'      => str_repeat('b', 64),
                'manifestJson'      => json_encode(['action' => 'review']),
            ],
            'document sign' => [
                'signatureIntent'   => 'sign',
                'digestAlgorithm'   => 'SHA-256',
                'signatureProvider' => 'CLAVE_FIRMA',
                'manifestHash'      => str_repeat('c', 64),
                'manifestJson'      => json_encode(['action' => 'sign', 'version' => 1]),
            ],
        ];

        foreach ($scenarios as $name => $data) {
            $data['documentId'] = $this->testDocumentId;

            $result = $this->withBodyFormat('json')->post('/signatures', $data);

            $this->assertSame(201, $result->response()->getStatusCode(), "Scenario: {$name}");
        }
    }

    /**
     * Requesting a signature without a document ID returns HTTP 400.
     *
     * @test
     */
    public function testRequestSignatureWithoutDocumentId(): void
    {
        $result = $this->withBodyFormat('json')->post('/signatures', [
            'signatureIntent'   => 'approve',
            'digestAlgorithm'   => 'SHA-256',
            'signatureProvider' => 'AUTOFIRMA',
            // documentId intentionally omitted
        ]);

        $this->assertSame(400, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // SHOW — GET /signatures/{id}
    // ──────────────────────────────────────────────────────────────

    /**
     * Fetching a single signature request by UUID returns HTTP 200
     * with the signature request JSON.
     *
     * @test
     */
    public function testShow(): void
    {
        $result = $this->get('/signatures/' . $this->testSignatureId);

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    /**
     * Fetching a non-existent signature request returns HTTP 404.
     *
     * @test
     */
    public function testShowReturns404ForUnknownSignature(): void
    {
        $result = $this->get('/signatures/00000000-0000-4000-a000-000000000000');

        $this->assertSame(404, $result->response()->getStatusCode());
    }
}
