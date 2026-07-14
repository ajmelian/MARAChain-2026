<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\DeviceModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * DeviceController — register, list, show and revoke user devices.
 *
 * @since  1.1.1
 * @author Aythami
 */
class DeviceController extends BaseController
{
    use ResponseTrait;

    private DeviceModel $deviceModel;

    /**
     * Constructor.
     *
     * @since 1.1.1
     */
    public function __construct()
    {
        $this->deviceModel = model(DeviceModel::class);
    }

    /**
     * List devices belonging to the authenticated user.
     *
     * @return ResponseInterface JSON array of devices
     *
     * @since 1.1.1
     */
    public function index(): ResponseInterface
    {
        $userId  = session('user_id');
        $devices = $this->deviceModel->findByUserId($userId);

        return $this->respond($devices);
    }

    /**
     * Show a single device by UUID.
     *
     * @param string $id Device UUID
     *
     * @return ResponseInterface JSON device or 404
     *
     * @since 1.1.1
     */
    public function show(string $id): ResponseInterface
    {
        $device = $this->deviceModel->find($id);

        if ($device === null) {
            return $this->failNotFound('Device not found.');
        }

        return $this->respond($device);
    }

    /**
     * Register a new device.
     *
     * @return ResponseInterface 201 with device JSON or 400 on validation failure
     *
     * @since 1.1.1
     */
    public function register(): ResponseInterface
    {
        $input = $this->request->getJSON(true);

        if ($input === null) {
            return $this->failValidationErrors('Invalid JSON body.');
        }

        // Add the authenticated user as the device owner
        $input['userId'] = session('user_id');

        // Convert camelCase to snake_case for validation
        $snakeInput = $this->camelToSnake($input);

        if (! $this->validateGroup($snakeInput, 'device')) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        try {
            $device = $this->deviceModel->create($input);
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        }

        return $this->respondCreated($device);
    }

    /**
     * Revoke a device.
     *
     * @param string $id Device UUID
     *
     * @return ResponseInterface 204 or 404
     *
     * @since 1.1.1
     */
    public function revoke(string $id): ResponseInterface
    {
        $device = $this->deviceModel->find($id);

        if ($device === null) {
            return $this->failNotFound('Device not found.');
        }

        $this->deviceModel->revokeDevice($device);

        return $this->respondNoContent();
    }
}
