<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * IpfsReconcile — Verifica que todos los CIDs en MySQL están pineados en IPFS.
 *
 * Escanea documentos con ipfs_cid, verifica el pin en IPFS,
 * y re-pinea si falta. Reporta discrepancias.
 *
 * Diseñado para ejecutarse diariamente via cron o systemd timer.
 *
 * @package App\Commands
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.8.0
 */
class IpfsReconcile extends BaseCommand
{
    protected $group       = 'IPFS';
    protected $name        = 'ipfs:reconcile';
    protected $description = 'Verifica que todos los CIDs en MySQL estén pineados en IPFS.';

    private string $ipfsApiUrl = 'http://127.0.0.1:5001/api/v0/';

    public function run(array $params): void
    {
        CLI::write('IPFS Reconciliation — ' . date('Y-m-d H:i:s'), 'yellow');
        CLI::newLine();

        // ── Health check first ──────────────────────────────────────
        $health = $this->checkIpfsHealth();
        if (! $health['connected']) {
            CLI::error('IPFS node unreachable: ' . $health['error']);

            return;
        }

        CLI::write("Peer ID: {$health['peerId']}", 'green');
        CLI::newLine();

        // ── Scan documents with ipfs_cid ────────────────────────────
        $db     = db_connect();
        $rows   = $db->table('documents')
            ->select('id, ipfs_cid, title')
            ->where('ipfs_cid IS NOT NULL')
            ->get()
            ->getResultArray();

        if ($rows === []) {
            CLI::write('No documents with ipfs_cid to reconcile.', 'green');

            return;
        }

        CLI::write("Documents to check: " . count($rows));
        CLI::newLine();

        $ok    = 0;
        $fixed = 0;
        $error = 0;

        foreach ($rows as $row) {
            $cid = $row['ipfs_cid'];
            CLI::write("  {$cid} — {$row['title']}... ", 'yellow');

            $pinned = $this->isPinned($cid);

            if ($pinned === true) {
                CLI::write("OK", 'green');
                $ok++;
            } elseif ($pinned === false) {
                CLI::write("NOT PINNED → re-pinning... ", 'red');
                $repinOk = $this->pinToIpfs($cid);
                if ($repinOk) {
                    CLI::write("PINNED", 'green');
                    $fixed++;
                } else {
                    CLI::write("FAILED", 'red');
                    $error++;
                }
            } else {
                CLI::write("ERROR (no response)", 'red');
                $error++;
            }
        }

        CLI::newLine();
        CLI::write("Done. OK: {$ok}, Re-pinned: {$fixed}, Errors: {$error}",
            $error === 0 ? 'green' : 'yellow');
    }

    private function isPinned(string $cid): ?bool
    {
        try {
            $ch = curl_init($this->ipfsApiUrl . 'pin/ls?arg=' . urlencode($cid));
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                return null;
            }

            $data = json_decode($response, true);

            return isset($data['Keys'][$cid]);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function pinToIpfs(string $cid): bool
    {
        try {
            $ch = curl_init($this->ipfsApiUrl . 'pin/add?arg=' . urlencode($cid));
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 15,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $httpCode === 200 && $response !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function checkIpfsHealth(): array
    {
        try {
            $ch = curl_init($this->ipfsApiUrl . 'id');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 5,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || $response === false) {
                return ['connected' => false, 'error' => 'IPFS API unreachable on ' . $this->ipfsApiUrl];
            }

            $data = json_decode($response, true);

            return [
                'connected' => true,
                'peerId'    => $data['ID'] ?? 'unknown',
            ];
        } catch (\Throwable $e) {
            return ['connected' => false, 'error' => $e->getMessage()];
        }
    }
}
