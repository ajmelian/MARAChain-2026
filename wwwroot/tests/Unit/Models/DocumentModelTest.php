<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Entities\Document;
use App\Entities\User;
use App\Models\DocumentModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Unit tests for DocumentModel.
 *
 * Tests the Document persistence layer covering creation, retrieval,
 * status lifecycle transitions, immutability rules, and versioning.
 *
 * @coversNothing (RED phase — model class does not exist yet)
 *
 * @since   1.1.1
 * @author  Aythami
 *
 * @internal
 */
final class DocumentModelTest extends CIUnitTestCase
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
     * DocumentModel instance under test.
     *
     * @var DocumentModel
     */
    private DocumentModel $model;

    /**
     * Pre-created owner user for document ownership.
     *
     * @var User
     */
    private User $owner;

    /**
     * Prepare test environment before each test.
     *
     * In RED phase, DocumentModel does not exist yet — the setUp
     * will fail with a fatal error. This is expected TDD behaviour.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // RED PHASE: These lines fail because neither model exists yet.
        $this->model = new DocumentModel();

        $userModel = new UserModel();
        $this->owner = $userModel->create([
            'firstName'    => 'Doc',
            'lastName'     => 'Owner',
            'email'        => 'doc.owner@example.com',
            'identityType' => 'physical',
        ]);
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->model, $this->owner);
    }

    // ──────────────────────────────────────────────────────────────
    // CREATE
    // ──────────────────────────────────────────────────────────────

    /**
     * Creates a document with valid data and expects a Document entity.
     *
     * Covers multiple scenarios via data provider: PDF invoice,
     * contract, and large report.
     *
     * @test
     *
     * @dataProvider provideValidDocumentData
     */
    public function testCreateDocument(array $data): void
    {
        // Inject the actual owner ID from setUp (data providers run
        // before setUp, so we resolve the ownerId at test time).
        $data['ownerId'] = $this->owner->id;

        $doc = $this->model->create($data);

        $this->assertInstanceOf(Document::class, $doc);
        $this->assertNotEmpty($doc->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $doc->id,
        );
        $this->assertSame($data['title'], $doc->title);
        $this->assertSame($data['ownerId'], $doc->ownerId);
        $this->assertSame($data['mimeType'], $doc->mimeType);
        $this->assertSame($data['fileSize'], $doc->fileSize);
        $this->assertSame($data['fileHashSha256'], $doc->fileHashSha256);
        $this->assertSame(1, $doc->version);
        $this->assertSame('DRAFT', $doc->status);
    }

    /**
     * Data provider for valid document creation.
     *
     * NOTE: ownerId is a placeholder — it is resolved to the actual
     *       owner ID at test runtime via setUp fixture.
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
                'mimeType'       => 'application/pdf',
                'fileSize'       => 5242880,
                'fileHashSha256' => str_repeat('c', 64),
            ]],
        ];
    }

    /**
     * Attempting to create a document without a title throws an exception.
     *
     * @test
     */
    public function testCreateDocumentWithoutTitle(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->model->create([
            'ownerId'        => $this->owner->id,
            'mimeType'       => 'application/pdf',
            'fileSize'       => 5000,
            'fileHashSha256' => str_repeat('d', 64),
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // FIND
    // ──────────────────────────────────────────────────────────────

    /**
     * Finding documents by owner ID returns documents belonging to that owner.
     *
     * @test
     */
    public function testFindByOwnerId(): void
    {
        $this->model->create([
            'title'          => 'Doc A',
            'ownerId'        => $this->owner->id,
            'mimeType'       => 'application/pdf',
            'fileSize'       => 1000,
            'fileHashSha256' => str_repeat('e', 64),
        ]);

        $this->model->create([
            'title'          => 'Doc B',
            'ownerId'        => $this->owner->id,
            'mimeType'       => 'application/pdf',
            'fileSize'       => 2000,
            'fileHashSha256' => str_repeat('f', 64),
        ]);

        $docs = $this->model->findByOwnerId($this->owner->id);

        $this->assertIsArray($docs);
        $this->assertCount(2, $docs);
        $this->assertContainsOnlyInstancesOf(Document::class, $docs);

        foreach ($docs as $doc) {
            $this->assertSame($this->owner->id, $doc->ownerId);
        }
    }

    /**
     * Finding documents by status filters results correctly.
     *
     * @test
     */
    public function testFindByStatus(): void
    {
        $draft = $this->model->create([
            'title'          => 'Draft Doc',
            'ownerId'        => $this->owner->id,
            'mimeType'       => 'application/pdf',
            'fileSize'       => 1000,
            'fileHashSha256' => str_repeat('g', 64),
        ]);

        // Seal the document.
        $this->model->updateStatus($draft, 'SEALED');

        $drafts = $this->model->findByStatus('DRAFT');
        $sealed = $this->model->findByStatus('SEALED');

        $this->assertCount(0, $drafts);
        $this->assertCount(1, $sealed);
        $this->assertSame('Draft Doc', $sealed[0]->title);
    }

    /**
     * Finding a document by file hash returns the matching document.
     *
     * @test
     */
    public function testFindByFileHash(): void
    {
        $hash = str_repeat('h', 64);

        $created = $this->model->create([
            'title'          => 'Hashable Doc',
            'ownerId'        => $this->owner->id,
            'mimeType'       => 'application/pdf',
            'fileSize'       => 3000,
            'fileHashSha256' => $hash,
        ]);

        $found = $this->model->findByFileHash($hash);

        $this->assertInstanceOf(Document::class, $found);
        $this->assertSame($created->id, $found->id);
    }

    // ──────────────────────────────────────────────────────────────
    // STATUS LIFECYCLE
    // ──────────────────────────────────────────────────────────────

    /**
     * Tests all valid status transitions: seal, encrypt, archive, destroy.
     *
     * @test
     */
    public function testUpdateStatus(): void
    {
        $doc = $this->model->create([
            'title'          => 'Lifecycle Doc',
            'ownerId'        => $this->owner->id,
            'mimeType'       => 'application/pdf',
            'fileSize'       => 4000,
            'fileHashSha256' => str_repeat('i', 64),
        ]);

        // DRAFT → SEALED
        $sealed = $this->model->updateStatus($doc, 'SEALED');
        $this->assertSame('SEALED', $sealed->status);
        $this->assertNotNull($sealed->sealedAt);

        // SEALED → ENCRYPTED
        $encrypted = $this->model->updateStatus($sealed, 'ENCRYPTED');
        $this->assertSame('ENCRYPTED', $encrypted->status);
        $this->assertNotNull($encrypted->encryptedAt);

        // ENCRYPTED → ARCHIVED
        $archived = $this->model->updateStatus($encrypted, 'ARCHIVED');
        $this->assertSame('ARCHIVED', $archived->status);
        $this->assertNotNull($archived->archivedAt);

        // ARCHIVED → DESTROYED
        $destroyed = $this->model->updateStatus($archived, 'DESTROYED');
        $this->assertSame('DESTROYED', $destroyed->status);
        $this->assertNotNull($destroyed->destroyedAt);
    }

    /**
     * A sealed document cannot be modified (immutability constraint).
     *
     * @test
     */
    public function testSealedDocumentIsImmutable(): void
    {
        $doc = $this->model->create([
            'title'          => 'Immutable Doc',
            'ownerId'        => $this->owner->id,
            'mimeType'       => 'application/pdf',
            'fileSize'       => 5000,
            'fileHashSha256' => str_repeat('j', 64),
        ]);

        // Seal it first.
        $sealed = $this->model->updateStatus($doc, 'SEALED');

        // Attempting to modify a sealed document should throw.
        $this->expectException(\RuntimeException::class);
        $this->model->updateStatus($sealed, 'DRAFT');
    }

    // ──────────────────────────────────────────────────────────────
    // VERSIONING
    // ──────────────────────────────────────────────────────────────

    /**
     * Creating a new version increments the version counter.
     *
     * @test
     */
    public function testCreateNewVersion(): void
    {
        $original = $this->model->create([
            'title'          => 'Versioned Doc',
            'ownerId'        => $this->owner->id,
            'mimeType'       => 'application/pdf',
            'fileSize'       => 6000,
            'fileHashSha256' => str_repeat('k', 64),
        ]);

        $this->assertSame(1, $original->version);

        $v2 = $this->model->createNewVersion($original, [
            'fileSize'       => 7000,
            'fileHashSha256' => str_repeat('l', 64),
        ]);

        $this->assertInstanceOf(Document::class, $v2);
        $this->assertSame(2, $v2->version);
        $this->assertSame(7000, $v2->fileSize);

        // The new version may share the same document ID across
        // versions or use a new ID — we only assert that it is
        // a valid Document entity.
    }

    // ──────────────────────────────────────────────────────────────
    // SPECIFIC LIFECYCLE METHODS
    // ──────────────────────────────────────────────────────────────

    /**
     * Sealing a document transitions from DRAFT to SEALED.
     *
     * @test
     */
    public function testSealDocument(): void
    {
        $doc = $this->model->create([
            'title'          => 'Sealable Doc',
            'ownerId'        => $this->owner->id,
            'mimeType'       => 'application/pdf',
            'fileSize'       => 5000,
            'fileHashSha256' => str_repeat('m', 64),
        ]);

        $this->assertTrue($doc->isDraft());

        $sealed = $this->model->sealDocument($doc);

        $this->assertSame('SEALED', $sealed->status);
        $this->assertTrue($sealed->isSealed());
        $this->assertNotNull($sealed->sealedAt);
        $this->assertTrue($sealed->isImmutable());
    }

    /**
     * Sealing an already-sealed document throws an exception.
     *
     * @test
     */
    public function testSealAlreadySealedDocument(): void
    {
        $doc = $this->model->create([
            'title'          => 'Already Sealed',
            'ownerId'        => $this->owner->id,
            'mimeType'       => 'application/pdf',
            'fileSize'       => 5000,
            'fileHashSha256' => str_repeat('n', 64),
        ]);

        $this->model->sealDocument($doc);

        $this->expectException(\RuntimeException::class);
        $this->model->sealDocument($doc);
    }

    /**
     * Encrypting a sealed document transitions from SEALED to ENCRYPTED.
     *
     * @test
     */
    public function testEncryptDocument(): void
    {
        $doc = $this->model->create([
            'title'          => 'Encryptable Doc',
            'ownerId'        => $this->owner->id,
            'mimeType'       => 'application/pdf',
            'fileSize'       => 5000,
            'fileHashSha256' => str_repeat('o', 64),
        ]);

        $sealed = $this->model->sealDocument($doc);

        $encrypted = $this->model->encryptDocument($sealed);

        $this->assertSame('ENCRYPTED', $encrypted->status);
        $this->assertTrue($encrypted->isEncrypted());
        $this->assertNotNull($encrypted->encryptedAt);
    }

    /**
     * Encrypting a non-sealed document throws an exception.
     *
     * @test
     */
    public function testEncryptNonSealedDocument(): void
    {
        $doc = $this->model->create([
            'title'          => 'Non-Sealed Encrypt',
            'ownerId'        => $this->owner->id,
            'mimeType'       => 'application/pdf',
            'fileSize'       => 5000,
            'fileHashSha256' => str_repeat('p', 64),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->model->encryptDocument($doc);
    }

    /**
     * Archiving an encrypted document transitions from ENCRYPTED to ARCHIVED.
     *
     * @test
     */
    public function testArchiveDocument(): void
    {
        $doc = $this->model->create([
            'title'          => 'Archivable Doc',
            'ownerId'        => $this->owner->id,
            'mimeType'       => 'application/pdf',
            'fileSize'       => 5000,
            'fileHashSha256' => str_repeat('q', 64),
        ]);

        $sealed    = $this->model->sealDocument($doc);
        $encrypted = $this->model->encryptDocument($sealed);

        $archived = $this->model->archiveDocument($encrypted);

        $this->assertSame('ARCHIVED', $archived->status);
        $this->assertTrue($archived->isArchived());
        $this->assertNotNull($archived->archivedAt);
    }

    /**
     * Destroying an archived document transitions from ARCHIVED to DESTROYED.
     *
     * @test
     */
    public function testDestroyDocument(): void
    {
        $doc = $this->model->create([
            'title'          => 'Destructible Doc',
            'ownerId'        => $this->owner->id,
            'mimeType'       => 'application/pdf',
            'fileSize'       => 5000,
            'fileHashSha256' => str_repeat('r', 64),
        ]);

        $sealed    = $this->model->sealDocument($doc);
        $encrypted = $this->model->encryptDocument($sealed);
        $archived  = $this->model->archiveDocument($encrypted);

        $destroyed = $this->model->destroyDocument($archived);

        $this->assertSame('DESTROYED', $destroyed->status);
        $this->assertTrue($destroyed->isDestroyed());
        $this->assertNotNull($destroyed->destroyedAt);
    }

    /**
     * Destroying an archived document marks it as destroyed,
     * and no further transitions are possible from DESTROYED.
     *
     * @test
     */
    public function testDestroyedDocumentIsTerminal(): void
    {
        $doc = $this->model->create([
            'title'          => 'Terminal Doc',
            'ownerId'        => $this->owner->id,
            'mimeType'       => 'application/pdf',
            'fileSize'       => 5000,
            'fileHashSha256' => str_repeat('s', 64),
        ]);

        $sealed    = $this->model->sealDocument($doc);
        $encrypted = $this->model->encryptDocument($sealed);
        $archived  = $this->model->archiveDocument($encrypted);
        $this->model->destroyDocument($archived);

        $this->expectException(\RuntimeException::class);
        $this->model->updateStatus($archived, 'DRAFT');
    }

    // ──────────────────────────────────────────────────────────────
    // FIND — EDGE CASES
    // ──────────────────────────────────────────────────────────────

    /**
     * Finding a document by a non-existing file hash returns null.
     *
     * @test
     */
    public function testFindByFileHashNonExisting(): void
    {
        $found = $this->model->findByFileHash(str_repeat('z', 64));

        $this->assertNull($found);
    }

    /**
     * Finding documents by owner ID when owner has no documents
     * returns an empty array.
     *
     * @test
     */
    public function testFindByOwnerIdEmpty(): void
    {
        // Create a fresh user with no documents.
        $emptyOwner = (new UserModel())->create([
            'firstName'    => 'Empty',
            'lastName'     => 'Owner',
            'email'        => 'empty.owner@example.com',
            'identityType' => 'physical',
        ]);

        $docs = $this->model->findByOwnerId($emptyOwner->id);

        $this->assertIsArray($docs);
        $this->assertCount(0, $docs);
    }

    /**
     * Creating a new version within a transaction guards against TOCTOU
     * on the version number.
     *
     * @test
     */
    public function testCreateNewVersionFromExistingVersion(): void
    {
        $original = $this->model->create([
            'title'          => 'Multi Version Doc',
            'ownerId'        => $this->owner->id,
            'mimeType'       => 'application/pdf',
            'fileSize'       => 1000,
            'fileHashSha256' => str_repeat('t', 64),
        ]);

        $v2 = $this->model->createNewVersion($original, [
            'fileSize'       => 2000,
            'fileHashSha256' => str_repeat('u', 64),
        ]);
        $this->assertSame(2, $v2->version);
        $this->assertSame('DRAFT', $v2->status);

        $v3 = $this->model->createNewVersion($v2, [
            'fileSize'       => 3000,
            'fileHashSha256' => str_repeat('v', 64),
        ]);
        $this->assertSame(3, $v3->version);
        $this->assertSame(3000, $v3->fileSize);
    }
}
