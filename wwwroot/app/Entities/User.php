<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * User identity entity for MARAChain.
 *
 * Internal identity representing a physical person (persona fisica)
 * or legal entity (persona juridica). NIF/NIE is stored encrypted
 * and located via deterministic HMAC with separate key.
 *
 * @property string      $id                    UUID v4 identifier
 * @property string      $identityType          physical|legal
 * @property string      $firstName             First name
 * @property string|null $lastName              Last name(s)
 * @property string|null $legalName             Legal entity name (razon social)
 * @property string|null $taxIdEncrypted        NIF/NIE encrypted via AEAD
 * @property string|null $taxIdHmac             HMAC for search key (64 chars)
 * @property string      $email                 Primary email (unique)
 * @property string|null $emailVerifiedAt      Email verification timestamp
 * @property string|null $phone                 Phone number in E.164
 * @property string|null $totpSecretEncrypted   TOTP secret encrypted via AEAD
 * @property bool        $totpEnabled           Whether TOTP is active
 * @property int         $totpFailures          Consecutive TOTP failure counter
 * @property string|null $totpBlockedUntil      Temporary block due to TOTP failures
 * @property string      $status                active|inactive|suspended|blocked
 * @property string      $guaranteeLevel        low|substantial|high (eIDAS)
 * @property string|null $lastLoginAt           Last successful authentication
 * @property string      $createdAt             Creation timestamp
 * @property string      $updatedAt             Last update timestamp
 *
 * @since 1.1.1
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
class User extends Entity
{
    protected $casts = [
        'id'                => 'string',
        'identityType'      => 'string',
        'firstName'         => 'string',
        'lastName'          => '?string',
        'legalName'         => '?string',
        'taxIdEncrypted'    => '?string',
        'taxIdHmac'         => '?string',
        'email'             => 'string',
        'emailVerifiedAt'  => '?datetime',
        'phone'             => '?string',
        'totpSecretEncrypted' => '?string',
        'totpEnabled'       => 'bool',
        'totpFailures'      => 'int',
        'totpBlockedUntil' => '?datetime',
        'status'            => 'string',
        'guaranteeLevel'    => 'string',
        'lastLoginAt'      => '?datetime',
        'createdAt'         => 'datetime',
        'updatedAt'         => 'datetime',
    ];

    protected $datamap = [
        'identity_type'       => 'identityType',
        'first_name'          => 'firstName',
        'last_name'           => 'lastName',
        'legal_name'          => 'legalName',
        'tax_id_encrypted'    => 'taxIdEncrypted',
        'tax_id_hmac'         => 'taxIdHmac',
        'email_verified_at'  => 'emailVerifiedAt',
        'totp_secret_encrypted' => 'totpSecretEncrypted',
        'totp_enabled'        => 'totpEnabled',
        'totp_failures'       => 'totpFailures',
        'totp_blocked_until' => 'totpBlockedUntil',
        'guarantee_level'     => 'guaranteeLevel',
        'last_login_at'      => 'lastLoginAt',
        'created_at'          => 'createdAt',
        'updated_at'          => 'updatedAt',
    ];

    /**
     * Check if this is a physical person identity.
     */
    public function isPhysical(): bool
    {
        return $this->identityType === 'physical';
    }

    /**
     * Check if this is a legal entity identity.
     */
    public function isLegal(): bool
    {
        return $this->identityType === 'legal';
    }

    /**
     * Check if the user account is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if the user account is blocked.
     */
    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    /**
     * Check if the user account is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Check if TOTP is configured and enabled.
     */
    public function hasTotpEnabled(): bool
    {
        return $this->totpEnabled === true;
    }

    /**
     * Check if TOTP is temporarily blocked due to failures.
     */
    public function isTotpBlocked(): bool
    {
        if ($this->totpBlockedUntil === null) {
            return false;
        }

        return strtotime((string) $this->totpBlockedUntil) > time();
    }

    /**
     * Check if email has been verified.
     */
    public function isEmailVerified(): bool
    {
        return $this->emailVerifiedAt !== null;
    }
}
