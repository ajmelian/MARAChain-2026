<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Uuid;

/**
 * QueueService — Cola de trabajos asincronos.
 *
 * Permite encolar y procesar operaciones asincronas:
 *   - ipfs_upload: Subir ciphertext a IPFS
 *   - send_email: Enviar notificacion email
 *   - seal_ledger: Sellar bloque del ledger
 *   - reconcile_ipfs: Verificar pines IPFS
 *   - expire_transfers: Marcar transfers expiradas
 *
 * @package App\Services
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.9.0
 */
class QueueService
{
    private string $table = 'jobs';

    /**
     * Push a new job onto the queue.
     *
     * @param string $queue   Queue name (default, ipfs, email, ledger)
     * @param string $type    Job type identifier
     * @param array  $payload Job payload data
     * @param int    $delay   Delay in seconds before job is available
     *
     * @return string Job ID
     */
    public function push(string $queue, string $type, array $payload, int $delay = 0): string
    {
        $db = db_connect();
        $id = Uuid::v4();
        $now = date('Y-m-d H:i:s');

        $db->table($this->table)->insert([
            'id'           => $id,
            'queue'        => $queue,
            'type'         => $type,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_SLASHES),
            'status'       => 'queued',
            'available_at' => $delay > 0
                ? date('Y-m-d H:i:s', time() + $delay)
                : $now,
            'created_at'   => $now,
            'updated_at'   => $now,
        ]);

        return $id;
    }

    /**
     * Get the next available job from the queue.
     *
     * @param string $queue Queue name
     *
     * @return array|null Job row or null
     */
    public function pop(string $queue = 'default'): ?array
    {
        $db = db_connect();
        $now = date('Y-m-d H:i:s');

        $row = $db->table($this->table)
            ->where('queue', $queue)
            ->where('status', 'queued')
            ->where('available_at <=', $now)
            ->orderBy('created_at', 'ASC')
            ->limit(1)
            ->get()
            ->getRowArray();

        if ($row === null || $row === false) {
            return null;
        }

        // Reserve the job atomically
        $db->table($this->table)
            ->where('id', $row['id'])
            ->where('status', 'queued')
            ->update([
                'status'      => 'processing',
                'reserved_at' => $now,
                'updated_at'  => $now,
            ]);

        $row['payload'] = json_decode($row['payload_json'], true);

        return $row;
    }

    /**
     * Mark a job as completed.
     */
    public function completed(string $jobId): void
    {
        $db = db_connect();
        $db->table($this->table)
            ->where('id', $jobId)
            ->update([
                'status'       => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
                'updated_at'   => date('Y-m-d H:i:s'),
            ]);
    }

    /**
     * Mark a job as failed.
     */
    public function failed(string $jobId, string $errorMessage): void
    {
        $db = db_connect();

        $job = $db->table($this->table)->where('id', $jobId)->get()->getRowArray();
        $attempts = ($job['attempts'] ?? 0) + 1;
        $maxAttempts = $job['max_attempts'] ?? 3;

        $newStatus = $attempts >= $maxAttempts ? 'failed' : 'queued';
        $now = date('Y-m-d H:i:s');

        $db->table($this->table)
            ->where('id', $jobId)
            ->update([
                'status'        => $newStatus,
                'attempts'      => $attempts,
                'error_message' => $errorMessage,
                'available_at'  => $newStatus === 'queued'
                    ? date('Y-m-d H:i:s', time() + 60 * $attempts) // backoff
                    : $now,
                'failed_at'     => $newStatus === 'failed' ? $now : null,
                'updated_at'    => $now,
            ]);
    }
}
