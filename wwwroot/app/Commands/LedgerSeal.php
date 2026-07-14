<?php

declare(strict_types=1);

namespace App\Commands;

use App\Services\LedgerService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Seal a new ledger block with all pending evidence.
 *
 * Collects evidence not yet in the ledger, builds a Merkle tree,
 * computes the block hash, signs, and seals. If no pending evidence
 * exists, informs and exits.
 *
 * @package App\Commands
 * @author  Aythami
 * @since   1.4.0
 */
class LedgerSeal extends BaseCommand
{
    protected $group       = 'Ledger';
    protected $name        = 'ledger:seal';
    protected $description = 'Seal a new block with all pending evidence into the ledger.';

    /**
     * Execute the command.
     *
     * @param array<int, string> $params CLI parameters
     */
    public function run(array $params): void
    {
        $fingerprint = $params[0] ?? bin2hex(random_bytes(32));

        CLI::write('Sealing new ledger block...', 'yellow');

        $service = new LedgerService();
        $result  = $service->sealBlock($fingerprint);

        if ($result === null) {
            CLI::write('No pending evidence to seal.', 'yellow');

            return;
        }

        $block = $result['block'];

        CLI::write('Block sealed successfully!', 'green');
        CLI::write("  Block #{$block->blockNumber}");
        CLI::write("  Block UUID: {$block->id}");
        CLI::write("  Events included: {$result['eventCount']}");
        CLI::write("  Merkle root: {$result['merkleRoot']}");
        CLI::write("  Block hash: {$block->blockHash}");
        CLI::write("  Sealed at: {$block->sealedAt}");
    }
}
