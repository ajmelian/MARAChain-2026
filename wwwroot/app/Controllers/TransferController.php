<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\DocumentTransferModel;
use App\Models\NotificationRequestedModel;
use App\Notifications\NotificationChannel;
use App\Services\EvidenceService;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * TransferController — document transfers (inbox/outbox).
 *
 * @since  1.1.1
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
class TransferController extends BaseController
{
    use ResponseTrait;

    private DocumentTransferModel $transferModel;

    private EvidenceService $evidenceService;

    private NotificationRequestedModel $notificationModel;

    /**
     * Constructor.
     *
     * @since 1.1.1
     */
    public function __construct()
    {
        $this->transferModel     = model(DocumentTransferModel::class);
        $this->evidenceService   = new EvidenceService();
        $this->notificationModel = model(NotificationRequestedModel::class);
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

        $input['senderId'] = session('user_id');

        // Transactional: transfer + notification + evidence are atomic
        $db = $this->transferModel->db;
        $db->transStart();

        try {
            $transfer = $this->transferModel->create($input);
        } catch (\Throwable $e) {
            $db->transRollback();

            return $this->fail($e->getMessage(), 400);
        }

        // Queue email notification atomically with the transfer
        $this->notificationModel->queue(
            NotificationChannel::EMAIL,
            $transfer->recipientId, // placeholder — should resolve recipient email
            'Nuevo documento recibido en MARAChain',
            "Ha recibido un documento. Acceda a MARAChain para consultarlo:\nhttps://marachain.local/inbox",
            $transfer->id,
            'high'
        );

        $this->evidenceService->documentSent(
            $transfer->id,
            $transfer->senderId,
            $transfer->recipientId,
            $transfer->documentId
        );

        $db->transComplete();

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

        // Record evidence
        $this->evidenceService->transferRevoked($transfer->id, $transfer->senderId);

        return $this->respond($transfer);
    }

    /**
     * Accept a transfer (recipient).
     *
     * @param string $id Transfer UUID
     *
     * @return ResponseInterface
     *
     * @since 1.4.0
     */
    public function accept(string $id): ResponseInterface
    {
        $transfer = $this->transferModel->freshEntity($id);

        if ($transfer === null) {
            return $this->failNotFound('Transfer not found.');
        }

        try {
            $transfer = $this->transferModel->transitionStatus($transfer, 'ACCEPTED');
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage(), 422);
        }

        $this->evidenceService->transferAccepted($transfer->id, $transfer->recipientId);

        return $this->respond($transfer);
    }

    /**
     * Reject a transfer (recipient).
     *
     * @param string $id Transfer UUID
     *
     * @return ResponseInterface
     *
     * @since 1.4.0
     */
    public function reject(string $id): ResponseInterface
    {
        $transfer = $this->transferModel->freshEntity($id);

        if ($transfer === null) {
            return $this->failNotFound('Transfer not found.');
        }

        $reason = $this->request->getJSON(true)['reason'] ?? '';

        try {
            $transfer = $this->transferModel->transitionStatus($transfer, 'REJECTED');
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage(), 422);
        }

        $this->evidenceService->transferRejected($transfer->id, $transfer->recipientId, $reason);

        return $this->respond($transfer);
    }
}
