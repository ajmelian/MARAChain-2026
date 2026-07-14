<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * Contact entity — user's address book entry.
 *
 * May be a physical person or legal entity. Represents previous
 * recipients, pending or verified identities. Contact data does not
 * imply document authorization.
 *
 * @property string      $id                    UUID v4
 * @property string      $ownerId               Owner user UUID
 * @property string|null $linkedUserId          Linked verified user UUID
 * @property string      $contactType           physical_person|legal_entity
 * @property string|null $firstName             First name
 * @property string|null $lastName              Last name(s)
 * @property string|null $legalName             Legal entity name
 * @property string|null $attentionOf           Attention of (mandatory for legal)
 * @property string|null $taxIdEncrypted        NIF/NIE/CIF encrypted
 * @property string|null $taxIdHmac             HMAC for search
 * @property string      $emailPrimary          Primary email (required)
 * @property string|null $emailSecondaryJson    Additional emails JSON
 * @property string|null $phone                 Phone in E.164
 * @property string|null $telegramAccount       Telegram account
 * @property string|null $whatsappAccount       WhatsApp account
 * @property string|null $address               Postal address
 * @property string|null $postalCode            Postal code (text, preserves leading zeros)
 * @property string|null $province              Province
 * @property string      $country               ISO 3166-1 alpha-2 country code
 * @property string      $identityStatus        pending|invited|verified|rejected
 * @property string|null $publicKeyFingerprint  Public key fingerprint (when verified)
 * @property string|null $notes                 Private notes
 * @property string      $createdAt             Creation timestamp
 * @property string      $updatedAt             Last update timestamp
 *
 * @since 1.1.1
 * @author Aythami
 */
class Contact extends Entity
{
    protected $casts = [
        'id'                    => 'string',
        'ownerId'               => 'string',
        'linkedUserId'          => '?string',
        'contactType'           => 'string',
        'firstName'             => '?string',
        'lastName'              => '?string',
        'legalName'             => '?string',
        'attentionOf'           => '?string',
        'taxIdEncrypted'        => '?string',
        'taxIdHmac'             => '?string',
        'emailPrimary'          => 'string',
        'emailSecondaryJson'    => '?string',
        'phone'                 => '?string',
        'telegramAccount'       => '?string',
        'whatsappAccount'       => '?string',
        'address'               => '?string',
        'postalCode'            => '?string',
        'province'              => '?string',
        'country'               => 'string',
        'identityStatus'        => 'string',
        'publicKeyFingerprint'  => '?string',
        'notes'                 => '?string',
        'createdAt'             => 'datetime',
        'updatedAt'             => 'datetime',
    ];

    protected $datamap = [
        'owner_id'                => 'ownerId',
        'linked_user_id'          => 'linkedUserId',
        'contact_type'            => 'contactType',
        'first_name'              => 'firstName',
        'last_name'               => 'lastName',
        'legal_name'              => 'legalName',
        'attention_of'            => 'attentionOf',
        'tax_id_encrypted'        => 'taxIdEncrypted',
        'tax_id_hmac'             => 'taxIdHmac',
        'email_primary'           => 'emailPrimary',
        'email_secondary_json'    => 'emailSecondaryJson',
        'telegram_account'        => 'telegramAccount',
        'whatsapp_account'        => 'whatsappAccount',
        'postal_code'             => 'postalCode',
        'identity_status'         => 'identityStatus',
        'public_key_fingerprint'  => 'publicKeyFingerprint',
        'created_at'              => 'createdAt',
        'updated_at'              => 'updatedAt',
    ];

    /**
     * Check if this is a physical person contact.
     */
    public function isPhysicalPerson(): bool
    {
        return $this->contactType === 'physical_person';
    }

    /**
     * Check if this is a legal entity contact.
     */
    public function isLegalEntity(): bool
    {
        return $this->contactType === 'legal_entity';
    }

    /**
     * Check if the contact's identity has been verified.
     */
    public function isVerified(): bool
    {
        return $this->identityStatus === 'verified';
    }

    /**
     * Check if the contact is linked to a registered user.
     */
    public function isLinked(): bool
    {
        return $this->linkedUserId !== null;
    }

    /**
     * Check if the contact has a pending invitation.
     */
    public function isPending(): bool
    {
        return $this->identityStatus === 'pending';
    }
}
