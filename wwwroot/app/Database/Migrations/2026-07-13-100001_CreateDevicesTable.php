<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Create devices table.
 *
 * @since 1.1.1
 * @author Aythami
 */
class CreateDevicesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => false,
            ],
            'user_id' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => false,
            ],
            'device_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'device_type' => [
                'type'       => 'ENUM',
                'constraint' => ['desktop', 'laptop', 'tablet', 'mobile', 'other'],
                'null'       => false,
                'default'    => 'other',
            ],
            'operating_system' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'browser' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'public_key_fingerprint' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => false,
            ],
            'public_key_algorithm' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
            ],
            'cryptographic_epoch' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 1,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'revoked', 'lost'],
                'null'       => false,
                'default'    => 'active',
            ],
            'first_seen_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'last_seen_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'revoked_at' => [
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
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addKey('status');
        $this->forge->addKey('public_key_fingerprint');

        $this->forge->createTable('devices', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('devices', true);
    }
}
