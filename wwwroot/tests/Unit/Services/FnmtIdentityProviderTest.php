<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\FnmtIdentityProvider;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Unit tests for FnmtIdentityProvider.
 *
 * Tests identity resolution from Nginx mTLS headers (server array),
 * fallback behaviour, and provider metadata. No database required.
 *
 * @since 1.4.0
 * @author Aythami
 */
final class FnmtIdentityProviderTest extends CIUnitTestCase
{
    private FnmtIdentityProvider $provider;

    /**
     * Prepare test environment before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new FnmtIdentityProvider();
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->provider);
    }

    // ──────────────────────────────────────────────────────────────
    // resolveIdentity
    // ──────────────────────────────────────────────────────────────

    /**
     * resolveIdentity returns a canonical identity array when
     * SSL_CLIENT_VERIFY=SUCCESS and a valid DN is provided.
     *
     * @test
     */
    public function testResolveIdentityFromServer(): void
    {
        $providerData = [
            'server' => [
                'SSL_CLIENT_VERIFY' => 'SUCCESS',
                'SSL_CLIENT_S_DN'   => '/C=ES/O=FNMT-RCM/OU=CERES/SN=GARCIA/GN=MARIA/serialNumber=12345678A',
            ],
        ];

        $identity = $this->provider->resolveIdentity($providerData);

        $this->assertIsArray($identity);
        $this->assertSame('physical', $identity['identityType']);
        $this->assertSame('12345678A', $identity['taxId']);
        $this->assertSame('high', $identity['guaranteeLevel']);
        $this->assertArrayHasKey('firstName', $identity);
        $this->assertArrayHasKey('lastName', $identity);
        $this->assertArrayHasKey('legalName', $identity);
    }

    /**
     * resolveIdentity throws RuntimeException when SSL_CLIENT_VERIFY
     * is NONE (no certificate presented).
     *
     * @test
     */
    public function testResolveIdentityNoVerify(): void
    {
        $providerData = [
            'server' => [
                'SSL_CLIENT_VERIFY' => 'NONE',
                'SSL_CLIENT_S_DN'   => '/C=ES/SN=GARCIA/GN=MARIA/serialNumber=12345678A',
            ],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No se pudo resolver la identidad');

        $this->provider->resolveIdentity($providerData);
    }

    /**
     * resolveIdentity throws RuntimeException when SSL_CLIENT_S_DN
     * is missing from the server array.
     *
     * @test
     */
    public function testResolveIdentityNoDN(): void
    {
        $providerData = [
            'server' => [
                'SSL_CLIENT_VERIFY' => 'SUCCESS',
            ],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No se pudo resolver la identidad');

        $this->provider->resolveIdentity($providerData);
    }

    /**
     * resolveIdentity throws RuntimeException when the tax ID in
     * the certificate does not match any valid Spanish tax ID format.
     *
     * @test
     */
    public function testResolveIdentityInvalidTaxId(): void
    {
        // serialNumber=INVALID — does not match NIF/NIE/CIF pattern
        $providerData = [
            'server' => [
                'SSL_CLIENT_VERIFY' => 'SUCCESS',
                'SSL_CLIENT_S_DN'   => '/C=ES/SN=GARCIA/GN=MARIA/serialNumber=INVALID',
            ],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('El NIF/NIE/CIF del certificado FNMT no es valido');

        $this->provider->resolveIdentity($providerData);
    }

    /**
     * resolveIdentity with an empty server array throws RuntimeException.
     *
     * @test
     */
    public function testResolveIdentityEmptyServer(): void
    {
        $providerData = [
            'server' => [],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No se pudo resolver la identidad');

        $this->provider->resolveIdentity($providerData);
    }

    // ──────────────────────────────────────────────────────────────
    // getName
    // ──────────────────────────────────────────────────────────────

    /**
     * getName returns the provider identifier 'fnmt'.
     *
     * @test
     */
    public function testGetName(): void
    {
        $name = $this->provider->getName();

        $this->assertSame('fnmt', $name);
    }
}
