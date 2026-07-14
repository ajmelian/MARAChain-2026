<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * UserController — CRUD + TOTP enablement for user identities.
 *
 * @since  1.1.1
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
class UserController extends BaseController
{
    use ResponseTrait;

    private UserModel $userModel;

    /**
     * Constructor.
     *
     * @since 1.1.1
     */
    public function __construct()
    {
        $this->userModel = model(UserModel::class);
    }

    /**
     * List all users.
     *
     * @return ResponseInterface JSON array of users
     *
     * @since 1.1.1
     */
    public function index(): ResponseInterface
    {
        $users = $this->userModel->findAll();

        return $this->respond($users);
    }

    /**
     * Show a single user by UUID.
     *
     * @param string $id User UUID
     *
     * @return ResponseInterface JSON user or 404
     *
     * @since 1.1.1
     */
    public function show(string $id): ResponseInterface
    {
        $user = $this->userModel->find($id);

        if ($user === null) {
            return $this->failNotFound('User not found.');
        }

        return $this->respond($user);
    }

    /**
     * Create a new user.
     *
     * Validates input via Config\Validation $user rules, then delegates
     * to UserModel::create().
     *
     * @return ResponseInterface 201 with user JSON or 400 on validation failure
     *
     * @since 1.1.1
     */
    public function create(): ResponseInterface
    {
        $input = $this->request->getJSON(true);

        if ($input === null) {
            return $this->failValidationErrors('Invalid JSON body.');
        }

        // Convert camelCase keys to snake_case for validation
        $snakeInput = $this->camelToSnake($input);

        if (! $this->validateGroup($snakeInput, 'user')) {
            return $this->failValidationErrors($this->validator->getErrors());
        }

        try {
            $user = $this->userModel->create($input);
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), 409);
        }

        return $this->respondCreated($user);
    }

    /**
     * Update an existing user.
     *
     * Accepts partial updates (PATCH semantics via PUT).
     *
     * @param string $id User UUID
     *
     * @return ResponseInterface 200 with updated user JSON or 404
     *
     * @since 1.1.1
     */
    public function update(string $id): ResponseInterface
    {
        $user = $this->userModel->find($id);

        if ($user === null) {
            return $this->failNotFound('User not found.');
        }

        $input = $this->request->getJSON(true);

        if ($input === null) {
            return $this->failValidationErrors('Invalid JSON body.');
        }

        // Convert camelCase input to snake_case for DB column names
        $updateData = $this->camelToSnake($input);

        $this->userModel->update($id, $updateData);

        // Re-fetch the updated entity
        $user = $this->userModel->find($id);

        return $this->respond($user);
    }

    /**
     * Soft-delete (block) a user.
     *
     * Sets status to 'blocked'.
     *
     * @param string $id User UUID
     *
     * @return ResponseInterface 204 or 404
     *
     * @since 1.1.1
     */
    public function delete(string $id): ResponseInterface
    {
        $user = $this->userModel->find($id);

        if ($user === null) {
            return $this->failNotFound('User not found.');
        }

        $this->userModel->update($id, ['status' => 'blocked']);

        return $this->respondNoContent();
    }

    /**
     * Enable TOTP for a user.
     *
     * @param string $id User UUID
     *
     * @return ResponseInterface 200 with updated user JSON or 404
     *
     * @since 1.1.1
     */
    public function enableTotp(string $id): ResponseInterface
    {
        $user = $this->userModel->find($id);

        if ($user === null) {
            return $this->failNotFound('User not found.');
        }

        $input = $this->request->getJSON(true);
        $secret = $input['totpSecret'] ?? bin2hex(random_bytes(16));

        $user = $this->userModel->enableTotp($user, $secret);

        return $this->respond($user);
    }
}
