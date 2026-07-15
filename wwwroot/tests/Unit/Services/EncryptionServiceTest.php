<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\EncryptionService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Unit tests for EncryptionService.
 *
 * Tests marachain-envelope validation, recipient envelope extraction,
 * and manifest hash verification without any database dependency.
 *
 * @since 1.4.0
 * @author Aythami
 */
final class EncryptionServiceTest extends CIUnitTestCase
{
    private EncryptionService $service;

    /**
     * Prepare test environment before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new EncryptionService();
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->service);
    }

    // ──────────────────────────────────────────────────────────────
    // VALIDATE ENVELOPE
    // ──────────────────────────────────────────────────────────────

    /**
     * A valid marachain-envelope passes validation.
     *
     * @test
     */
    public function testValidateEnvelopeValid(): void
    {
        $envelope = [
            'format'        => 'marachain-envelope',
            'version'       => 1,
            'contentCipher' => 'AES-256-GCM',
            'manifestHash'  => hash('sha256', 'test'),
            'recipients'    => [],
        ];

        $result = $this->service->validateEnvelope($envelope);

        $this->assertTrue($result);
    }

    /**
     * An envelope missing the 'format' key fails validation.
     *
     * @test
     */
    public function testValidateEnvelopeMissingFormat(): void
    {
        $envelope = [
            'version'       => 1,
            'contentCipher' => 'AES-256-GCM',
            'manifestHash'  => hash('sha256', 'test'),
            'recipients'    => [],
        ];

        $result = $this->service->validateEnvelope($envelope);

        $this->assertFalse($result);
    }

    /**
     * An envelope with format != 'marachain-envelope' fails validation.
     *
     * @test
     */
    public function testValidateEnvelopeWrongFormat(): void
    {
        $envelope = [
            'format'        => 'other-format',
            'version'       => 1,
            'contentCipher' => 'AES-256-GCM',
            'manifestHash'  => hash('sha256', 'test'),
            'recipients'    => [],
        ];

        $result = $this->service->validateEnvelope($envelope);

        $this->assertFalse($result);
    }

    /**
     * An envelope with version != 1 fails validation.
     *
     * @test
     */
    public function testValidateEnvelopeWrongVersion(): void
    {
        $envelope = [
            'format'        => 'marachain-envelope',
            'version'       => 2,
            'contentCipher' => 'AES-256-GCM',
            'manifestHash'  => hash('sha256', 'test'),
            'recipients'    => [],
        ];

        $result = $this->service->validateEnvelope($envelope);

        $this->assertFalse($result);
    }

    /**
     * An envelope missing the 'recipients' key fails validation.
     *
     * @test
     */
    public function testValidateEnvelopeMissingRecipients(): void
    {
        $envelope = [
            'format'        => 'marachain-envelope',
            'version'       => 1,
            'contentCipher' => 'AES-256-GCM',
            'manifestHash'  => hash('sha256', 'test'),
        ];

        $result = $this->service->validateEnvelope($envelope);

        $this->assertFalse($result);
    }

    // ──────────────────────────────────────────────────────────────
    // GET RECIPIENT ENVELOPE
    // ──────────────────────────────────────────────────────────────

    /**
     * getRecipientEnvelope returns the correct recipient by userId.
     *
     * @test
     */
    public function testGetRecipientEnvelope(): void
    {
        $userId = '550e8400-e29b-41d4-a716-446655440001';

        $envelope = [
            'format'        => 'marachain-envelope',
            'version'       => 1,
            'contentCipher' => 'AES-256-GCM',
            'manifestHash'  => hash('sha256', 'test'),
            'recipients'    => [
                [
                    'userId'    => '550e8400-e29b-41d4-a716-446655440002',
                    'keyType'   => 'ecies-p256',
                    'wrappedDek' => 'base64-encoded-dek-1',
                ],
                [
                    'userId'    => $userId,
                    'keyType'   => 'ecies-p256',
                    'wrappedDek' => 'base64-encoded-dek-2',
                ],
                [
                    'userId'    => '550e8400-e29b-41d4-a716-446655440003',
                    'keyType'   => 'ecies-p256',
                    'wrappedDek' => 'base64-encoded-dek-3',
                ],
            ],
        ];

        $result = $this->service->getRecipientEnvelope($envelope, $userId);

        $this->assertNotNull($result);
        $this->assertSame($userId, $result['userId']);
        $this->assertSame('ecies-p256', $result['keyType']);
        $this->assertSame('base64-encoded-dek-2', $result['wrappedDek']);
    }

    /**
     * getRecipientEnvelope returns null when userId is not found.
     *
     * @test
     */
    public function testGetRecipientEnvelopeNotFound(): void
    {
        $envelope = [
            'format'        => 'marachain-envelope',
            'version'       => 1,
            'contentCipher' => 'AES-256-GCM',
            'manifestHash'  => hash('sha256', 'test'),
            'recipients'    => [
                [
                    'userId'    => '550e8400-e29b-41d4-a716-446655440001',
                    'keyType'   => 'ecies-p256',
                    'wrappedDek' => 'base64-encoded-dek',
                ],
            ],
        ];

        $result = $this->service->getRecipientEnvelope(
            $envelope,
            '00000000-0000-4000-a000-000000000000',
        );

        $this->assertNull($result);
    }

    // ──────────────────────────────────────────────────────────────
    // VERIFY MANIFEST HASH
    // ──────────────────────────────────────────────────────────────

    /**
     * verifyManifestHash returns true when the hash matches.
     *
     * @test
     */
    public function testVerifyManifestHash(): void
    {
        $manifestJson = json_encode(['foo' => 'bar']);
        $expectedHash = hash('sha256', $manifestJson);

        $result = $this->service->verifyManifestHash($expectedHash, $manifestJson);

        $this->assertTrue($result);
    }

    /**
     * verifyManifestHash returns false when the hash does not match.
     *
     * @test
     */
    public function testVerifyManifestHashMismatch(): void
    {
        $manifestJson = json_encode(['foo' => 'bar']);
        $wrongHash    = hash('sha256', 'completely different content');

        $result = $this->service->verifyManifestHash($wrongHash, $manifestJson);

        $this->assertFalse($result);
    }
}
