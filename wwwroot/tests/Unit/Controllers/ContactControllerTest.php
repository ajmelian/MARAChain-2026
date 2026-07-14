<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Models\ContactModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * HTTP integration tests for ContactController.
 *
 * <p>Tests full CRUD for contacts (address book entries). Contacts may be
 * physical persons or legal entities, and go through identity verification
 * status transitions.</p>
 *
 * @coversNothing (integration test)
 *
 * @since   1.1.1
 * @author  Aythami
 *
 * @internal
 */
final class ContactControllerTest extends CIUnitTestCase
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
     * Authenticated user UUID used as owner.
     *
     * @var string
     */
    private string $testUserId;

    /**
     * Pre-created contact UUID for show/update/delete tests.
     *
     * @var string
     */
    private string $testContactId;

    /**
     * Prepare test environment before each test.
     *
     * Creates the authenticated user (owner) and a contact for
     * show/update/delete tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $userModel    = new UserModel();
        $contactModel = new ContactModel();

        // Create the authenticated user (owner)
        $user = $userModel->create([
            'firstName'    => 'Contact',
            'lastName'     => 'Owner',
            'email'        => 'contact-owner.' . bin2hex(random_bytes(4)) . '@test.com',
            'identityType' => 'physical',
        ]);
        $this->testUserId = $user->id;

        // Create a contact for show/update/delete tests
        $contact = $contactModel->createContact([
            'ownerId'      => $this->testUserId,
            'contactType'  => 'physical_person',
            'firstName'    => 'Existing',
            'lastName'     => 'Contact',
            'emailPrimary' => 'existing.' . bin2hex(random_bytes(4)) . '@test.com',
        ]);
        $this->testContactId = $contact->id;

        $this->withSession([
            'user_id' => $this->testUserId,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // INDEX — GET /contacts
    // ──────────────────────────────────────────────────────────────

    /**
     * Listing contacts returns HTTP 200 with a JSON array of the
     * authenticated user's contacts.
     *
     * @test
     */
    public function testIndex(): void
    {
        $result = $this->get('/contacts');

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    /**
     * Listing contacts filtered by identity status returns HTTP 200.
     *
     * @test
     */
    public function testIndexFilteredByStatus(): void
    {
        $result = $this->call('get', '/contacts?identity_status=verified');

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // CREATE — POST /contacts
    // ──────────────────────────────────────────────────────────────

    /**
     * Creating a contact with valid data returns HTTP 201 with the
     * contact JSON.
     *
     * Covers both physical person and legal entity scenarios via
     * a data provider. Note: the controller's {@code create()} method
     * uses {@code camelToSnake()} to convert camelCase request keys
     * to snake_case for validation rules.
     *
     * @test
     *
     * @dataProvider provideValidContactData
     */
    public function testCreate(array $data): void
    {
        $result = $this->withBodyFormat('json')->post('/contacts', $data);

        $this->assertSame(201, $result->response()->getStatusCode());
    }

    /**
     * Data provider for valid contact creation scenarios.
     *
     * @return array<string, array<array<string, mixed>>>
     */
    public static function provideValidContactData(): array
    {
        return [
            'physical person' => [[
                'contactType'    => 'physical_person',
                'firstName'      => 'Maria',
                'lastName'       => 'Garcia Lopez',
                'emailPrimary'   => 'maria.garcia@example.com',
                'phone'          => '+34600123456',
                'country'        => 'ES',
            ]],
            'legal entity' => [[
                'contactType'  => 'legal_entity',
                'legalName'    => 'Empresas Unidas S.L.',
                'attentionOf'  => 'Juan Martinez',
                'emailPrimary' => 'juan.martinez@empresasunidas.com',
                'country'      => 'ES',
            ]],
            'physical person with full address' => [[
                'contactType'  => 'physical_person',
                'firstName'    => 'Carlos',
                'lastName'     => 'Diaz',
                'emailPrimary' => 'carlos.diaz@example.com',
                'phone'        => '+34900111222',
                'address'      => 'Calle Mayor 15, 3B',
                'postalCode'   => '28013',
                'province'     => 'Madrid',
                'country'      => 'ES',
            ]],
        ];
    }

    /**
     * Creating a contact without an email returns HTTP 400.
     *
     * @test
     */
    public function testCreateWithoutEmail(): void
    {
        $result = $this->withBodyFormat('json')->post('/contacts', [
            'contactType' => 'physical_person',
            'firstName'   => 'Pedro',
            'lastName'    => 'Sanchez',
            // emailPrimary intentionally omitted
        ]);

        $this->assertSame(409, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // SHOW — GET /contacts/{id}
    // ──────────────────────────────────────────────────────────────

    /**
     * Fetching a single contact by UUID returns HTTP 200 with contact JSON.
     *
     * @test
     */
    public function testShow(): void
    {
        $result = $this->get('/contacts/' . $this->testContactId);

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    /**
     * Fetching a non-existent contact returns HTTP 404.
     *
     * @test
     */
    public function testShowReturns404ForUnknownContact(): void
    {
        $result = $this->get('/contacts/00000000-0000-4000-a000-000000000000');

        $this->assertSame(404, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // UPDATE — PUT /contacts/{id}
    // ──────────────────────────────────────────────────────────────

    /**
     * Updating a contact returns HTTP 200 with the updated contact JSON.
     *
     * @test
     */
    public function testUpdate(): void
    {
        $result = $this->withBodyFormat('json')->put('/contacts/' . $this->testContactId, [
            'firstName' => 'Updated',
            'lastName'  => 'Contact',
            'phone'     => '+34700999000',
        ]);

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    /**
     * Updating a non-existent contact returns HTTP 404.
     *
     * @test
     */
    public function testUpdateReturns404ForUnknownContact(): void
    {
        $result = $this->withBodyFormat('json')->put('/contacts/00000000-0000-4000-a000-000000000000', [
            'firstName' => 'Ghost',
        ]);

        $this->assertSame(404, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // DELETE — DELETE /contacts/{id}
    // ──────────────────────────────────────────────────────────────

    /**
     * Deleting a contact returns HTTP 204 with no content.
     *
     * @test
     */
    public function testDelete(): void
    {
        $result = $this->delete('/contacts/' . $this->testContactId);

        $this->assertSame(204, $result->response()->getStatusCode());
    }

    /**
     * Deleting a non-existent contact returns HTTP 404.
     *
     * @test
     */
    public function testDeleteReturns404ForUnknownContact(): void
    {
        $result = $this->delete('/contacts/00000000-0000-4000-a000-000000000000');

        $this->assertSame(404, $result->response()->getStatusCode());
    }
}
