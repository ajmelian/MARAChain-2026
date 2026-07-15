<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DocumentModel;
use RuntimeException;

/**
 * StorageService — handles encrypted document persistence.
 *
 * MVP stores ciphertext directly in the database (documents table
 * ciphertext column). Phase 2 will use IPFS via a background worker.
 *
 * The backend NEVER receives the plaintext; only the ciphertext
 * after client-side AES-256-GCM encryption. The DEK is encapsulated
 * per recipient inside the envelope.
 *
 * @package App\Services
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.4.0
 */
class StorageService
{
    private EncryptionService $encryptionService;

    public function __construct()
    {
        $this->encryptionService = new EncryptionService();
    }

    /**
     * Store an encrypted document and create its metadata record.
     *
     * @param array  $envelope   marachain-envelope v1 (format, version, contentCipher, manifestHash, recipients)
     * @param array  $metadata   Document metadata (title, description, mimeType, fileSize, fileHashSha256, ownerId)
     * @param string $ciphertext Base64-encoded AES-256-GCM ciphertext
     *
     * @return \App\Entities\Document The created document entity
     *
     * @throws RuntimeException When envelope validation fails or storage fails
     *
     * @since 1.4.0
     */
    public function storeEncryptedDocument(array $envelope, array $metadata, string $ciphertext): \App\Entities\Document
    {
        // Validate envelope structure
        if (! $this->encryptionService->validateEnvelope($envelope)) {
            throw new RuntimeException('Invalid marachain-envelope format.');
        }

        // Verify manifest hash matches the file hash (SHA-256 of original document)
        $manifestHash   = $envelope['manifestHash'] ?? '';
        $fileHashSha256 = $metadata['fileHashSha256'] ?? '';

        if ($manifestHash !== $fileHashSha256) {
            throw new \InvalidArgumentException(
                'Manifest hash mismatch — document integrity check failed. '
                . 'Expected: ' . substr($fileHashSha256, 0, 16)
                . '..., Got: ' . substr($manifestHash, 0, 16) . '...'
            );
        }

        $documentModel = model(DocumentModel::class);

        $doc = $documentModel->create([
            'ownerId'          => $metadata['ownerId'] ?? '',
            'title'            => $metadata['title'] ?? 'Sin titulo',
            'description'      => $metadata['description'] ?? null,
            'mimeType'         => $metadata['mimeType'] ?? 'application/pdf',
            'fileSize'         => (int) ($metadata['fileSize'] ?? 0),
            'fileHashSha256'   => $fileHashSha256,
            'manifestHash'     => $manifestHash,
            'manifestJson'     => json_encode($envelope, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'encryptionFormat' => 'marachain-envelope',
            'contentCipher'    => $envelope['contentCipher'] ?? 'AES-256-GCM',
            'encryptedAt'      => date('Y-m-d H:i:s'),
            'version'          => 1,
            'status'           => 'ENCRYPTED',
        ]);

        return $doc;
    }
}
