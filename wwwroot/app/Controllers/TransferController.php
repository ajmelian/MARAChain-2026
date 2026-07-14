<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\DocumentTransferModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * TransferController — document transfers (inbox/outbox).
 *
 * @since  1.1.1
 * @author Aythami
 */
class TransferController extends BaseController
{
    use ResponseTrait;

    private DocumentTransferModel $transferModel;

    /**
     * Constructor.
     *
     * @since 1.1.1
     */
    public function __construct()
    {
        $this->transferModel = model(DocumentTransferModel::class);
    }

    /**
     * List all transfers.
     *
     * @return ResponseInterface JSON array of transfers
     *
     * @since 1.1.1
     */
    public function index(): ResponseInterface
    {
        $transfers = $this->transferModel->findAll();

        return $this->respond($transfers);
    }

    /**
     * List transfers sent by the authenticated user (outbox).
     *
     * @return ResponseInterface JSON array of sent transfers
     *
     * @since 1.1.1
     */
    public function outbox(): ResponseInterface
    {
        $userId    = session('user_id');
        $transfers = $this->transferModel->findBySenderId($userId);

        return $this->respond($transfers);
    }

    /**
     * List transfers received by the authenticated user (inbox).
     *
     * @return ResponseInterface JSON array of received transfers
     *
     * @since 1.1.1
     */
    public function inbox(): ResponseInterface
    {
        $userId    = session('user_id');
        $transfers = $this->transferModel->findByRecipientId($userId);

        return $this->respond($transfers);
    }

    /**
     * Create a new document transfer.
     *
     * @return ResponseInterface 201 with transfer JSON or 400 on validation failure
     *
     * @since 1.1.1
     */
    public function create(): ResponseInterface
    {
        $input = $this->request->getJSON(true);

        if ($input === null) {
            return $this->failValidationErrors('Invalid JSON body.');
        }

        // Attach the authenticated user as the sender
        $input['senderId'] = session('user_id');

        // Let the model handle validation internally
        try {
            $transfer = $this->transferModel->create($input);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage(), 400);
        }

        return $this->respondCreated($transfer);
    }

    /**
     * Show a single transfer by UUID.
     *
     * @param string $id Transfer UUID
     *
     * @return ResponseInterface JSON transfer or 404
     *
     * @since 1.1.1
     */
    public function show(string $id): ResponseInterface
    {
        $transfer = $this->transferModel->find($id);

        if ($transfer === null) {
            return $this->failNotFound('Transfer not found.');
        }

        return $this->respond($transfer);
    }

    /**
     * Revoke a transfer.
     *
     * @param string $id Transfer UUID
     *
     * @return ResponseInterface 200 with updated transfer JSON or 404
     *
     * @since 1.1.1
     */
    public function revoke(string $id): ResponseInterface
    {
        $transfer = $this->transferModel->find($id);

        if ($transfer === null) {
            return $this->failNotFound('Transfer not found.');
        }

        $transfer = $this->transferModel->revokeTransfer($transfer);

        return $this->respond($transfer);
    }
}
