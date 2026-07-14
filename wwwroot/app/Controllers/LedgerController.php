<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\LedgerBlockModel;
use App\Services\LedgerService;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * LedgerController — REST API for ledger blocks.
 *
 * Provides read access to the immutable blockchain-backed ledger
 * and a verify endpoint for chain integrity checks.
 *
 * @package App\Controllers
 * @author  Aythami
 * @since   1.1.1
 */
class LedgerController extends BaseController
{
    use ResponseTrait;

    private LedgerBlockModel $ledgerBlockModel;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->ledgerBlockModel = model(LedgerBlockModel::class);
    }

    /**
     * List all ledger blocks.
     *
     * @return ResponseInterface JSON array of ledger blocks
     *
     * @since 1.1.1
     */
    public function index(): ResponseInterface
    {
        $blocks = $this->ledgerBlockModel->findAll();

        return $this->respond($blocks);
    }

    /**
     * Fetch a single ledger block by its UUID.
     *
     * @param string $id Ledger block UUID
     *
     * @return ResponseInterface Ledger block JSON or 404
     *
     * @since 1.1.1
     */
    public function show(string $id): ResponseInterface
    {
        $block = $this->ledgerBlockModel->find($id);

        if ($block === null) {
            return $this->failNotFound('Ledger block not found.');
        }

        return $this->respond($block);
    }

    /**
     * Verify the integrity of the full ledger chain.
     *
     * Recomputes block hashes, checks hash chain continuity,
     * and verifies Merkle roots for all blocks.
     *
     * @return ResponseInterface JSON with verification report
     *
     * @since 1.1.1
     */
    public function verify(): ResponseInterface
    {
        $service = new LedgerService();
        $report  = $service->verifyChain();

        $statusCode = $report['valid'] ? 200 : 422;

        return $this->respond($report, $statusCode);
    }
}
