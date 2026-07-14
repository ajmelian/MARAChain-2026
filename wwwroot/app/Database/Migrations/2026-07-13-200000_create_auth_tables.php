<?php

declare(strict_types=1);

/**
 * SHIELD Auth Tables Migration (App override).
 *
 * This file exists to prevent the vendor SHIELD migration from running
 * directly, since we have a custom users table. The SHIELD vendor
 * migration at vendor/codeigniter4/shield/src/Database/Migrations/
 * will be auto-discovered and handled by the module system.
 *
 * This migration adds SHIELD-required columns to the existing users table.
 *
 * @package App\Database\Migrations
 * @author  Aythami
 * @since   1.2.0
 */

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuthTables extends Migration
{
    /**
     * Add SHIELD-related columns to the existing custom users table.
     */
    public function up(): void
    {
        // Add SHIELD-compatible columns to the existing custom users table.
        // These columns are needed for SHIELD's UserModel to function.
        $fields = [
            'username'       => ['type' => 'varchar', 'constraint' => 30, 'null' => true, 'after' => 'id'],
            'active'         => ['type' => 'tinyint', 'constraint' => 1, 'null' => true, 'default' => 1, 'after' => 'status'],
            'status_message' => ['type' => 'varchar', 'constraint' => 255, 'null' => true, 'after' => 'status'],
            'last_active'    => ['type' => 'datetime', 'null' => true, 'after' => 'status_message'],
        ];

        foreach ($fields as $name => $def) {
            if (! $this->db->fieldExists($name, 'users')) {
                $this->forge->addColumn('users', [$name => $def]);
            }
        }
    }

    public function down(): void
    {
        $columns = ['username', 'active', 'status_message', 'last_active'];
        foreach ($columns as $col) {
            if ($this->db->fieldExists($col, 'users')) {
                $this->forge->dropColumn('users', $col);
            }
        }
    }
}
