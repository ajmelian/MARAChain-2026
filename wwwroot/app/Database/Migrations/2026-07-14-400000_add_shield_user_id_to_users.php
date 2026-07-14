<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Add shield_user_id to users table.
 *
 * Links our custom users table (UUID PK) to SHIELD's shield_users
 * table (auto-increment INT PK). This allows SHIELD to manage
 * authentication while our table stores MARAChain-specific data
 * (identity types, tax IDs, TOTP, etc.).
 *
 * @since 1.3.0
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
class AddShieldUserIdToUsers extends Migration
{
    public function up(): void
    {
        $fields = [
            'shield_user_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'id',
            ],
        ];

        if (! $this->db->fieldExists('shield_user_id', 'users')) {
            $this->forge->addColumn('users', $fields);
            $this->forge->addUniqueKey('shield_user_id');
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('shield_user_id', 'users')) {
            $this->forge->dropColumn('users', 'shield_user_id');
        }
    }
}
