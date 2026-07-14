<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Create signature_requests table.
 *
 * @since 1.1.1
 * @author Aythami
 */
class CreateSignatureRequestsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => false,
            ],
            'document_id' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => false,
            ],
            'user_id' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => false,
            ],
            'signature_intent' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'CREATED', 'PROVIDER_REQUESTED', 'PROVIDER_COMPLETED',
                    'VALIDATED', 'CONSUMED', 'FAILED', 'EXPIRED',
                ],
                'null'    => false,
                'default' => 'CREATED',
            ],
            'manifest_version' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 1,
            ],
            'manifest_json' => [
                'type' => 'JSON',
                'null' => false,
            ],
            'manifest_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => false,
            ],
            'digest_algorithm' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'SHA-256',
            ],
            'signature_provider' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'provider_request_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'provider_response_json' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'signed_digest' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'signer_identity' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'signature_algorithm' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'nonce' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => false,
            ],
            'issued_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'completed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'failed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'failure_reason' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
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
        $this->forge->addUniqueKey('nonce');
        $this->forge->addKey('document_id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('document_id', 'documents', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('signature_requests', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('signature_requests', true);
    }
}
