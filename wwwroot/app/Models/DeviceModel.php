<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Device;
use CodeIgniter\Model;

/**
 * DeviceModel — persistence layer for Device entities.
 *
 * Manages the lifecycle of authorized devices: creation, retrieval
 * by user, filtering by active status, revocation, marking as lost,
 * and last-seen updates.
 *
 * @package App\Models
 * @author  Aythami
 * @since   1.1.1
 */
class DeviceModel extends Model
{
    protected $table            = 'devices';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = Device::class;
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $skipValidation   = true;

    protected $allowedFields = [
        'id',
        'user_id',
        'device_name',
        'device_type',
        'operating_system',
        'browser',
        'public_key_fingerprint',
        'public_key_algorithm',
        'cryptographic_epoch',
        'status',
        'first_seen_at',
        'last_seen_at',
        'revoked_at',
    ];

    /**
     * Create a new authorized device.
     *
     * Validates that userId is present, generates a UUID v4,
     * and sets default status to 'active' with firstSeenAt as now.
     *
     * @param array<string, mixed> $data Device data (camelCase keys)
     *
     * @return Device Persisted device entity
     *
     * @throws \RuntimeException When userId is missing
     *
     * @since 1.1.1
     */
    public function create(array $data): Device
    {
        if (empty($data['userId'] ?? '')) {
            throw new \RuntimeException('User ID is required.');
        }

        $id = $this->generateUuidV4();

        $row = [
            'id'                    => $id,
            'user_id'               => $data['userId'],
            'device_name'           => $data['deviceName'] ?? '',
            'device_type'           => $data['deviceType'] ?? '',
            'operating_system'      => $data['operatingSystem'] ?? null,
            'browser'               => $data['browser'] ?? null,
            'public_key_fingerprint' => $data['publicKeyFingerprint'] ?? '',
            'public_key_algorithm'  => $data['publicKeyAlgorithm'] ?? '',
            'cryptographic_epoch'   => $data['cryptographicEpoch'] ?? 1,
            'status'                => $data['status'] ?? 'active',
            'first_seen_at'         => date('Y-m-d H:i:s'),
        ];

        $this->insert($row);

        return $this->freshEntity($id);
    }

    /**
     * Find all devices belonging to a user.
     *
     * @param string $userId Owner user UUID
     *
     * @return Device[] List of devices
     *
     * @since 1.1.1
     */
    public function findByUserId(string $userId): array
    {
        $rows = $this->db->table($this->table)
            ->where('user_id', $userId)
            ->get()
            ->getResultArray();

        return $this->freshEntities($rows);
    }

    /**
     * Find active devices belonging to a user.
     *
     * Only returns devices with status 'active'.
     *
     * @param string $userId Owner user UUID
     *
     * @return Device[] List of active devices
     *
     * @since 1.1.1
     */
    public function findActiveByUserId(string $userId): array
    {
        $rows = $this->db->table($this->table)
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->get()
            ->getResultArray();

        return $this->freshEntities($rows);
    }

    /**
     * Revoke a device.
     *
     * Sets status to 'revoked' and records the revocation timestamp.
     *
     * @param Device $device Device entity
     *
     * @return Device Updated device entity
     *
     * @since 1.1.1
     */
    public function revokeDevice(Device $device): Device
    {
        $this->update($device->id, [
            'status'     => 'revoked',
            'revoked_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->freshEntity($device->id);
    }

    /**
     * Mark a device as lost.
     *
     * Sets status to 'lost'.
     *
     * @param Device $device Device entity
     *
     * @return Device Updated device entity
     *
     * @since 1.1.1
     */
    public function markDeviceLost(Device $device): Device
    {
        $this->update($device->id, [
            'status' => 'lost',
        ]);

        return $this->freshEntity($device->id);
    }

    /**
     * Update the last-seen timestamp of a device.
     *
     * Sets lastSeenAt to the current server time.
     *
     * @param Device $device Device entity
     *
     * @return Device Updated device entity
     *
     * @since 1.1.1
     */
    public function updateLastSeen(Device $device): Device
    {
        $this->update($device->id, [
            'last_seen_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->freshEntity($device->id);
    }

    /**
     * Refresh entity from database, bypassing CI4 result cache.
     *
     * @param string $id Device UUID
     *
     * @return Device Fresh Device entity
     *
     * @since 1.1.1
     */
    private function freshEntity(string $id): Device
    {
        $row = $this->db->table($this->table)
            ->where('id', $id)
            ->get()
            ->getRowArray();

        return new Device($row);
    }

    /**
     * Build fresh entities from raw DB rows, bypassing CI4 entity cache.
     *
     * @param array<int, array<string, mixed>> $rows Raw database rows
     *
     * @return Device[]
     *
     * @since 1.1.1
     */
    private function freshEntities(array $rows): array
    {
        return array_map(static fn (array $row): Device => new Device($row), $rows);
    }

    /**
     * Generate a UUID v4 compatible with RFC 4122.
     *
     * @return string UUID v4 in canonical 8-4-4-4-12 format
     *
     * @since 1.1.1
     */
    private function generateUuidV4(): string
    {
        $data = random_bytes(16);

        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
