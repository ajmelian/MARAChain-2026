<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\EvidenceModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * EvidenceController — REST API for evidence records.
 *
 * Evidence is read-only (append-only event log). Only index and show
 * operations are exposed. Create/update/delete are handled internally
 * by domain services.
 *
 * @package App\Controllers
 * @author  Aythami
 * @since   1.1.1
 */
class EvidenceController extends BaseController
{
    use ResponseTrait;

    private EvidenceModel $evidenceModel;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->evidenceModel = model(EvidenceModel::class);
    }

    /**
     * List all evidence records, optionally filtered by event_type.
     *
     * @return ResponseInterface JSON array of evidence entities
     *
     * @since 1.1.1
     */
    public function index(): ResponseInterface
    {
        $eventType = $this->request->getVar('event_type');

        if ($eventType !== null && $eventType !== '') {
            $data = $this->evidenceModel->findByEventType($eventType);
        } else {
            $data = $this->evidenceModel->findAll();
        }

        return $this->respond($data);
    }

    /**
     * Fetch a single evidence record by its UUID.
     *
     * @param string $id Evidence UUID
     *
     * @return ResponseInterface Evidence JSON or 404
     *
     * @since 1.1.1
     */
    public function show(string $id): ResponseInterface
    {
        $evidence = $this->evidenceModel->find($id);

        if ($evidence === null) {
            return $this->failNotFound('Evidence record not found.');
        }

        return $this->respond($evidence);
    }
}
