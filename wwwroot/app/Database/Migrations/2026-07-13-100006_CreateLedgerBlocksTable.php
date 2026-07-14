<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Create ledger_blocks table.
 *
 * @since 1.1.1
 * @author Aythami
 */
class CreateLedgerBlocksTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => false,
            ],
            'block_number' => [
                'type' => 'BIGINT',
                'null' => false,
            ],
            'period_start' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'period_end' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'event_count' => [
                'type' => 'INT',
                'null' => false,
            ],
            'events_json' => [
                'type' => 'JSON',
                'null' => false,
            ],
            'merkle_root' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => false,
            ],
            'previous_block_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
            ],
            'block_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => false,
            ],
            'block_signature' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'signing_key_fingerprint' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => false,
            ],
            'schema_version' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'null'       => false,
                'default'    => '1.0',
            ],
            'sealed_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'checkpoint_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'checkpoint_tx_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 66,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('block_number');
        $this->forge->addUniqueKey('block_hash');
        $this->forge->addKey('sealed_at');

        $this->forge->createTable('ledger_blocks', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('ledger_blocks', true);
    }
}
