<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * TimestampController — Endpoints de sellado de tiempo.
 *
 * GET  /timestamps/{hash}/receipt — Obtiene recibo verificable para un hash
 * POST /timestamps            — Crea un timestamp sellando el hash
 *
 * @package App\Controllers
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.9.0
 */
class TimestampController extends BaseController
{
    use ResponseTrait;

    /**
     * GET /timestamps/{hash}/receipt
     *
     * Recupera el recibo de timestamp para un hash,
     * buscando en evidencias y generando el Merkle proof.
     *
     * @param string $hash SHA-256 hash (64 hex chars)
     *
     * @return ResponseInterface JSON con el recibo o 404
     */
    public function receipt(string $hash): ResponseInterface
    {
        if (strlen($hash) !== 64 || ! ctype_xdigit($hash)) {
            return $this->failValidationErrors('Invalid SHA-256 hash format.');
        }

        $service = new \App\Services\TimestampService();
        $event   = model(\App\Models\EvidenceModel::class)
            ->where('payload_hash', $hash)
            ->first();

        if ($event === null) {
            return $this->failNotFound('No timestamp found for this hash.');
        }

        $receipt = $service->getReceipt($event->eventId);

        if ($receipt === null) {
            return $this->failNotFound('Receipt not available yet — event not sealed in ledger.');
        }

        return $this->respond([
            'status'  => 'success',
            'receipt' => $receipt,
        ]);
    }
}
