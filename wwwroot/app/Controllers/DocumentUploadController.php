<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\StorageService;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * DocumentUploadController — handles encrypted document upload.
 *
 * Receives a marachain-envelope (JSON) and the encrypted file
 * (multipart), validates the envelope, stores the ciphertext,
 * and creates the Document record.
 *
 * @package App\Controllers
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.4.0
 */
class DocumentUploadController extends BaseController
{
    use ResponseTrait;

    private StorageService $storage;

    public function __construct()
    {
        $this->storage = new StorageService();
    }

    /**
     * POST /documents/upload — Upload an encrypted document.
     *
     * Expects multipart form data:
     *   - envelope: JSON string (marachain-envelope v1)
     *   - file: encrypted binary file (multipart)
     *   - metadata: JSON string (title, description, mimeType, fileSize, fileHashSha256)
     *
     * @return ResponseInterface JSON with document ID or validation errors
     *
     * @since 1.4.0
     */
    public function upload(): ResponseInterface
    {
        $envelopeJson = $this->request->getPost('envelope');
        $metadataJson = $this->request->getPost('metadata');

        if (empty($envelopeJson) || empty($metadataJson)) {
            return $this->failValidationErrors('Envelope and metadata are required.');
        }

        $envelope = json_decode($envelopeJson, true);
        $metadata = json_decode($metadataJson, true);

        if (! is_array($envelope) || ! is_array($metadata)) {
            return $this->failValidationErrors('Invalid JSON in envelope or metadata.');
        }

        $file = $this->request->getFile('file');

        if ($file === null || ! $file->isValid()) {
            return $this->failValidationErrors('Encrypted file is required and must be valid.');
        }

        $ciphertext = base64_encode(file_get_contents($file->getTempName() ?: $file->getPathname()));

        // Inject owner from authenticated session
        $shieldUser = auth()->user();
        if ($shieldUser !== null) {
            $userModel = model(\App\Models\UserModel::class);
            $customUser = $userModel->findByShieldUserId($shieldUser->id ?? 0);
            $metadata['ownerId'] = $customUser?->id ?? '';
        }

        try {
            $doc = $this->storage->storeEncryptedDocument($envelope, $metadata, $ciphertext);

            return $this->respondCreated([
                'status' => 'success',
                'data'   => [
                    'documentId'     => $doc->id,
                    'title'          => $doc->title,
                    'fileHashSha256' => $doc->fileHashSha256,
                    'status'         => $doc->status,
                    'encryptedAt'    => (string) $doc->encryptedAt,
                    'ipfsCid'        => $doc->ipfsCid,
                ],
            ]);
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), 422);
        } catch (\Throwable $e) {
            log_message('critical', 'Document upload failed: ' . $e->getMessage());

            return $this->fail('Internal server error during upload.', 500);
        }
    }
}
