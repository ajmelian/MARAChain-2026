<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Create notifications table.
 *
 * @since 1.1.1
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
class CreateNotificationsTable extends Migration
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
            'recipient_user_id' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => true,
            ],
            'recipient_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 254,
                'null'       => false,
            ],
            'notification_type' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'auth_success', 'auth_failed', 'totp_reset',
                    'device_added', 'transfer_available', 'transfer_accessed',
                    'transfer_downloaded', 'transfer_revoked', 'transfer_expired',
                    'invitation', 'session_revoked', 'account_blocked', 'admin_action',
                ],
                'null' => false,
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
                'constraint' => ['PENDING', 'SENDING', 'SENT', 'FAILED', 'DEAD_LETTER'],
                'null'       => false,
                'default'    => 'PENDING',
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
            'last_attempt_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'sent_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'provider_message_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'provider_response' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'scheduled_at' => [
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
        $this->forge->addKey('transfer_id');
        $this->forge->addKey('recipient_user_id');
        $this->forge->addKey('recipient_email');
        $this->forge->addKey('status');
        $this->forge->addKey('notification_type');
        $this->forge->addKey('scheduled_at');
        $this->forge->addForeignKey('transfer_id', 'document_transfers', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('recipient_user_id', 'users', 'id', 'SET NULL', 'CASCADE');

        $this->forge->createTable('notifications', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('notifications', true);
    }
}
