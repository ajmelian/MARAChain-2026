<?php

declare(strict_types=1);

namespace App\Commands;

use App\Services\QueueService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * QueueWork — Procesa trabajos de la cola de forma continua.
 *
 * Lee jobs de la tabla `jobs`, los procesa segun su tipo,
 * y actualiza el estado. Corre como worker continuo (systemd).
 *
 * @package App\Commands
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.9.0
 */
class QueueWork extends BaseCommand
{
    protected $group       = 'Queue';
    protected $name        = 'queue:work';
    protected $description = 'Process jobs from the queue continuously.';

    public function run(array $params): void
    {
        $queue = $params[0] ?? 'default';
        $sleep = max(1, (int) ($params[1] ?? 5));

        CLI::write("Queue worker started [{$queue}]. Polling every {$sleep}s.", 'green');
        CLI::newLine();

        $service   = new QueueService();
        $processed = 0;

        while (true) {
            $job = $service->pop($queue);

            if ($job === null) {
                CLI::write("Waiting... ({$processed} processed)", 'yellow');
                sleep($sleep);
                continue;
            }

            CLI::write("[{$job['type']}] Processing job {$job['id']}...", 'yellow');

            try {
                $this->process($job, $service);
                $service->completed($job['id']);
                $processed++;
                CLI::write("  Completed ✓", 'green');
            } catch (\Throwable $e) {
                $service->failed($job['id'], $e->getMessage());
                CLI::write("  Failed ✗ ({$e->getMessage()})", 'red');
            }
        }
    }

    private function process(array $job, QueueService $service): void
    {
        $type    = $job['type'];
        $payload = $job['payload'];

        switch ($type) {
            case 'send_email':
                $this->processSendEmail($payload);
                break;

            case 'ipfs_upload':
                $this->processIpfsUpload($payload);
                break;

            case 'seal_ledger':
                $this->processSealLedger($payload);
                break;

            case 'expire_transfers':
                $this->processExpireTransfers($payload);
                break;

            default:
                throw new \RuntimeException("Unknown job type: {$type}");
        }
    }

    private function processSendEmail(array $payload): void
    {
        $recipient = new \App\Notifications\RecipientAddress(
            \App\Notifications\NotificationChannel::EMAIL,
            $payload['email']
        );
        $message = new \App\Notifications\NotificationMessage(
            $payload['subject'],
            $payload['body']
        );

        $provider = new \App\Notifications\Providers\EmailNotificationProvider();
        $result   = $provider->send($recipient, $message);

        if (! $result->success) {
            throw new \RuntimeException($result->errorMessage ?? 'Email send failed');
        }
    }

    private function processIpfsUpload(array $payload): void
    {
        $data = base64_decode($payload['data'] ?? '');
        if ($data === false) {
            throw new \RuntimeException('Invalid base64 data');
        }

        $storage = new \App\Services\StorageService();
        $result  = $storage->uploadToIpfs($data);

        if ($result === null) {
            throw new \RuntimeException('IPFS upload failed');
        }

        // Update document with ipfs_cid
        $docId = $payload['documentId'] ?? null;
        if ($docId !== null) {
            $model = model(\App\Models\DocumentModel::class);
            $model->update($docId, ['ipfs_cid' => $result['cid']]);
        }
    }

    private function processSealLedger(array $payload): void
    {
        $ledger = new \App\Services\LedgerService();
        $result = $ledger->sealBlock();

        if ($result === null && ($payload['require'] ?? false)) {
            throw new \RuntimeException('No evidence to seal');
        }
    }

    private function processExpireTransfers(array $payload): void
    {
        $db = db_connect();
        $expirable = ['PENDING_RECIPIENT', 'READY', 'SENDING', 'SENT',
                       'AVAILABLE', 'ACCESSED', 'DOWNLOADED'];

        $db->table('document_transfers')
            ->whereIn('status', $expirable)
            ->where('expires_at IS NOT NULL')
            ->where('expires_at <', date('Y-m-d H:i:s'))
            ->set('status', 'EXPIRED')
            ->update();
    }
}
