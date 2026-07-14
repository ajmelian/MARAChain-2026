<?php

declare(strict_types=1);

namespace Tests\Unit\Controllers;

use App\Models\DeviceModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * HTTP integration tests for DeviceController.
 *
 * <p>Tests CRUD operations for device registration and revocation.</p>
 *
 * @coversNothing
 *
 * @since   1.1.1
 * @author  Aythami
 *
 * @internal
 */
final class DeviceControllerTest extends CIUnitTestCase
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

    private DeviceModel $deviceModel;

    /**
     * UUID of the user created in setUp.
     *
     * @var string
     */
    private string $userId;

    /**
     * UUID of the device created in setUp for read/revoke tests.
     *
     * @var string
     */
    private string $deviceId;

    /**
     * Prepare test environment before each test.
     *
     * Creates a real user and device in the database so that
     * show/revoke tests can operate on existing entities.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->userModel   = model(UserModel::class);
        $this->deviceModel = model(DeviceModel::class);

        $user = $this->userModel->create([
            'email'        => 'device-test-user@marachain.test',
            'identityType' => 'physical',
            'firstName'    => 'Device',
            'lastName'     => 'Owner',
        ]);
        $this->userId = $user->id;

        $device = $this->deviceModel->create([
            'userId'                => $this->userId,
            'deviceName'            => 'Test Device',
            'deviceType'            => 'desktop',
            'operatingSystem'       => 'Linux x86_64',
            'browser'               => 'Firefox 128',
            'publicKeyFingerprint'  => 'f1e2d3c4b5a6f7e8d9c0b1a2f3e4d5c6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6',
            'publicKeyAlgorithm'    => 'ED25519',
        ]);
        $this->deviceId = $device->id;

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
    // INDEX — GET /devices
    // ──────────────────────────────────────────────────────────────

    /**
     * Listing devices returns HTTP 200 with a JSON array of the
     * authenticated user's registered devices.
     *
     * @test
     */
    public function testIndex(): void
    {
        $result = $this->get('/devices');

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // SHOW — GET /devices/{id}
    // ──────────────────────────────────────────────────────────────

    /**
     * Fetching a single device by UUID returns HTTP 200 with device JSON.
     *
     * @test
     */
    public function testShow(): void
    {
        $result = $this->get('/devices/' . $this->deviceId);

        $this->assertSame(200, $result->response()->getStatusCode());
    }

    /**
     * Fetching a non-existent device returns HTTP 404.
     *
     * @test
     */
    public function testShowReturns404ForUnknownDevice(): void
    {
        $result = $this->get('/devices/00000000-0000-4000-a000-000000000000');

        $this->assertSame(404, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // REGISTER — POST /devices
    // ──────────────────────────────────────────────────────────────

    /**
     * Registering a new device returns HTTP 201 with the device JSON.
     *
     * Covers multiple device types via a data provider: desktop,
     * laptop, tablet, and mobile.
     *
     * @test
     *
     * @dataProvider provideValidDeviceData
     */
    public function testRegister(array $data): void
    {
        $result = $this->withBodyFormat('json')->post('/devices', $data);

        $this->assertSame(201, $result->response()->getStatusCode());
    }

    /**
     * Data provider for valid device registration scenarios.
     *
     * @return array<string, array<array<string, mixed>>>
     */
    public static function provideValidDeviceData(): array
    {
        $fingerprint = 'f1e2d3c4b5a6f7e8d9c0b1a2f3e4d5c6a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6';

        return [
            'desktop workstation' => [[
                'deviceName'           => 'Office Desktop',
                'deviceType'           => 'desktop',
                'operatingSystem'      => 'Linux x86_64',
                'browser'              => 'Firefox 128',
                'publicKeyFingerprint'  => $fingerprint,
                'publicKeyAlgorithm'   => 'ED25519',
            ]],
            'laptop' => [[
                'deviceName'           => 'Work Laptop',
                'deviceType'           => 'laptop',
                'operatingSystem'      => 'macOS 15',
                'browser'              => 'Safari 18',
                'publicKeyFingerprint'  => $fingerprint,
                'publicKeyAlgorithm'   => 'ECDSA-P256',
            ]],
            'tablet' => [[
                'deviceName'           => 'iPad Pro',
                'deviceType'           => 'tablet',
                'operatingSystem'      => 'iPadOS 18',
                'browser'              => 'Safari',
                'publicKeyFingerprint'  => $fingerprint,
                'publicKeyAlgorithm'   => 'ECDSA-P256',
            ]],
            'mobile phone' => [[
                'deviceName'           => 'Android Phone',
                'deviceType'           => 'mobile',
                'operatingSystem'      => 'Android 15',
                'browser'              => 'Chrome',
                'publicKeyFingerprint'  => $fingerprint,
                'publicKeyAlgorithm'   => 'RSA-2048',
            ]],
        ];
    }

    /**
     * Registering a device without a public key fingerprint returns HTTP 400.
     *
     * @test
     */
    public function testRegisterWithoutFingerprint(): void
    {
        $result = $this->withBodyFormat('json')->post('/devices', [
            'deviceName' => 'No Fingerprint Device',
            'deviceType' => 'other',
            // publicKeyFingerprint intentionally omitted
        ]);

        $this->assertSame(400, $result->response()->getStatusCode());
    }

    // ──────────────────────────────────────────────────────────────
    // REVOKE — DELETE /devices/{id}
    // ──────────────────────────────────────────────────────────────

    /**
     * Revoking a device returns HTTP 204 with no content.
     *
     * @test
     */
    public function testRevoke(): void
    {
        $result = $this->delete('/devices/' . $this->deviceId);

        $this->assertSame(204, $result->response()->getStatusCode());
    }

    /**
     * Revoking a non-existent device returns HTTP 404.
     *
     * @test
     */
    public function testRevokeReturns404ForUnknownDevice(): void
    {
        $result = $this->delete('/devices/00000000-0000-4000-a000-000000000000');

        $this->assertSame(404, $result->response()->getStatusCode());
    }
}
