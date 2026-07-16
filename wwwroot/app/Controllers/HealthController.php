<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * HealthCheck controller for deployment smoke tests.
 *
 * Returns system status: database, IPFS, SMTP, colas, disco, ledger.
 * Used by deploy scripts, monitoring (Prometheus/Grafana) and alerting.
 *
 * @package App\Controllers
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.7.0
 */
class HealthController extends BaseController
{
    use ResponseTrait;

    /**
     * GET /health — System health check.
     *
     * Tests all components and returns 200 when healthy, 503 when degraded.
     *
     * @return ResponseInterface JSON health report
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

        // ── Pending notifications ──────────────────────────────────
        if ($healthy) {
            try {
                $pending = $db->table('notification_requested')
                    ->whereIn('status', ['QUEUED', 'FAILED', 'RETRYING'])
                    ->countAllResults();
                $checks['pending_notifications'] = $pending;
            } catch (\Throwable $e) {
                $checks['pending_notifications'] = null;
            }
        }

        // ── SMTP connectivity ──────────────────────────────────────
        $smtpHost = env('email.SMTPHost');
        $smtpPort = env('email.SMTPPort') ?? '465';
        if (! empty($smtpHost)) {
            try {
                $smtp = @fsockopen($smtpHost, (int) $smtpPort, $eNo, $eStr, 3);
                $checks['smtp'] = $smtp ? 'connected' : 'unreachable';
                if ($smtp) {
                    fclose($smtp);
                }
            } catch (\Throwable $e) {
                $checks['smtp'] = 'error';
            }
        } else {
            $checks['smtp'] = 'not_configured';
        }

        // ── IPFS connectivity ──────────────────────────────────────
        $ipfsApiUrl = 'http://127.0.0.1:5001/api/v0/id';
        try {
            $ch = curl_init($ipfsApiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 3,
                CURLOPT_POST => true,
            ]);
            $resp = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $resp !== false) {
                $ipfsData = json_decode($resp, true);
                $checks['ipfs'] = 'connected';
                $checks['ipfs_peer_id'] = substr($ipfsData['ID'] ?? '', 0, 16) . '…';
            } else {
                $checks['ipfs'] = 'unreachable';
            }
        } catch (\Throwable $e) {
            $checks['ipfs'] = 'not_configured';
        }

        // ── Disk space ────────────────────────────────────────────
        $writablePath = WRITEPATH;
        if (is_dir($writablePath)) {
            $freeSpace = disk_free_space($writablePath);
            $totalSpace = disk_total_space($writablePath);
            $pct = $totalSpace > 0
                ? round(($totalSpace - $freeSpace) / $totalSpace * 100, 1)
                : 0;
            $checks['disk'] = [
                'usage_pct' => $pct,
                'free_gb'   => round($freeSpace / 1073741824, 1),
                'total_gb'  => round($totalSpace / 1073741824, 1),
            ];
            if ($pct >= 90) {
                $healthy = false;
            }
        } else {
            $checks['disk'] = 'unavailable';
        }

        // ── Version ────────────────────────────────────────────────
        $checks['version'] = '1.7.0';
        $checks['php']     = PHP_VERSION;
        $checks['time']    = date('c');

        $statusCode = $healthy ? 200 : 503;

        return $this->respond([
            'status'  => $healthy ? 'healthy' : 'degraded',
            'checks'  => $checks,
        ], $statusCode);
    }
}
