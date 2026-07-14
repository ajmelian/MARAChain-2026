<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Create documents table.
 *
 * @since 1.1.1
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
class CreateDocumentsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => false,
            ],
            'owner_id' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => false,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'mime_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'file_size' => [
                'type' => 'BIGINT',
                'null' => false,
            ],
            'file_hash_sha256' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => false,
            ],
            'version' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 1,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['DRAFT', 'SEALED', 'ENCRYPTED', 'ARCHIVED', 'DESTROYED'],
                'null'       => false,
                'default'    => 'DRAFT',
            ],
            'manifest_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
            ],
            'manifest_json' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'cid' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'encryption_format' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'content_cipher' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'default'    => 'AES-256-GCM',
            ],
            'sealed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'encrypted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'archived_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'destroyed_at' => [
                'type' => 'DATETIME',
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
        $this->forge->addKey('owner_id');
        $this->forge->addForeignKey('owner_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addKey('status');
        $this->forge->addKey('file_hash_sha256');
        $this->forge->addKey('created_at');

        $this->forge->createTable('documents', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('documents', true);
    }
}
