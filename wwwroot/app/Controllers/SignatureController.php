<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\SignatureRequestModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * SignatureController — request and retrieve electronic signatures.
 *
 * @since  1.1.1
 * @author Aythami
 */
class SignatureController extends BaseController
{
    use ResponseTrait;

    private SignatureRequestModel $signatureModel;

    /**
     * Constructor.
     *
     * @since 1.1.1
     */
    public function __construct()
    {
        $this->signatureModel = model(SignatureRequestModel::class);
    }

    /**
     * Request a new electronic signature.
     *
     * Creates a signature request linked to the authenticated user
     * and the specified document.
     *
     * @return ResponseInterface 201 with signature request JSON or 400
     *
     * @since 1.1.1
     */
    public function request(): ResponseInterface
    {
        $input = $this->request->getJSON(true);

        if ($input === null) {
            return $this->failValidationErrors('Invalid JSON body.');
        }

        // Attach authenticated user
        $input['userId'] = session('user_id');

        // Generate a unique nonce for replay prevention
        $input['nonce'] = bin2hex(random_bytes(32));

        // Let the model handle validation internally
        try {
            $signature = $this->signatureModel->createSignatureRequest($input);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage(), 400);
        }

        return $this->respondCreated($signature);
    }

    /**
     * Show a single signature request by UUID.
     *
     * @param string $id Signature request UUID
     *
     * @return ResponseInterface JSON signature request or 404
     *
     * @since 1.1.1
     */
    public function show(string $id): ResponseInterface
    {
        $signature = $this->signatureModel->find($id);

        if ($signature === null) {
            return $this->failNotFound('Signature request not found.');
        }

        return $this->respond($signature);
    }
}
