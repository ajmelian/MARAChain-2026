<?php

declare(strict_types=1);

namespace App\Commands;

use App\Services\LedgerService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Create the MARAChain ledger genesis block.
 *
 * The genesis block is block #1 with a single placeholder event.
 * Run once during initial deployment. Cannot be executed if a
 * genesis block already exists.
 *
 * @package App\Commands
 * @author  Aythami
 * @since   1.4.0
 */
class LedgerGenesis extends BaseCommand
{
    protected $group       = 'Ledger';
    protected $name        = 'ledger:genesis';
    protected $description = 'Create the MARAChain ledger genesis block (block #1).';

    /**
     * Execute the command.
     *
     * @param array<int, string> $params CLI parameters
     */
    public function run(array $params): void
    {
        $fingerprint = $params[0] ?? bin2hex(random_bytes(32));

        CLI::write('Creating genesis block...', 'yellow');

        try {
            $service = new LedgerService();
            $block   = $service->createGenesisBlock($fingerprint);

            CLI::write('Genesis block created successfully!', 'green');
            CLI::write("  Block UUID: {$block->id}");
            CLI::write("  Block Hash: {$block->blockHash}");
            CLI::write("  Merkle Root: {$block->merkleRoot}");
            CLI::write("  Sealed at: {$block->sealedAt}");
        } catch (\RuntimeException $e) {
            CLI::error($e->getMessage());
        }
    }
}
