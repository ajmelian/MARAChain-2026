<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * Document entity with versioning.
 *
 * Each document has a manifest linking hash, version, sender, recipients
 * and metadata. Content never reaches the backend in plaintext.
 *
 * @property string      $id                  UUID v4
 * @property string      $ownerId             Owner user UUID
 * @property string      $title               Document title
 * @property string|null $description         Description or motivation
 * @property string      $mimeType            MIME type (PDF only in MVP)
 * @property int         $fileSize            Size in bytes before encryption
 * @property string      $fileHashSha256      SHA-256 of original document
 * @property int         $version             Document version (immutable)
 * @property string      $status              DRAFT|SEALED|ENCRYPTED|ARCHIVED|DESTROYED
 * @property string|null $manifestHash        SHA-256 of canonicalized manifest
 * @property string|null $manifestJson        Full manifest JSON (canonicalized)
 * @property string|null $cid                 Content Identifier in private IPFS
 * @property string|null $encryptionFormat    Encryption format (marachain-envelope v1)
 * @property string|null $contentCipher       Symmetric encryption algorithm
 * @property string|null $sealedAt            Seal timestamp
 * @property string|null $encryptedAt         Encryption timestamp
 * @property string|null $archivedAt          Archive timestamp
 * @property string|null $destroyedAt         Destruction timestamp
 * @property string      $createdAt           Creation timestamp
 * @property string      $updatedAt           Last update timestamp
 *
 * @since 1.1.1
 * @author Aythami
 */
class Document extends Entity
{
    protected $casts = [
        'id'               => 'string',
        'ownerId'          => 'string',
        'title'            => 'string',
        'description'      => '?string',
        'mimeType'         => 'string',
        'fileSize'         => 'int',
        'fileHashSha256'   => 'string',
        'version'          => 'int',
        'status'           => 'string',
        'manifestHash'     => '?string',
        'manifestJson'     => '?string',
        'cid'              => '?string',
        'encryptionFormat' => '?string',
        'contentCipher'    => '?string',
        'sealedAt'         => '?datetime',
        'encryptedAt'      => '?datetime',
        'archivedAt'       => '?datetime',
        'destroyedAt'      => '?datetime',
        'createdAt'        => 'datetime',
        'updatedAt'        => 'datetime',
    ];

    protected $datamap = [
        'owner_id'           => 'ownerId',
        'mime_type'          => 'mimeType',
        'file_size'          => 'fileSize',
        'file_hash_sha256'   => 'fileHashSha256',
        'manifest_hash'      => 'manifestHash',
        'manifest_json'      => 'manifestJson',
        'encryption_format'  => 'encryptionFormat',
        'content_cipher'     => 'contentCipher',
        'sealed_at'          => 'sealedAt',
        'encrypted_at'       => 'encryptedAt',
        'archived_at'        => 'archivedAt',
        'destroyed_at'       => 'destroyedAt',
        'created_at'         => 'createdAt',
        'updated_at'         => 'updatedAt',
    ];

    /**
     * Check if the document is in DRAFT status.
     */
    public function isDraft(): bool
    {
        return $this->status === 'DRAFT';
    }

    /**
     * Check if the document has been sealed (immutable).
     */
    public function isSealed(): bool
    {
        return $this->status === 'SEALED';
    }

    /**
     * Check if the document is encrypted.
     */
    public function isEncrypted(): bool
    {
        return $this->status === 'ENCRYPTED';
    }

    /**
     * Check if the document is archived.
     */
    public function isArchived(): bool
    {
        return $this->status === 'ARCHIVED';
    }

    /**
     * Check if the document has been destroyed.
     */
    public function isDestroyed(): bool
    {
        return $this->status === 'DESTROYED';
    }

    /**
     * Check if the document is immutable (sealed or beyond).
     */
    public function isImmutable(): bool
    {
        return in_array($this->status, ['SEALED', 'ENCRYPTED', 'ARCHIVED', 'DESTROYED'], true);
    }
}
