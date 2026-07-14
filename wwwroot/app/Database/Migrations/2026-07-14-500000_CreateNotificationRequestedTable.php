<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Create notification_requested outbox table.
 *
 * Transactional outbox for notification delivery across all channels.
 * Each row represents a notification queued for delivery.
 *
 * @since 1.5.0
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
class CreateNotificationRequestedTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => false,
            ],
            'transfer_id' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => true,
            ],
            'channel' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
            ],
            'recipient_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'subject' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'body_text' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'body_html' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['QUEUED', 'SENDING', 'SENT', 'DELIVERED', 'FAILED', 'RETRYING', 'DEAD_LETTER', 'CANCELLED'],
                'null'       => false,
                'default'    => 'QUEUED',
            ],
            'priority' => [
                'type'       => 'ENUM',
                'constraint' => ['low', 'normal', 'high', 'critical'],
                'null'       => false,
                'default'    => 'normal',
            ],
            'attempt_count' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 0,
            ],
            'max_attempts' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 5,
            ],
            'provider_message_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'error_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'scheduled_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'sent_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_attempt_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'idempotency_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => false,
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
        $this->forge->addKey('transfer_id');
        $this->forge->addKey('channel');
        $this->forge->addKey('status');
        $this->forge->addKey('scheduled_at');
        $this->forge->addForeignKey('transfer_id', 'document_transfers', 'id', 'SET NULL', 'CASCADE');

        $this->forge->createTable('notification_requested', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('notification_requested', true);
    }
}
