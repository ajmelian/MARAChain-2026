<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Create jobs table — queue de trabajos asincronos.
 *
 * Orquesta operaciones asincronas: IPFS upload, email, ledger sealing,
 * expiracion de transfers, reconciliacion IPFS.
 *
 * @since 1.9.0
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
class CreateJobsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => false,
            ],
            'queue' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
                'default'    => 'default',
            ],
            'type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'payload_json' => [
                'type' => 'JSON',
                'null' => false,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'queued',
            ],
            'attempts' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 0,
            ],
            'max_attempts' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 3,
            ],
            'available_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'reserved_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'completed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'failed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('queue');
        $this->forge->addKey('status');
        $this->forge->addKey('available_at');

        $this->forge->createTable('jobs', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('jobs', true);
    }
}
