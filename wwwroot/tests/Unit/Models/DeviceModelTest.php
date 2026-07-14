<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Entities\Device;
use App\Entities\User;
use App\Models\DeviceModel;
use App\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Unit tests for DeviceModel.
 *
 * Tests the Device persistence layer covering creation, retrieval,
 * filtering by status, revocation, marking lost, and last-seen updates.
 *
 * @coversNothing (RED phase — model class does not exist yet)
 *
 * @since   1.1.1
 * @author  Aythami
 *
 * @internal
 */
final class DeviceModelTest extends CIUnitTestCase
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
     * DeviceModel instance under test.
     *
     * @var DeviceModel
     */
    private DeviceModel $model;

    /**
     * A pre-created user entity used as device owner.
     *
     * @var User
     */
    private User $owner;

    /**
     * Prepare test environment before each test.
     *
     * In RED phase, DeviceModel does not exist yet — the setUp
     * will fail with a fatal error, which is the expected TDD
     * behaviour.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // RED PHASE: This line fails because App\Models\DeviceModel does not exist yet.
        $this->model = new DeviceModel();

        // We also need a UserModel for creating owners — also RED phase.
        $userModel = new UserModel();
        $this->owner = $userModel->create([
            'firstName'    => 'Device',
            'lastName'     => 'Owner',
            'email'        => 'device.owner@example.com',
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
     * Creates a device with valid data and expects a Device entity.
     *
     * @test
     */
    public function testCreateDevice(): void
    {
        $device = $this->model->create([
            'userId'               => $this->owner->id,
            'deviceName'           => 'iPhone 15 Pro',
            'deviceType'           => 'mobile',
            'publicKeyFingerprint' => str_repeat('a', 64),
            'publicKeyAlgorithm'   => 'ECDSA-P256',
        ]);

        $this->assertInstanceOf(Device::class, $device);
        $this->assertNotEmpty($device->id);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $device->id,
        );
        $this->assertSame($this->owner->id, $device->userId);
        $this->assertSame('iPhone 15 Pro', $device->deviceName);
        $this->assertSame('mobile', $device->deviceType);
        $this->assertSame(str_repeat('a', 64), $device->publicKeyFingerprint);
        $this->assertSame('active', $device->status);
    }

    /**
     * Attempting to create a device without a user ID throws an exception.
     *
     * @test
     */
    public function testCreateDeviceWithoutUserId(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->model->create([
            'deviceName'           => 'Orphan Device',
            'deviceType'           => 'desktop',
            'publicKeyFingerprint' => str_repeat('b', 64),
            'publicKeyAlgorithm'   => 'ECDSA-P256',
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // FIND
    // ──────────────────────────────────────────────────────────────

    /**
     * Finding devices by user ID returns an array of Device entities.
     *
     * @test
     */
    public function testFindByUserId(): void
    {
        // Create two devices for same owner.
        $this->model->create([
            'userId'               => $this->owner->id,
            'deviceName'           => 'MacBook Pro',
            'deviceType'           => 'laptop',
            'publicKeyFingerprint' => str_repeat('c', 64),
            'publicKeyAlgorithm'   => 'ECDSA-P256',
        ]);

        $this->model->create([
            'userId'               => $this->owner->id,
            'deviceName'           => 'iPhone 15',
            'deviceType'           => 'mobile',
            'publicKeyFingerprint' => str_repeat('d', 64),
            'publicKeyAlgorithm'   => 'ECDSA-P256',
        ]);

        $devices = $this->model->findByUserId($this->owner->id);

        $this->assertIsArray($devices);
        $this->assertCount(2, $devices);
        $this->assertContainsOnlyInstancesOf(Device::class, $devices);

        // Every device must belong to the queried owner.
        foreach ($devices as $device) {
            $this->assertSame($this->owner->id, $device->userId);
        }
    }

    /**
     * Finding active devices by user ID returns only 'active' status devices.
     *
     * @test
     */
    public function testFindActiveByUserId(): void
    {
        // Create an active device.
        $this->model->create([
            'userId'               => $this->owner->id,
            'deviceName'           => 'Active Laptop',
            'deviceType'           => 'laptop',
            'publicKeyFingerprint' => str_repeat('e', 64),
            'publicKeyAlgorithm'   => 'ECDSA-P256',
        ]);

        // Create and revoke a second device.
        $revokedDevice = $this->model->create([
            'userId'               => $this->owner->id,
            'deviceName'           => 'Revoked Phone',
            'deviceType'           => 'mobile',
            'publicKeyFingerprint' => str_repeat('f', 64),
            'publicKeyAlgorithm'   => 'ECDSA-P256',
        ]);

        $this->model->revokeDevice($revokedDevice);

        $activeDevices = $this->model->findActiveByUserId($this->owner->id);

        $this->assertIsArray($activeDevices);
        $this->assertCount(1, $activeDevices);
        $this->assertSame('Active Laptop', $activeDevices[0]->deviceName);
        $this->assertSame('active', $activeDevices[0]->status);
    }

    // ──────────────────────────────────────────────────────────────
    // STATUS TRANSITIONS
    // ──────────────────────────────────────────────────────────────

    /**
     * Revoking a device changes its status to 'revoked' and sets revokedAt.
     *
     * @test
     */
    public function testRevokeDevice(): void
    {
        $device = $this->model->create([
            'userId'               => $this->owner->id,
            'deviceName'           => 'Device to Revoke',
            'deviceType'           => 'desktop',
            'publicKeyFingerprint' => str_repeat('1', 64),
            'publicKeyAlgorithm'   => 'ECDSA-P256',
        ]);

        $result = $this->model->revokeDevice($device);

        $this->assertSame('revoked', $result->status);
        $this->assertNotNull($result->revokedAt);
    }

    /**
     * Marking a device as lost changes its status to 'lost'.
     *
     * @test
     */
    public function testMarkDeviceLost(): void
    {
        $device = $this->model->create([
            'userId'               => $this->owner->id,
            'deviceName'           => 'Device Lost',
            'deviceType'           => 'mobile',
            'publicKeyFingerprint' => str_repeat('2', 64),
            'publicKeyAlgorithm'   => 'ECDSA-P256',
        ]);

        $result = $this->model->markDeviceLost($device);

        $this->assertSame('lost', $result->status);
    }

    // ──────────────────────────────────────────────────────────────
    // LAST SEEN
    // ──────────────────────────────────────────────────────────────

    /**
     * Updating lastSeenAt sets the timestamp on the device.
     *
     * @test
     */
    public function testUpdateLastSeen(): void
    {
        $device = $this->model->create([
            'userId'               => $this->owner->id,
            'deviceName'           => 'LastSeen Device',
            'deviceType'           => 'mobile',
            'publicKeyFingerprint' => str_repeat('3', 64),
            'publicKeyAlgorithm'   => 'ECDSA-P256',
        ]);

        $before = $device->lastSeenAt;

        $result = $this->model->updateLastSeen($device);

        $this->assertNotNull($result->lastSeenAt);

        if ($before !== null) {
            $this->assertTrue(
                strtotime((string) $result->lastSeenAt) >= strtotime((string) $before),
            );
        }
    }
}
