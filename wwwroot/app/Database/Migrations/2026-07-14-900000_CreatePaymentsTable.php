<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: Create payments table.
 *
 * Stores Stripe payment intents, invoices, and subscription records.
 *
 * @since 1.8.0
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
class CreatePaymentsTable extends Migration
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
            'stripe_session_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'stripe_payment_intent_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'stripe_subscription_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'stripe_customer_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'amount' => [
                'type'    => 'INT',
                'null'    => true,
            ],
            'currency' => [
                'type'       => 'VARCHAR',
                'constraint' => 3,
                'null'       => true,
                'default'    => 'eur',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => false,
                'default'    => 'pending',
            ],
            'payment_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'subscription_plan' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'subscription_status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'transfer_id' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => true,
            ],
            'webhook_received_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'metadata_json' => [
                'type' => 'JSON',
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
        $this->forge->addKey('stripe_session_id');
        $this->forge->addKey('stripe_subscription_id');
        $this->forge->addKey('status');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');

        $this->forge->createTable('payments', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('payments', true);
    }
}
