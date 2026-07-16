<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\DocumentModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * DocumentController — CRUD + seal for user documents.
 *
 * @since  1.1.1
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
class DocumentController extends BaseController
{
    use ResponseTrait;

    private DocumentModel $documentModel;

    /**
     * Constructor.
     *
     * @since 1.1.1
     */
    public function __construct()
    {
        $this->documentModel = model(DocumentModel::class);
    }

    /**
     * List documents owned by the authenticated user.
     *
     * @return ResponseInterface JSON array of documents
     *
     * @since 1.1.1
     */
    public function index(): ResponseInterface
    {
        $userId    = session('user_id');
        $documents = $this->documentModel->findByOwnerId($userId);

        return $this->respond($documents);
    }

    /**
     * Show a single document by UUID.
     *
     * @param string $id Document UUID
     *
     * @return ResponseInterface JSON document or 404
     *
     * @since 1.1.1
     */
    public function show(string $id): ResponseInterface
    {
        $document = $this->documentModel->find($id);

        if ($document === null) {
            return $this->failNotFound('Document not found.');
        }

        return $this->respond($document);
    }

    /**
     * Create a new document.
     *
     * @return ResponseInterface 201 with document JSON or 400 on validation failure
     *
     * @since 1.1.1
     */
    public function create(): ResponseInterface
    {
        $input = $this->request->getJSON(true);

        if ($input === null) {
            return $this->failValidationErrors('Invalid JSON body.');
        }

        // Attach the authenticated user as the document owner
        $input['ownerId'] = session('user_id');

        // Convert camelCase to snake_case for validation
        $snakeInput = $this->camelToSnake($input);

        if (! $this->validateGroup($snakeInput, 'document')) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        try {
            $document = $this->documentModel->create($input);
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        }

        return $this->respondCreated($document);
    }

    /**
     * Seal a document (transition from DRAFT to SEALED).
     *
     * @param string $id Document UUID
     *
     * @return ResponseInterface 200 with sealed document JSON or 404
     *
     * @since 1.1.1
     */
    public function seal(string $id): ResponseInterface
    {
        $document = $this->documentModel->find($id);

        if ($document === null) {
            return $this->failNotFound('Document not found.');
        }

        $input = $this->request->getJSON(true);

        if ($input !== null && isset($input['manifestHash'])) {
            $this->documentModel->update($id, [
                'manifest_hash' => $input['manifestHash'],
            ]);
        }

        try {
            $document = $this->documentModel->sealDocument($document);
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), 409);
        }

        return $this->respond($document);
    }

    /**
     * Destroy a document (transition to DESTROYED).
     *
     * @param string $id Document UUID
     *
     * @return ResponseInterface 204 or 404
     *
     * @since 1.1.1
     */
    public function delete(string $id): ResponseInterface
    {
        $document = $this->documentModel->find($id);

        if ($document === null) {
            return $this->failNotFound('Document not found.');
        }

        $this->documentModel->update($id, [
            'status'       => 'DESTROYED',
            'destroyed_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->respondNoContent();
    }

    /**
     * Destroy a document (CU-DELETE-001).
     *
     * Removes ipfs_cid to orphan the ciphertext in IPFS.
     * Preserves blockchain_tx_id for audit trail.
     *
     * @param string $id Document UUID
     *
     * @return ResponseInterface
     *
     * @since 1.8.0
     */
    public function destroy(string $id): ResponseInterface
    {
        $document = $this->documentModel->find($id);

        if ($document === null) {
            return $this->failNotFound('Document not found.');
        }

        $storage = new \App\Services\StorageService();
        $ok      = $storage->destroyDocument($id);

        if (! $ok) {
            return $this->fail('Failed to destroy document.', 500);
        }

        return $this->respondNoContent();
    }
}
