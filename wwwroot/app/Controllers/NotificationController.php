<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\NotificationModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * NotificationController — REST API for notification records.
 *
 * Notifications are produced by the system for various events
 * (transfers, auth, device changes) and sent to recipients.
 * This controller provides read access scoped to the current user.
 *
 * @package App\Controllers
 * @author  Aythami
 * @since   1.1.1
 */
class NotificationController extends BaseController
{
    use ResponseTrait;

    private NotificationModel $notificationModel;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->notificationModel = model(NotificationModel::class);
    }

    /**
     * List notifications for the authenticated user, optionally
     * filtered by notification_type and/or status query parameters.
     *
     * @return ResponseInterface JSON array of notification entities
     *
     * @since 1.1.1
     */
    public function index(): ResponseInterface
    {
        $userId = session('user_id');

        $query = $this->notificationModel
            ->where('recipient_user_id', $userId);

        $notificationType = $this->request->getVar('notification_type');
        if ($notificationType !== null && $notificationType !== '') {
            $query = $query->where('notification_type', $notificationType);
        }

        $status = $this->request->getVar('status');
        if ($status !== null && $status !== '') {
            $query = $query->where('status', $status);
        }

        $notifications = $query->findAll();

        return $this->respond($notifications);
    }

    /**
     * Fetch a single notification by its UUID.
     *
     * @param string $id Notification UUID
     *
     * @return ResponseInterface Notification JSON or 404
     *
     * @since 1.1.1
     */
    public function show(string $id): ResponseInterface
    {
        $notification = $this->notificationModel->find($id);

        if ($notification === null) {
            return $this->failNotFound('Notification not found.');
        }

        return $this->respond($notification);
    }
}
