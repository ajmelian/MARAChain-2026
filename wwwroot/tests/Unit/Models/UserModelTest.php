<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Entities\User;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Unit tests for UserModel.
 *
 * Tests the User persistence layer covering creation, retrieval,
 * TOTP management, and status lifecycle transitions.
 *
 * @coversNothing (RED phase — model class does not exist yet)
 *
 * @since   1.1.1
 * @author  Aythami
 *
 * @internal
 */
final class UserModelTest extends CIUnitTestCase
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
     * UserModel instance under test.
     *
     * @var UserModel
     */
    private UserModel $model;

    /**
     * Prepare test environment before each test.
     *
     * In RED phase, the model class App\Models\UserModel does not
     * exist yet, so this setUp will cause a fatal error. This is
     * the expected behaviour for TDD Red-Green-Refactor.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // RED PHASE: This line fails because App\Models\UserModel does not exist yet.
        $this->model = new UserModel();
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->model);
    }

    // ──────────────────────────────────────────────────────────────
    // CREATE
    // ──────────────────────────────────────────────────────────────

    /**
     * Creates a user with valid data and expects a User entity with UUID id.
     *
     * @test
     *
     * @dataProvider provideValidUserData
     */
    public function testCreateUserWithValidData(array $data): void
    {
        $user = $this->model->create($data);

        $this->assertInstanceOf(User::class, $user);
        $this->assertNotEmpty($user->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $user->id,
        );
        $this->assertSame($data['firstName'], $user->firstName);
        $this->assertSame($data['lastName'], $user->lastName);
        $this->assertSame($data['email'], $user->email);
        $this->assertSame($data['identityType'], $user->identityType);
        $this->assertSame('active', $user->status);
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
                'firstName'    => 'John',
                'lastName'     => 'Doe',
                'email'        => 'john.doe@example.com',
                'identityType' => 'physical',
            ]],
            'legal person with legal name' => [[
                'firstName'    => 'ACME Corp',
                'lastName'     => null,
                'email'        => 'legal@acme.example.com',
                'identityType' => 'legal',
            ]],
            'minimal physical person' => [[
                'firstName'    => 'Alice',
                'lastName'     => 'Smith',
                'email'        => 'alice@example.org',
                'identityType' => 'physical',
            ]],
        ];
    }

    /**
     * Attempting to create a user without an email throws an exception.
     *
     * @test
     */
    public function testCreateUserWithoutEmail(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->model->create([
            'firstName'    => 'No',
            'lastName'     => 'Email',
            'identityType' => 'physical',
        ]);
    }

    /**
     * Creating a user with an email that already exists throws an exception.
     *
     * @test
     */
    public function testCreateUserWithDuplicateEmail(): void
    {
        $this->model->create([
            'firstName'    => 'First',
            'lastName'     => 'User',
            'email'        => 'duplicate@example.com',
            'identityType' => 'physical',
        ]);

        $this->expectException(\RuntimeException::class);

        $this->model->create([
            'firstName'    => 'Second',
            'lastName'     => 'User',
            'email'        => 'duplicate@example.com',
            'identityType' => 'physical',
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // FIND BY EMAIL
    // ──────────────────────────────────────────────────────────────

    /**
     * Finding a user by an existing email returns a User entity.
     *
     * @test
     */
    public function testFindByExistingEmail(): void
    {
        $created = $this->model->create([
            'firstName'    => 'Jane',
            'lastName'     => 'Doe',
            'email'        => 'jane@example.com',
            'identityType' => 'physical',
        ]);

        $found = $this->model->findByEmail('jane@example.com');

        $this->assertInstanceOf(User::class, $found);
        $this->assertSame($created->id, $found->id);
        $this->assertSame('jane@example.com', $found->email);
    }

    /**
     * Finding a user by a non-existent email returns null.
     *
     * @test
     */
    public function testFindByNonExistentEmail(): void
    {
        $result = $this->model->findByEmail('ghost@example.com');

        $this->assertNull($result);
    }

    // ──────────────────────────────────────────────────────────────
    // FIND BY TAX ID HMAC
    // ──────────────────────────────────────────────────────────────

    /**
     * Finding a user by tax ID HMAC returns the matching User entity.
     *
     * @test
     */
    public function testFindByTaxIdHmac(): void
    {
        $taxIdHmac = str_repeat('a', 64);

        $created = $this->model->create([
            'firstName'    => 'TaxId',
            'lastName'     => 'User',
            'email'        => 'taxid@example.com',
            'identityType' => 'physical',
        ]);

        $this->model->update($created->id, ['tax_id_hmac' => $taxIdHmac]);

        $found = $this->model->findByTaxIdHmac($taxIdHmac);

        $this->assertNotNull($found);
        $this->assertInstanceOf(User::class, $found);
        $this->assertSame($created->id, $found->id);
    }

    // ──────────────────────────────────────────────────────────────
    // TOTP MANAGEMENT
    // ──────────────────────────────────────────────────────────────

    /**
     * Enabling TOTP marks totpEnabled as true on the user.
     *
     * @test
     */
    public function testEnableTotp(): void
    {
        $user = $this->model->create([
            'firstName'    => 'Totp',
            'lastName'     => 'Enable',
            'email'        => 'totpenable@example.com',
            'identityType' => 'physical',
        ]);

        $result = $this->model->enableTotp($user, 'MOCK_TOTP_SECRET');

        $this->assertTrue($result->totpEnabled);
    }

    /**
     * Disabling TOTP marks totpEnabled as false on the user.
     *
     * @test
     */
    public function testDisableTotp(): void
    {
        $user = $this->model->create([
            'firstName'    => 'Totp',
            'lastName'     => 'Disable',
            'email'        => 'totpdisable@example.com',
            'identityType' => 'physical',
        ]);

        // First enable, then disable.
        $this->model->enableTotp($user, 'MOCK_TOTP_SECRET');
        $result = $this->model->disableTotp($user);

        $this->assertFalse($result->totpEnabled);
    }

    /**
     * Incrementing TOTP failures increases the counter.
     *
     * @test
     */
    public function testIncrementTotpFailures(): void
    {
        $user = $this->model->create([
            'firstName'    => 'Totp',
            'lastName'     => 'Fail',
            'email'        => 'totpfail@example.com',
            'identityType' => 'physical',
        ]);

        $this->model->enableTotp($user, 'MOCK_TOTP_SECRET');

        $result = $this->model->incrementTotpFailures($user);
        $this->assertSame(1, $result->totpFailures);

        $result = $this->model->incrementTotpFailures($result);
        $this->assertSame(2, $result->totpFailures);

        $result = $this->model->incrementTotpFailures($result);
        $this->assertSame(3, $result->totpFailures);
    }

    /**
     * After 5 consecutive TOTP failures, the user gets blocked.
     *
     * @test
     */
    public function testBlockAfterMaxTotpFailures(): void
    {
        $user = $this->model->create([
            'firstName'    => 'Totp',
            'lastName'     => 'Block',
            'email'        => 'totpblock@example.com',
            'identityType' => 'physical',
        ]);

        $this->model->enableTotp($user, 'MOCK_TOTP_SECRET');

        // Increment failures 5 times.
        $result = $user;
        for ($i = 0; $i < 5; $i++) {
            $result = $this->model->incrementTotpFailures($result);
        }

        $this->assertSame(5, $result->totpFailures);
        $this->assertSame('blocked', $result->status);
        $this->assertNotNull($result->totpBlockedUntil);
    }

    /**
     * Resetting TOTP failures sets the counter to zero.
     *
     * @test
     */
    public function testResetTotpFailures(): void
    {
        $user = $this->model->create([
            'firstName'    => 'Totp',
            'lastName'     => 'Reset',
            'email'        => 'totpreset@example.com',
            'identityType' => 'physical',
        ]);

        $this->model->enableTotp($user, 'MOCK_TOTP_SECRET');

        // Increment a few times first.
        $user = $this->model->incrementTotpFailures($user);
        $user = $this->model->incrementTotpFailures($user);
        $this->assertSame(2, $user->totpFailures);

        // Reset.
        $result = $this->model->resetTotpFailures($user);

        $this->assertSame(0, $result->totpFailures);
    }

    // ──────────────────────────────────────────────────────────────
    // STATUS MANAGEMENT
    // ──────────────────────────────────────────────────────────────

    /**
     * Activating a user changes status to 'active'.
     *
     * @test
     */
    public function testActivateUser(): void
    {
        $user = $this->model->create([
            'firstName'    => 'Status',
            'lastName'     => 'Activate',
            'email'        => 'activate@example.com',
            'identityType' => 'physical',
        ]);

        // Create as inactive first to test activation.
        $user->status = 'inactive';
        $result = $this->model->updateStatus($user, 'active');

        $this->assertSame('active', $result->status);
    }

    /**
     * Suspending a user changes status to 'suspended'.
     *
     * @test
     */
    public function testSuspendUser(): void
    {
        $user = $this->model->create([
            'firstName'    => 'Status',
            'lastName'     => 'Suspend',
            'email'        => 'suspend@example.com',
            'identityType' => 'physical',
        ]);

        $result = $this->model->updateStatus($user, 'suspended');

        $this->assertSame('suspended', $result->status);
    }

    /**
     * Blocking a user changes status to 'blocked'.
     *
     * @test
     */
    public function testBlockUser(): void
    {
        $user = $this->model->create([
            'firstName'    => 'Status',
            'lastName'     => 'Block',
            'email'        => 'block@example.com',
            'identityType' => 'physical',
        ]);

        $result = $this->model->updateStatus($user, 'blocked');

        $this->assertSame('blocked', $result->status);
    }
}
