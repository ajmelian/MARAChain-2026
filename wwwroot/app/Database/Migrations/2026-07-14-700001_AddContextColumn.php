<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AppAddContextColumn extends Migration
{
    public function up(): void
    {
        $context = [
            'context' => [
                'type'       => 'varchar',
                'constraint' => 255,
                'null'       => true,
                'after'      => 'type',
            ],
        ];

        if (! $this->db->fieldExists('context', 'settings')) {
            $this->forge->addColumn('settings', $context);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('context', 'settings')) {
            $this->forge->dropColumn('settings', 'context');
        }
    }
}
