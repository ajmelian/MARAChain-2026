<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\User;
use CodeIgniter\Model;

/**
 * UserModel — persistence layer for User entities.
 *
 * Manages the full lifecycle: creation, retrieval,
 * TOTP management (block after 5 failures), and status transitions.
 *
 * @since  1.1.1
 * @author Aythami
 */
class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = User::class;
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $skipValidation   = true;

    protected $allowedFields = [
        'id',
        'shield_user_id',
        'identity_type',
        'first_name',
        'last_name',
        'legal_name',
        'tax_id_encrypted',
        'tax_id_hmac',
        'email',
        'email_verified_at',
        'phone',
        'totp_secret_encrypted',
        'totp_enabled',
        'totp_failures',
        'totp_blocked_until',
        'status',
        'guarantee_level',
        'last_login_at',
    ];

    /**
     * Find a user by SHIELD user ID (auto-increment INT).
     *
     * @param int $shieldUserId SHIELD shield_users.id
     *
     * @return User|null User entity or null if not found
     *
     * @since 1.3.0
     */
    public function findByShieldUserId(int $shieldUserId): ?User
    {
        return $this->where('shield_user_id', $shieldUserId)->first();
    }

    /**
     * Link a custom user to a SHIELD user.
     *
     * @param string $userId       UUID of the custom user
     * @param int    $shieldUserId SHIELD shield_users.id
     *
     * @return bool
     *
     * @since 1.3.0
     */
    public function linkToShield(string $userId, int $shieldUserId): bool
    {
        return $this->update($userId, ['shield_user_id' => $shieldUserId]);
    }

    /**
     * Create a new user.
     *
     * @param array<string, mixed> $data User data (camelCase or snake_case)
     *
     * @return User Persisted user entity
     *
     * @throws \RuntimeException When email is missing or already exists
     *
     * @since 1.1.1
     */
    public function create(array $data): User
    {
        $email = $data['email'] ?? '';

        if ($email === '') {
            throw new \RuntimeException('Email is required.');
        }

        $existing = $this->where('email', $email)->first();
        if ($existing !== null) {
            throw new \RuntimeException('A user with this email already exists.');
        }

        $id = $this->generateUuidV4();

        $row = [
            'id'              => $id,
            'identity_type'   => $data['identityType'] ?? $data['identity_type'] ?? 'physical',
            'first_name'      => $data['firstName'] ?? $data['first_name'] ?? '',
            'last_name'       => $data['lastName'] ?? $data['last_name'] ?? null,
            'legal_name'      => $data['legalName'] ?? $data['legal_name'] ?? null,
            'email'           => $email,
            'status'          => $data['status'] ?? 'active',
            'guarantee_level' => $data['guaranteeLevel'] ?? $data['guarantee_level'] ?? 'low',
        ];

        $this->insert($row);

        return $this->freshEntity($id);
    }

    /**
     * Find a user by email address.
     *
     * @param string $email Email to search for
     *
     * @return User|null User entity or null if not found
     *
     * @since 1.1.1
     */
    public function findByEmail(string $email): ?User
    {
        return $this->where('email', $email)->first();
    }

    /**
     * Find a user by the HMAC of their NIF/NIE.
     *
     * @param string $hmac HMAC in hexadecimal (64 characters)
     *
     * @return User|null User entity or null if not found
     *
     * @since 1.1.1
     */
    public function findByTaxIdHmac(string $hmac): ?User
    {
        return $this->where('tax_id_hmac', $hmac)->first();
    }

    /**
     * Enable TOTP for a user.
     *
     * Stores the encrypted secret and marks TOTP as enabled.
     *
     * @param User   $user            User entity
     * @param string $encryptedSecret TOTP secret encrypted via AEAD
     *
     * @return User Updated user entity
     *
     * @since 1.1.1
     */
    public function enableTotp(User $user, string $encryptedSecret): User
    {
        $this->update($user->id, [
            'totp_secret_encrypted' => $encryptedSecret,
            'totp_enabled'          => 1,
        ]);

        return $this->freshEntity($user->id);
    }

    /**
     * Disable TOTP for a user.
     *
     * Removes the secret, marks TOTP as disabled,
     * and resets the failure counter.
     *
     * @param User $user User entity
     *
     * @return User Updated user entity
     *
     * @since 1.1.1
     */
    public function disableTotp(User $user): User
    {
        $this->update($user->id, [
            'totp_secret_encrypted' => null,
            'totp_enabled'          => 0,
            'totp_failures'         => 0,
            'totp_blocked_until'    => null,
        ]);

        return $this->freshEntity($user->id);
    }

    /**
     * Increment the TOTP failure counter.
     *
     * After 5 or more failures, the user is blocked and
     * totp_blocked_until is set to now + 30 minutes.
     *
     * @param User $user User entity
     *
     * @return User Updated user entity
     *
     * @since 1.1.1
     */
    public function incrementTotpFailures(User $user): User
    {
        $row       = $this->db->table($this->table)->where('id', $user->id)->get()->getRowArray();
        $current   = (int) ($row['totp_failures'] ?? 0);
        $failures  = $current + 1;
        $updateData = [
            'totp_failures' => $failures,
        ];

        if ($failures >= 5) {
            $updateData['status']            = 'blocked';
            $updateData['totp_blocked_until'] = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        }

        $this->update($user->id, $updateData);

        return $this->freshEntity($user->id);
    }

    /**
     * Reset the TOTP failure counter to zero.
     *
     * Also removes any temporary block.
     *
     * @param User $user User entity
     *
     * @return User Updated user entity
     *
     * @since 1.1.1
     */
    public function resetTotpFailures(User $user): User
    {
        $this->update($user->id, [
            'totp_failures'      => 0,
            'totp_blocked_until' => null,
        ]);

        return $this->freshEntity($user->id);
    }

    /**
     * Manually block TOTP for a user.
     *
     * Sets status to 'blocked' and blocks TOTP for 30 minutes.
     *
     * @param User $user User entity
     *
     * @return User Updated user entity
     *
     * @since 1.1.1
     */
    public function blockTotp(User $user): User
    {
        $this->update($user->id, [
            'status'             => 'blocked',
            'totp_blocked_until' => date('Y-m-d H:i:s', strtotime('+30 minutes')),
        ]);

        return $this->freshEntity($user->id);
    }

    /**
     * Update the status of a user.
     *
     * @param User   $user   User entity
     * @param string $status New status (active|inactive|suspended|blocked)
     *
     * @return User Updated user entity
     *
     * @since 1.1.1
     */
    public function updateStatus(User $user, string $status): User
    {
        $this->update($user->id, ['status' => $status]);

        return $this->freshEntity($user->id);
    }

    /**
     * Refresh entity from database, bypassing CI4 result cache.
     *
     * @return User Fresh User entity
     *
     * @since 1.1.1
     */
    private function freshEntity(string $id): User
    {
        $row = $this->db->table($this->table)->where('id', $id)->get()->getRowArray();

        return new User($row);
    }

    /**
     * Build fresh entities from raw DB rows, bypassing CI4 entity cache.
     *
     * @param array<string, mixed> $rows Raw database rows
     *
     * @return User[]
     *
     * @since 1.1.1
     */
    private function freshEntities(array $rows): array
    {
        return array_map(static fn (array $row): User => new User($row), $rows);
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
