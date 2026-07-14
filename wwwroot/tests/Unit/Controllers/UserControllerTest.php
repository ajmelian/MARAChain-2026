<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * HTTP integration tests for UserController.
 *
 * <p>Tests CRUD operations for user identities plus TOTP enablement.</p>
 *
 * @coversNothing
 *
 * @since   1.1.1
 * @author  Aythami
 *
 * @internal
 */
final class UserControllerTest extends CIUnitTestCase
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

    /**
     * UUID of the user created in setUp for read/update/delete tests.
     *
     * @var string
     */
    private string $userId;

    /**
     * Prepare test environment before each test.
     *
     * Creates a real user in the database so that show/update/delete/totp
     * tests can operate on an existing entity.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->userModel = model(UserModel::class);
        $user = $this->userModel->create([
            'email'        => 'test-user-setup@marachain.test',
            'identityType' => 'physical',
            'firstName'    => 'Test',
            'lastName'     => 'User',
        ]);
        $this->userId = $user->id;

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
    // INDEX — GET /users
    // ──────────────────────────────────────────────────────────────

    /**
     * Listing users returns HTTP 200 with a JSON array.
     *
     * @test
     */
    public function testIndex(): void
    {
        $result = $this->get('/users');

        // RED PHASE: this assertion will fail because the route is
        // not registered and/or the controller does not exist.
        $this->assertSame(200, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // SHOW — GET /users/{id}
    // ──────────────────────────────────────────────────────────────

    /**
     * Fetching a single user by UUID returns HTTP 200 with user JSON.
     *
     * @test
     */
    public function testShow(): void
    {
        $result = $this->get('/users/' . $this->userId);

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    /**
     * Fetching a non-existent user returns HTTP 404.
     *
     * @test
     */
    public function testShowReturns404ForUnknownUser(): void
    {
        $result = $this->get('/users/00000000-0000-4000-a000-000000000000');

        $this->assertSame(404, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // CREATE — POST /users
    // ──────────────────────────────────────────────────────────────

    /**
     * Creating a user with valid data returns HTTP 201 with the user JSON.
     *
     * Covers multiple scenarios via a data provider: physical person
     * with full details, legal entity, and minimal physical person.
     *
     * @test
     *
     * @dataProvider provideValidUserData
     */
    public function testCreate(array $data): void
    {
        $result = $this->withBodyFormat('json')->post('/users', $data);

        $this->assertSame(201, $result->response()->getStatusCode());
    }

    /**
     * Data provider for valid user creation scenarios.
     *
     * @return array<string, array<array<string, string>>>
     */
    public static function provideValidUserData(): array
    {
        return [
            'physical person with all fields' => [[
                'identityType' => 'physical',
                'firstName'    => 'John',
                'lastName'     => 'Doe',
                'email'        => 'john.doe@example.com',
            ]],
            'legal entity with legal name' => [[
                'identityType' => 'legal',
                'firstName'    => 'ACME Corp',
                'legalName'    => 'ACME Corporation S.L.',
                'email'        => 'legal@acme.example.com',
            ]],
            'minimal physical person' => [[
                'identityType' => 'physical',
                'firstName'    => 'Alice',
                'lastName'     => 'Smith',
                'email'        => 'alice@example.org',
            ]],
        ];
    }

    /**
     * Creating a user without an email returns HTTP 400 (validation failure).
     *
     * @test
     */
    public function testCreateValidation(): void
    {
        $result = $this->withBodyFormat('json')->post('/users', [
            'identityType' => 'physical',
            'firstName'    => 'No',
            'lastName'     => 'Email',
            // email intentionally omitted
        ]);

        $this->assertSame(400, $result->response()->getStatusCode());
    }

    /**
     * Creating a user with an invalid identity type returns HTTP 400.
     *
     * @test
     */
    public function testCreateWithInvalidIdentityType(): void
    {
        $result = $this->withBodyFormat('json')->post('/users', [
            'identityType' => 'corporation', // invalid enum value
            'firstName'    => 'Bad',
            'lastName'     => 'Type',
            'email'        => 'bad@example.com',
        ]);

        $this->assertSame(400, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // UPDATE — PUT /users/{id}
    // ──────────────────────────────────────────────────────────────

    /**
     * Updating a user returns HTTP 200 with the updated user JSON.
     *
     * @test
     */
    public function testUpdate(): void
    {
        $result = $this->withBodyFormat('json')->put('/users/' . $this->userId, [
            'firstName'      => 'Updated',
            'lastName'       => 'Name',
            'guaranteeLevel' => 'substantial',
        ]);

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    /**
     * Updating a non-existent user returns HTTP 404.
     *
     * @test
     */
    public function testUpdateReturns404ForUnknownUser(): void
    {
        $result = $this->withBodyFormat('json')->put('/users/00000000-0000-4000-a000-000000000000', [
            'firstName' => 'Ghost',
        ]);

        $this->assertSame(404, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // DELETE — DELETE /users/{id} (soft delete / status change)
    // ──────────────────────────────────────────────────────────────

    /**
     * Deleting (soft-deleting or deactivating) a user returns HTTP 204.
     *
     * @test
     */
    public function testDelete(): void
    {
        $result = $this->delete('/users/' . $this->userId);

        $this->assertSame(204, $result->response()->getStatusCode());
    }

    /**
     * Deleting a non-existent user returns HTTP 404.
     *
     * @test
     */
    public function testDeleteReturns404ForUnknownUser(): void
    {
        $result = $this->delete('/users/00000000-0000-4000-a000-000000000000');

        $this->assertSame(404, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // TOTP — POST /users/{id}/totp
    // ──────────────────────────────────────────────────────────────

    /**
     * Enabling TOTP for a user returns HTTP 200.
     *
     * @test
     */
    public function testEnableTotp(): void
    {
        $result = $this->withBodyFormat('json')->post('/users/' . $this->userId . '/totp', [
            'totpSecret' => 'MOCK_BASE32_SECRET',
        ]);

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    /**
     * Enabling TOTP for a non-existent user returns HTTP 404.
     *
     * @test
     */
    public function testEnableTotpReturns404ForUnknownUser(): void
    {
        $result = $this->withBodyFormat('json')->post('/users/00000000-0000-4000-a000-000000000000/totp', [
            'totpSecret' => 'MOCK_BASE32_SECRET',
        ]);

        $this->assertSame(404, $result->response()->getStatusCode());
    }
}
