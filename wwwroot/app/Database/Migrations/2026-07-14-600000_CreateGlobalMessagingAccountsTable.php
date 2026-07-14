<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Create global_messaging_accounts table.
 *
 * Stores configuration for each channel's global account.
 * Only one active account per channel and environment.
 *
 * @since 1.5.0
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
class CreateGlobalMessagingAccountsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => false,
            ],
            'environment' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'development',
            ],
            'channel' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
            ],
            'account_reference' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'credential_reference' => [
                'type'       => 'VARCHAR',
                'constraint' => 512,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['PENDING_CONFIGURATION', 'CONNECTED', 'DEGRADED', 'DISCONNECTED', 'DISABLED', 'ERROR'],
                'null'       => false,
                'default'    => 'PENDING_CONFIGURATION',
            ],
            'connected_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_health_check_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_error_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'disabled_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'version' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 1,
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
        $this->forge->addUniqueKey(['environment', 'channel']);
        $this->forge->addKey('channel');
        $this->forge->addKey('status');

        $this->forge->createTable('global_messaging_accounts', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('global_messaging_accounts', true);
    }
}
