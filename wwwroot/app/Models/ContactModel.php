<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Contact;
use CodeIgniter\Model;
use InvalidArgumentException;

/**
 * ContactModel — CRUD and identity management for contacts.
 *
 * Contacts may be physical persons or legal entities. Identity status
 * transitions through: pending -> invited -> verified or rejected.
 * Contacts can be linked to registered users on verification.
 *
 * @since  1.1.1
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
class ContactModel extends Model
{
    protected $table            = 'contacts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = Contact::class;
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $skipValidation   = true;

    protected $allowedFields = [
        'id', 'owner_id', 'linked_user_id', 'contact_type',
        'first_name', 'last_name', 'legal_name', 'attention_of',
        'tax_id_encrypted', 'tax_id_hmac', 'email_primary',
        'email_secondary_json', 'phone', 'telegram_account',
        'whatsapp_account', 'address', 'postal_code', 'province',
        'country', 'identity_status', 'public_key_fingerprint', 'notes',
    ];

    /**
     * Creates a new contact with default identity status 'pending'.
     *
     * Required fields: ownerId, emailPrimary.
     * Defaults: country='ES', identityStatus='pending'.
     *
     * @param array $data Contact data in camelCase keys.
     *
     * @return Contact The newly created contact entity.
     *
     * @throws InvalidArgumentException If required fields are missing.
     *
     * @since 1.1.1
     */
    public function createContact(array $data): Contact
    {
        if (empty($data['ownerId'])) {
            throw new InvalidArgumentException(
                'The ownerId field is required to create a contact.'
            );
        }

        if (empty($data['emailPrimary'])) {
            throw new InvalidArgumentException(
                'The emailPrimary field is required to create a contact.'
            );
        }

        $id = \App\Helpers\Uuid::v4();

        $row = [
            'id'                     => $id,
            'owner_id'               => $data['ownerId'],
            'linked_user_id'         => $data['linkedUserId'] ?? null,
            'contact_type'           => $data['contactType'] ?? 'physical_person',
            'first_name'             => $data['firstName'] ?? null,
            'last_name'              => $data['lastName'] ?? null,
            'legal_name'             => $data['legalName'] ?? null,
            'attention_of'           => $data['attentionOf'] ?? null,
            'tax_id_encrypted'       => $data['taxIdEncrypted'] ?? null,
            'tax_id_hmac'            => $data['taxIdHmac'] ?? null,
            'email_primary'          => $data['emailPrimary'],
            'email_secondary_json'   => $data['emailSecondaryJson'] ?? null,
            'phone'                  => $data['phone'] ?? null,
            'telegram_account'       => $data['telegramAccount'] ?? null,
            'whatsapp_account'       => $data['whatsappAccount'] ?? null,
            'address'                => $data['address'] ?? null,
            'postal_code'            => $data['postalCode'] ?? null,
            'province'               => $data['province'] ?? null,
            'country'                => $data['country'] ?? 'ES',
            'identity_status'        => 'pending',
            'public_key_fingerprint' => $data['publicKeyFingerprint'] ?? null,
            'notes'                  => $data['notes'] ?? null,
        ];

        $this->insert($row);

        return $this->freshEntity($id);
    }

    /**
     * Finds all contacts belonging to an owner user.
     *
     * @param string $ownerId The owner user UUID.
     *
     * @return Contact[] Array of matching entities.
     *
     * @since 1.1.1
     */
    public function findByOwnerId(string $ownerId): array
    {
        $rows = $this->db->table($this->table)
            ->where('owner_id', $ownerId)
            ->get()
            ->getResultArray();

        return $this->freshEntities($rows);
    }

    /**
     * Finds a contact by its primary email address.
     *
     * @param string $email The email address to search for.
     *
     * @return Contact|null The entity if found, null otherwise.
     *
     * @since 1.1.1
     */
    public function findByEmail(string $email): ?Contact
    {
        $row = $this->db->table($this->table)
            ->where('email_primary', $email)
            ->get()
            ->getRowArray();

        return $row ? new Contact($row) : null;
    }

    /**
     * Finds contacts filtered by identity status.
     *
     * @param string $identityStatus Status to filter (pending, invited, verified, rejected).
     *
     * @return Contact[] Array of matching entities.
     *
     * @since 1.1.1
     */
    public function findByStatus(string $identityStatus): array
    {
        $rows = $this->db->table($this->table)
            ->where('identity_status', $identityStatus)
            ->get()
            ->getResultArray();

        return $this->freshEntities($rows);
    }

    /**
     * Updates the identity status of a contact.
     *
     * Supports transitions: pending -> invited -> verified,
     * and any state -> rejected.
     *
     * @param string $id        The contact UUID.
     * @param string $newStatus The new identity status.
     *
     * @return Contact The updated contact entity.
     *
     * @since 1.1.1
     */
    public function updateIdentityStatus(string $id, string $newStatus): Contact
    {
        $this->update($id, [
            'identity_status' => $newStatus,
        ]);

        return $this->freshEntity($id);
    }

    /**
     * Verifies a contact and links it to a registered user.
     *
     * Sets identity_status to 'verified', linkedUserId, and
     * publicKeyFingerprint.
     *
     * @param string $id                   The contact UUID.
     * @param string $userId               The verified user UUID to link.
     * @param string $publicKeyFingerprint The public key fingerprint.
     *
     * @return Contact The updated contact entity.
     *
     * @since 1.1.1
     */
    public function verifyAndLink(string $id, string $userId, string $publicKeyFingerprint): Contact
    {
        $this->update($id, [
            'identity_status'        => 'verified',
            'linked_user_id'         => $userId,
            'public_key_fingerprint' => $publicKeyFingerprint,
        ]);

        return $this->freshEntity($id);
    }

    /**
     * Rejects a contact's identity verification.
     *
     * Sets identity_status to 'rejected'.
     *
     * @param Contact $contact The contact entity.
     *
     * @return Contact The updated contact entity.
     *
     * @since 1.1.1
     */
    public function rejectIdentity(Contact $contact): Contact
    {
        $this->update($contact->id, [
            'identity_status' => 'rejected',
        ]);

        return $this->freshEntity($contact->id);
    }

    /**
     * Refresh entity from database, bypassing CI4 result cache.
     *
     * @param string $id The entity UUID.
     *
     * @return Contact Fresh Contact entity.
     *
     * @since 1.1.1
     */
    private function freshEntity(string $id): Contact
    {
        $row = $this->db->table($this->table)->where('id', $id)->get()->getRowArray();

        return new Contact($row);
    }

    /**
     * Build fresh entities from raw DB rows, bypassing CI4 entity cache.
     *
     * @param array<int, array<string, mixed>> $rows Raw database rows
     *
     * @return Contact[]
     *
     * @since 1.1.1
     */
    private function freshEntities(array $rows): array
    {
        return array_map(static fn (array $row): Contact => new Contact($row), $rows);
    }

}
