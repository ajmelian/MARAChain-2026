<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\StorageService;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * DocumentAccessController — Acceso a documentos para destinatarios.
 *
 * CU-ACCESS-002: el destinatario solicita acceso, recibe ciphertext + sobre,
 * descifra en cliente con su DEK.
 *
 * @package App\Controllers
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.9.0
 */
class DocumentAccessController extends BaseController
{
    use ResponseTrait;

    private StorageService $storage;

    public function __construct()
    {
        $this->storage = new StorageService();
    }

    /**
     * GET /documents/{id}/access — Obtener ciphertext + envelope.
     *
     * @param string $id Document UUID
     *
     * @return ResponseInterface
     */
    public function access(string $id): ResponseInterface
    {
        $documentModel = model(\App\Models\DocumentModel::class);
        $document = $documentModel->find($id);

        if ($document === null) {
            return $this->failNotFound('Document not found.');
        }

        // Check authorization: user must be the recipient of a transfer for this document
        $shieldUser = auth()->user();
        if ($shieldUser === null) {
            return $this->failUnauthorized('Authentication required.');
        }

        $userModel = model(\App\Models\UserModel::class);
        $customUser = $userModel->findByShieldUserId($shieldUser->id ?? 0);

        if ($customUser === null) {
            return $this->failUnauthorized('User profile not found.');
        }

        $transferModel = model(\App\Models\DocumentTransferModel::class);
        $transfers = $transferModel->where('document_id', $id)
            ->where('recipient_id', $customUser->id)
            ->findAll();

        if ($transfers === []) {
            return $this->failForbidden('You do not have access to this document.');
        }

        $transfer = $transfers[0];

        if (! in_array($transfer->status, ['AVAILABLE', 'ACCESSED', 'DOWNLOADED'], true)) {
            return $this->failForbidden(
                "Document is not available (status: {$transfer->status})."
            );
        }

        // Mark as ACCESSED
        $transferModel->transitionStatus($transfer, 'ACCESSED');

        // Record evidence
        $evidence = new \App\Services\EvidenceService();
        $evidence->transferAccessed($transfer->id, $customUser->id);

        // Retrieve ciphertext (IPFS or MySQL)
        $ciphertext = null;

        if (! empty($document->ipfsCid)) {
            $ciphertext = $this->storage->retrieveFromIpfs($document->ipfsCid);
        }

        if ($ciphertext === null) {
            return $this->fail('Ciphertext not available for this document.', 404);
        }

        return $this->respond([
            'status'     => 'success',
            'document'   => [
                'id'               => $document->id,
                'title'            => $document->title,
                'mimeType'         => $document->mimeType,
                'fileHashSha256'   => $document->fileHashSha256,
            ],
            'envelope'   => json_decode($document->manifestJson ?? '{}', true),
            'ciphertext' => base64_encode($ciphertext),
        ]);
    }
}
