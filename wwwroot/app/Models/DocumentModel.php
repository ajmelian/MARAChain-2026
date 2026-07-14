<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\Document;
use CodeIgniter\Model;

/**
 * DocumentModel — persistence layer for Document entities.
 *
 * Manages the full document lifecycle: creation, retrieval by owner/status/hash,
 * status transitions (DRAFT → SEALED → ENCRYPTED → ARCHIVED → DESTROYED),
 * immutability enforcement for sealed documents, and versioning.
 *
 * @package App\Models
 * @author  Aythami
 * @since   1.1.1
 */
class DocumentModel extends Model
{
    /**
     * Valid status transitions map.
     *
     * @var array<string, string[]>
     */
    private const VALID_TRANSITIONS = [
        'DRAFT'     => ['SEALED'],
        'SEALED'    => ['ENCRYPTED'],
        'ENCRYPTED' => ['ARCHIVED'],
        'ARCHIVED'  => ['DESTROYED'],
        'DESTROYED' => [],
    ];

    protected $table            = 'documents';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = Document::class;
    protected $useSoftDeletes   = false;
    protected $useTimestamps    = true;
    protected $skipValidation   = true;

    protected $allowedFields = [
        'id',
        'owner_id',
        'title',
        'description',
        'mime_type',
        'file_size',
        'file_hash_sha256',
        'version',
        'status',
        'manifest_hash',
        'manifest_json',
        'cid',
        'encryption_format',
        'content_cipher',
        'sealed_at',
        'encrypted_at',
        'archived_at',
        'destroyed_at',
    ];

    /**
     * Create a new document.
     *
     * Requires a title, generates a UUID v4, sets version to 1
     * and default status to 'DRAFT'.
     *
     * @param array<string, mixed> $data Document data (camelCase keys)
     *
     * @return Document Persisted document entity
     *
     * @throws \RuntimeException When title is missing
     *
     * @since 1.1.1
     */
    public function create(array $data): Document
    {
        if (empty($data['title'] ?? '')) {
            throw new \RuntimeException('Title is required.');
        }

        $id = \App\Helpers\Uuid::v4();

        $row = [
            'id'               => $id,
            'owner_id'         => $data['ownerId'] ?? '',
            'title'            => $data['title'],
            'description'      => $data['description'] ?? null,
            'mime_type'        => $data['mimeType'] ?? 'application/pdf',
            'file_size'        => $data['fileSize'] ?? 0,
            'file_hash_sha256' => $data['fileHashSha256'] ?? '',
            'version'          => $data['version'] ?? 1,
            'status'           => $data['status'] ?? 'DRAFT',
        ];

        $this->insert($row);

        return $this->freshEntity($id);
    }

    /**
     * Find documents owned by a specific user.
     *
     * @param string $ownerId Owner user UUID
     *
     * @return Document[] List of documents
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
     * Find documents by status.
     *
     * @param string $status Document status (DRAFT|SEALED|ENCRYPTED|ARCHIVED|DESTROYED)
     *
     * @return Document[] List of matching documents
     *
     * @since 1.1.1
     */
    public function findByStatus(string $status): array
    {
        $rows = $this->db->table($this->table)
            ->where('status', $status)
            ->get()
            ->getResultArray();

        return $this->freshEntities($rows);
    }

    /**
     * Find a document by its original file SHA-256 hash.
     *
     * @param string $hash SHA-256 hash in hexadecimal (64 characters)
     *
     * @return Document|null Document entity or null if not found
     *
     * @since 1.1.1
     */
    public function findByFileHash(string $hash): ?Document
    {
        $row = $this->db->table($this->table)
            ->where('file_hash_sha256', $hash)
            ->get()
            ->getRowArray();

        return $row ? new Document($row) : null;
    }

    /**
     * Transition a document to a new status.
     *
     * Validates the transition against the state machine:
     * DRAFT → SEALED → ENCRYPTED → ARCHIVED → DESTROYED.
     * Sealed documents cannot be modified (transition back to DRAFT).
     * Sets the appropriate timestamp for the target status.
     *
     * @param Document $document Document entity
     * @param string   $status   Target status
     *
     * @return Document Updated document entity
     *
     * @throws \RuntimeException When the transition is invalid
     *
     * @since 1.1.1
     */
    public function updateStatus(Document $document, string $status): Document
    {
        $row = $this->db->table($this->table)
            ->where('id', $document->id)
            ->get()
            ->getRowArray();

        $currentStatus = $row['status'] ?? '';
        $allowed       = self::VALID_TRANSITIONS[$currentStatus] ?? [];

        if (! in_array($status, $allowed, true)) {
            throw new \RuntimeException(
                "Invalid document status transition from {$currentStatus} to {$status}."
            );
        }

        $updateData = [
            'status' => $status,
        ];

        $now = date('Y-m-d H:i:s');

        match ($status) {
            'SEALED'    => $updateData['sealed_at']    = $now,
            'ENCRYPTED' => $updateData['encrypted_at'] = $now,
            'ARCHIVED'  => $updateData['archived_at']  = $now,
            'DESTROYED' => $updateData['destroyed_at'] = $now,
            default     => null,
        };

        $this->update($document->id, $updateData);

        return $this->freshEntity($document->id);
    }

    /**
     * Seal a document (DRAFT → SEALED).
     *
     * @param Document $document Document entity
     *
     * @return Document Sealed document entity
     *
     * @throws \RuntimeException When document is not in DRAFT status
     *
     * @since 1.1.1
     */
    public function sealDocument(Document $document): Document
    {
        return $this->updateStatus($document, 'SEALED');
    }

    /**
     * Encrypt a sealed document (SEALED → ENCRYPTED).
     *
     * @param Document $document Document entity
     *
     * @return Document Encrypted document entity
     *
     * @throws \RuntimeException When document is not in SEALED status
     *
     * @since 1.1.1
     */
    public function encryptDocument(Document $document): Document
    {
        return $this->updateStatus($document, 'ENCRYPTED');
    }

    /**
     * Archive an encrypted document (ENCRYPTED → ARCHIVED).
     *
     * @param Document $document Document entity
     *
     * @return Document Archived document entity
     *
     * @throws \RuntimeException When document is not in ENCRYPTED status
     *
     * @since 1.1.1
     */
    public function archiveDocument(Document $document): Document
    {
        return $this->updateStatus($document, 'ARCHIVED');
    }

    /**
     * Destroy an archived document (ARCHIVED → DESTROYED).
     *
     * @param Document $document Document entity
     *
     * @return Document Destroyed document entity
     *
     * @throws \RuntimeException When document is not in ARCHIVED status
     *
     * @since 1.1.1
     */
    public function destroyDocument(Document $document): Document
    {
        return $this->updateStatus($document, 'DESTROYED');
    }

    /**
     * Create a new version of an existing document.
     *
     * Reads the current version from the database, increments it,
     * and creates a new draft document with the updated data.
     *
     * @param Document          $document Original document entity
     * @param array<string, mixed> $data     Updated fields (camelCase keys)
     *
     * @return Document New version entity
     *
     * @since 1.1.1
     */
    public function createNewVersion(Document $document, array $data): Document
    {
        $row = $this->db->table($this->table)
            ->where('id', $document->id)
            ->get()
            ->getRowArray();

        $currentVersion = (int) ($row['version'] ?? 0);

        $newData = [
            'ownerId'        => $document->ownerId,
            'title'          => $document->title,
            'description'    => $document->description,
            'mimeType'       => $document->mimeType,
            'fileSize'       => $data['fileSize'] ?? $document->fileSize,
            'fileHashSha256' => $data['fileHashSha256'] ?? $document->fileHashSha256,
            'version'        => $currentVersion + 1,
            'status'         => 'DRAFT',
        ];

        return $this->create($newData);
    }

    /**
     * Refresh entity from database, bypassing CI4 result cache.
     *
     * @param string $id Document UUID
     *
     * @return Document Fresh Document entity
     *
     * @since 1.1.1
     */
    private function freshEntity(string $id): Document
    {
        $row = $this->db->table($this->table)
            ->where('id', $id)
            ->get()
            ->getRowArray();

        return new Document($row);
    }

    /**
     * Build fresh entities from raw DB rows, bypassing CI4 entity cache.
     *
     * @param array<int, array<string, mixed>> $rows Raw database rows
     *
     * @return Document[]
     *
     * @since 1.1.1
     */
    private function freshEntities(array $rows): array
    {
        return array_map(static fn (array $row): Document => new Document($row), $rows);
    }

}
