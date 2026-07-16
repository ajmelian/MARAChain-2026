<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Add ipfs_cid and blockchain_tx_id to documents table.
 *
 * ipfs_cid: CID hash del ciphertext en IPFS privado.
 * blockchain_tx_id: hash del bloque del ledger donde se registró
 * la transacción (ledger block hash).
 *
 * Estos dos campos son los ÚNICOS vínculos entre MySQL ↔ IPFS ↔ Blockchain.
 * Para eliminar: se borra ipfs_cid (documento "olvidado"),
 * se conserva blockchain_tx_id para trazabilidad.
 *
 * @since 1.8.0
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
class AddIpfsAndBlockchainIds extends Migration
{
    public function up(): void
    {
        $fields = [
            'ipfs_cid' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'after'      => 'cid',
            ],
            'blockchain_tx_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 66,
                'null'       => true,
                'after'      => 'ipfs_cid',
            ],
        ];

        if (! $this->db->fieldExists('ipfs_cid', 'documents')) {
            $this->forge->addColumn('documents', $fields);
        }

        $this->forge->addKey('ipfs_cid');
    }

    public function down(): void
    {
        if ($this->db->fieldExists('ipfs_cid', 'documents')) {
            $this->forge->dropColumn('documents', 'ipfs_cid');
        }
        if ($this->db->fieldExists('blockchain_tx_id', 'documents')) {
            $this->forge->dropColumn('documents', 'blockchain_tx_id');
        }
    }
}
