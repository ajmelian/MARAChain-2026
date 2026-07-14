<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * HealthCheck controller for deployment smoke tests.
 *
 * Returns system status: database connectivity, migrations applied,
 * and ledger block count. Used by deploy scripts and monitoring.
 *
 * @package App\Controllers
 * @author  Aythami
 * @since   1.4.0
 */
class HealthController extends BaseController
{
    use ResponseTrait;

    /**
     * GET /health — System health check.
     *
     * Tests database connectivity and returns basic stats.
     * Returns 200 when healthy, 503 when degraded.
     *
     * @return ResponseInterface JSON health report
     *
     * @since 1.4.0
     */
    public function index(): ResponseInterface
    {
        $healthy = true;
        $checks  = [];

        // ── Database connectivity ──────────────────────────────────
        try {
            $db = db_connect();
            $db->query('SELECT 1');
            $checks['database'] = 'connected';
        } catch (\Throwable $e) {
            $healthy               = false;
            $checks['database']    = 'error';
            $checks['db_error']    = $e->getMessage();
        }

        // ── Migrations ─────────────────────────────────────────────
        if ($healthy) {
            try {
                $count = $db->table('migrations')->countAllResults();
                $checks['migrations'] = $count . ' applied';
            } catch (\Throwable $e) {
                $checks['migrations'] = 'unavailable';
            }
        }

        // ── Ledger status ──────────────────────────────────────────
        if ($healthy) {
            try {
                $blocks = $db->table('ledger_blocks')->countAllResults();
                $checks['ledger_blocks'] = $blocks;
            } catch (\Throwable $e) {
                $checks['ledger_blocks'] = 'unavailable';
            }
        }

        // ── Version ────────────────────────────────────────────────
        $checks['version'] = '1.4.0';
        $checks['php']     = PHP_VERSION;
        $checks['time']    = date('c');

        $statusCode = $healthy ? 200 : 503;

        return $this->respond([
            'status'  => $healthy ? 'healthy' : 'degraded',
            'checks'  => $checks,
        ], $statusCode);
    }
}
