<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

/**
 * Migration: Create users table.
 *
 * @since 1.1.1
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => false,
            ],
            'identity_type' => [
                'type'       => 'ENUM',
                'constraint' => ['physical', 'legal'],
                'null'       => false,
            ],
            'first_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'last_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
            ],
            'legal_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => true,
            ],
            'tax_id_encrypted' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'tax_id_hmac' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
                'null'       => true,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 254,
                'null'       => false,
            ],
            'email_verified_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'phone' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
            ],
            'totp_secret_encrypted' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'totp_enabled' => [
                'type'    => 'TINYINT',
                'constraint' => 1,
                'null'    => false,
                'default' => 0,
            ],
            'totp_failures' => [
                'type'    => 'INT',
                'null'    => false,
                'default' => 0,
            ],
            'totp_blocked_until' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'inactive', 'suspended', 'blocked'],
                'null'       => false,
                'default'    => 'active',
            ],
            'guarantee_level' => [
                'type'       => 'ENUM',
                'constraint' => ['low', 'substantial', 'high'],
                'null'       => false,
                'default'    => 'low',
            ],
            'last_login_at' => [
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
        $this->forge->addUniqueKey('email');
        $this->forge->addUniqueKey('tax_id_hmac');
        $this->forge->addKey('status');
        $this->forge->addKey('identity_type');
        $this->forge->addKey('created_at');

        $this->forge->createTable('users', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('users', true);
    }
}
