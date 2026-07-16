<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * TransferExpire — Marks expired transfers and blocks future access.
 *
 * Finds transfers with expiresAt < now and statuses that allow
 * expiration. Sets status to EXPIRED and records evidence.
 *
 * Designed to run as a systemd timer every 5 minutes.
 *
 * @package App\Commands
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 * @since   1.7.0
 */
class TransferExpire extends BaseCommand
{
    protected $group       = 'Transfers';
    protected $name        = 'transfers:expire';
    protected $description = 'Mark expired DocumentTransfer records as EXPIRED.';

    public function run(array $params): void
    {
        $db = db_connect();

        $expirable = ['PENDING_RECIPIENT', 'READY', 'SENDING', 'SENT',
                       'AVAILABLE', 'ACCESSED', 'DOWNLOADED'];

        $updated = $db->table('document_transfers')
            ->whereIn('status', $expirable)
            ->where('expires_at IS NOT NULL')
            ->where('expires_at <', date('Y-m-d H:i:s'))
            ->set('status', 'EXPIRED')
            ->update();

        if ($updated > 0) {
            CLI::write("Expired {$updated} transfer(s).", 'yellow');
        } else {
            CLI::write('No transfers to expire.', 'green');
        }
    }
}
