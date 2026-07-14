<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ContactModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * ContactController — REST API CRUD for contacts (address book).
 *
 * Supports full CRUD for physical persons and legal entities.
 * Each contact is owned by the authenticated user from the session.
 *
 * @package App\Controllers
 * @author  Aythami
 * @since   1.1.1
 */
class ContactController extends BaseController
{
    use ResponseTrait;

    private ContactModel $contactModel;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->contactModel = model(ContactModel::class);
    }

    /**
     * List contacts for the authenticated user, optionally filtered
     * by identity_status query parameter.
     *
     * @return ResponseInterface JSON array of contact entities
     *
     * @since 1.1.1
     */
    public function index(): ResponseInterface
    {
        $ownerId        = session('user_id');
        $identityStatus = $this->request->getVar('identity_status');

        if ($identityStatus !== null && $identityStatus !== '') {
            $contacts = $this->contactModel
                ->where('owner_id', $ownerId)
                ->where('identity_status', $identityStatus)
                ->findAll();
        } else {
            $contacts = $this->contactModel->findByOwnerId($ownerId);
        }

        return $this->respond($contacts);
    }

    /**
     * Fetch a single contact by its UUID.
     *
     * @param string $id Contact UUID
     *
     * @return ResponseInterface Contact JSON or 404
     *
     * @since 1.1.1
     */
    public function show(string $id): ResponseInterface
    {
        $contact = $this->contactModel->find($id);

        if ($contact === null) {
            return $this->failNotFound('Contact not found.');
        }

        return $this->respond($contact);
    }

    /**
     * Create a new contact.
     *
     * Validates input against contact rules, injects owner_id from
     * session, and delegates to ContactModel::createContact().
     *
     * @return ResponseInterface Created contact JSON or 400 on validation failure
     *
     * @since 1.1.1
     */
    public function create(): ResponseInterface
    {
        $input = $this->request->getJSON(true);

        if ($input === null) {
            return $this->failValidationErrors('Invalid JSON body.');
        }

        // Attach authenticated user as owner
        $input['ownerId'] = session('user_id');

        try {
            $contact = $this->contactModel->createContact($input);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage(), 409);
        }

        return $this->respondCreated($contact);
    }

    /**
     * Update an existing contact.
     *
     * Finds the contact first (404 if not found), maps camelCase
     * input to snake_case column names, and updates.
     *
     * @param string $id Contact UUID
     *
     * @return ResponseInterface Updated contact JSON or 404
     *
     * @since 1.1.1
     */
    public function update(string $id): ResponseInterface
    {
        $contact = $this->contactModel->find($id);

        if ($contact === null) {
            return $this->failNotFound('Contact not found.');
        }

        $data = $this->request->getJSON(true) ?? $this->request->getRawInput() ?? [];

        if (empty($data)) {
            return $this->failValidationErrors('No data provided.');
        }

        // Let the model handle validation internally
        try {
            $this->contactModel->update($id, $data);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage(), 409);
        }

        $updated = $this->contactModel->find($id);

        return $this->respond($updated);
    }

    /**
     * Delete a contact (hard delete).
     *
     * @param string $id Contact UUID
     *
     * @return ResponseInterface 204 No Content or 404
     *
     * @since 1.1.1
     */
    public function delete(string $id): ResponseInterface
    {
        $contact = $this->contactModel->find($id);

        if ($contact === null) {
            return $this->failNotFound('Contact not found.');
        }

        $this->contactModel->delete($id);

        return $this->respondNoContent();
    }
}

