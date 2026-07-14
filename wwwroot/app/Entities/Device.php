<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * Device entity — authorized user device.
 *
 * Each device holds its own public/private key pair generated locally.
 * Adding a new device requires approval from an existing one.
 *
 * @property string      $id                      UUID v4
 * @property string      $userId                  Owner user UUID
 * @property string      $deviceName              Descriptive name (e.g. MacBook Pro Chrome)
 * @property string      $deviceType              desktop|laptop|tablet|mobile|other
 * @property string|null $operatingSystem         Detected OS
 * @property string|null $browser                 Detected browser
 * @property string      $publicKeyFingerprint    Public key fingerprint (64 chars)
 * @property string      $publicKeyAlgorithm      Algorithm (e.g. ECDSA-P256)
 * @property int         $cryptographicEpoch      Cryptographic epoch
 * @property string      $status                  active|revoked|lost
 * @property string      $firstSeenAt             First seen timestamp
 * @property string|null $lastSeenAt              Last seen timestamp
 * @property string|null $revokedAt               Revocation timestamp
 * @property string      $createdAt               Creation timestamp
 * @property string      $updatedAt               Last update timestamp
 *
 * @since 1.1.1
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
class Device extends Entity
{
    protected $casts = [
        'id'                    => 'string',
        'userId'                => 'string',
        'deviceName'            => 'string',
        'deviceType'            => 'string',
        'operatingSystem'       => '?string',
        'browser'               => '?string',
        'publicKeyFingerprint'  => 'string',
        'publicKeyAlgorithm'    => 'string',
        'cryptographicEpoch'    => 'int',
        'status'                => 'string',
        'firstSeenAt'           => 'datetime',
        'lastSeenAt'            => '?datetime',
        'revokedAt'             => '?datetime',
        'createdAt'             => 'datetime',
        'updatedAt'             => 'datetime',
    ];

    protected $datamap = [
        'user_id'                 => 'userId',
        'device_name'             => 'deviceName',
        'device_type'             => 'deviceType',
        'operating_system'        => 'operatingSystem',
        'public_key_fingerprint'  => 'publicKeyFingerprint',
        'public_key_algorithm'    => 'publicKeyAlgorithm',
        'cryptographic_epoch'     => 'cryptographicEpoch',
        'first_seen_at'           => 'firstSeenAt',
        'last_seen_at'            => 'lastSeenAt',
        'revoked_at'              => 'revokedAt',
        'created_at'              => 'createdAt',
        'updated_at'              => 'updatedAt',
    ];

    /**
     * Check if the device is currently active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the device has been revoked.
     */
    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }

    /**
     * Check if the device is reported lost.
     */
    public function isLost(): bool
    {
        return $this->status === 'lost';
    }

    /**
     * Check if the device is a mobile type.
     */
    public function isMobile(): bool
    {
        return $this->deviceType === 'mobile' || $this->deviceType === 'tablet';
    }
}
