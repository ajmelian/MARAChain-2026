<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Create document_transfers table.
 *
 * @since 1.1.1
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
class CreateDocumentTransfersTable extends Migration
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
            'sender_id' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => false,
            ],
            'recipient_id' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => false,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'PENDING_RECIPIENT', 'READY', 'SENDING', 'SENT',
                    'AVAILABLE', 'ACCESSED', 'DOWNLOADED', 'ACCEPTED',
                    'REJECTED', 'EXPIRED', 'REVOKED', 'FAILED',
                ],
                'null'    => false,
                'default' => 'PENDING_RECIPIENT',
            ],
            'requires_signature' => [
                'type'    => 'TINYINT',
                'constraint' => 1,
                'null'    => false,
                'default' => 0,
            ],
            'signature_completed' => [
                'type'    => 'TINYINT',
                'constraint' => 1,
                'null'    => false,
                'default' => 0,
            ],
            'signature_request_id' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => true,
            ],
            'requires_encryption' => [
                'type'    => 'TINYINT',
                'constraint' => 1,
                'null'    => false,
                'default' => 1,
            ],
            'encryption_envelope_id' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => true,
            ],
            'security_level' => [
                'type'       => 'ENUM',
                'constraint' => ['standard', 'signed', 'signed_sealed'],
                'null'       => false,
                'default'    => 'standard',
            ],
            'idempotency_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => false,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'available_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'accessed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'downloaded_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'accepted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'rejected_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'revoked_at' => [
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
        $this->forge->addUniqueKey('idempotency_key');
        $this->forge->addKey('document_id');
        $this->forge->addKey('sender_id');
        $this->forge->addKey('recipient_id');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('document_id', 'documents', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('sender_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('recipient_id', 'users', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('document_transfers', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('document_transfers', true);
    }
}
