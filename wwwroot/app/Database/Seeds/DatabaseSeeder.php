<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Database seeder for MARAChain test fixtures.
 *
 * Seeds all tables with the minimum data required by the controller
 * integration tests. Each test class refreshes the database before
 * running, so this seeder is invoked on every test setUp().
 *
 * @since 1.1.1
 * @author Aythami
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = date('Y-m-d H:i:s');

        // ──────────────────────────────────────────────────────────
        //  User fixture
        // ──────────────────────────────────────────────────────────
        $this->db->table('users')->insert([
            'id'                => '550e8400-e29b-41d4-a716-446655440000',
            'identity_type'     => 'physical',
            'first_name'        => 'Test',
            'last_name'         => 'User',
            'email'             => 'test.user@marachain.local',
            'status'            => 'active',
            'guarantee_level'   => 'substantial',
            'totp_enabled'      => 0,
            'totp_failures'     => 0,
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);

        // ──────────────────────────────────────────────────────────
        //  Device fixture
        // ──────────────────────────────────────────────────────────
        $this->db->table('devices')->insert([
            'id'                     => '660e8400-e29b-41d4-a716-446655440001',
            'user_id'                => '550e8400-e29b-41d4-a716-446655440000',
            'device_name'            => 'Test Device',
            'device_type'            => 'desktop',
            'operating_system'       => 'Linux x86_64',
            'browser'                => 'Firefox',
            'public_key_fingerprint' => 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0c1d2e3f4a5b6c7d8e9f0a1b2',
            'public_key_algorithm'   => 'ED25519',
            'status'                 => 'active',
            'first_seen_at'          => $now,
            'created_at'             => $now,
            'updated_at'             => $now,
        ]);

        // ──────────────────────────────────────────────────────────
        //  Document fixture (DRAFT so seal can transition to SEALED)
        // ──────────────────────────────────────────────────────────
        $this->db->table('documents')->insert([
            'id'               => '770e8400-e29b-41d4-a716-446655440002',
            'owner_id'         => '550e8400-e29b-41d4-a716-446655440000',
            'title'            => 'Test Document',
            'mime_type'        => 'application/pdf',
            'file_size'        => 102400,
            'file_hash_sha256' => str_repeat('a', 64),
            'version'          => 1,
            'status'           => 'DRAFT',
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        // ──────────────────────────────────────────────────────────
        //  Document transfer fixture
        // ──────────────────────────────────────────────────────────
        $this->db->table('document_transfers')->insert([
            'id'                  => '880e8400-e29b-41d4-a716-446655440003',
            'document_id'         => '770e8400-e29b-41d4-a716-446655440002',
            'sender_id'           => '550e8400-e29b-41d4-a716-446655440000',
            'recipient_id'        => '550e8400-e29b-41d4-a716-446655440000',
            'status'              => 'PENDING_RECIPIENT',
            'requires_signature'  => 0,
            'signature_completed' => 0,
            'requires_encryption' => 1,
            'security_level'      => 'standard',
            'idempotency_key'     => str_repeat('x', 64),
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);

        // ──────────────────────────────────────────────────────────
        //  Signature request fixture
        // ──────────────────────────────────────────────────────────
        $this->db->table('signature_requests')->insert([
            'id'                 => '990e8400-e29b-41d4-a716-446655440004',
            'document_id'        => '770e8400-e29b-41d4-a716-446655440002',
            'user_id'            => '550e8400-e29b-41d4-a716-446655440000',
            'signature_intent'   => 'approve',
            'status'             => 'CREATED',
            'manifest_version'   => 1,
            'manifest_json'      => json_encode(['test' => true]),
            'manifest_hash'      => str_repeat('a', 64),
            'digest_algorithm'   => 'SHA-256',
            'signature_provider' => 'AUTOFIRMA',
            'nonce'              => str_repeat('n', 64),
            'issued_at'          => $now,
            'expires_at'         => date('Y-m-d H:i:s', strtotime('+2 hours')),
            'created_at'         => $now,
            'updated_at'         => $now,
        ]);
    }
}
