<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\X509Service;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Unit tests for X509Service.
 *
 * Tests DN parsing, tax ID validation, and identity extraction
 * from X.509 certificates. No database required.
 *
 * @since 1.4.0
 * @author Aythami
 */
final class X509ServiceTest extends CIUnitTestCase
{
    private X509Service $service;

    /**
     * Prepare test environment before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new X509Service();
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
    // parseDN
    // ──────────────────────────────────────────────────────────────

    /**
     * parseDN correctly splits a basic RFC 4514 DN into fields.
     *
     * @test
     */
    public function testParseDNBasic(): void
    {
        $dn = '/C=ES/SN=GARCIA/GN=MARIA/serialNumber=12345678A';

        $fields = $this->service->parseDN($dn);

        $this->assertSame('ES', $fields['C']);
        $this->assertSame('GARCIA', $fields['SN']);
        $this->assertSame('MARIA', $fields['GN']);
        $this->assertSame('12345678A', $fields['serialNumber']);
    }

    /**
     * parseDN correctly handles attribute values containing '=' signs.
     *
     * @test
     */
    public function testParseDNWithEquals(): void
    {
        $dn = '/C=ES/CN=EMPRESA=SL/organizationIdentifier=VATES-B12345678';

        $fields = $this->service->parseDN($dn);

        $this->assertSame('ES', $fields['C']);
        $this->assertSame('EMPRESA=SL', $fields['CN']);
        $this->assertSame('VATES-B12345678', $fields['organizationIdentifier']);
    }

    // ──────────────────────────────────────────────────────────────
    // isValidTaxId
    // ──────────────────────────────────────────────────────────────

    /**
     * isValidTaxId returns true for a valid NIF (8 digits + letter).
     *
     * @test
     */
    public function testIsValidNif(): void
    {
        $result = $this->service->isValidTaxId('12345678A');

        $this->assertTrue($result);
    }

    /**
     * isValidTaxId returns true for a valid NIE (X/Y/Z + 7 digits + letter).
     *
     * @test
     */
    public function testIsValidNie(): void
    {
        $result = $this->service->isValidTaxId('X1234567A');

        $this->assertTrue($result);
    }

    /**
     * isValidTaxId returns true for a valid CIF (letter + 7 digits).
     *
     * @test
     */
    public function testIsValidCif(): void
    {
        $result = $this->service->isValidTaxId('B12345678');

        $this->assertTrue($result);
    }

    /**
     * isValidTaxId returns false for an invalid tax ID.
     *
     * @test
     */
    public function testInvalidTaxId(): void
    {
        $result = $this->service->isValidTaxId('123');

        $this->assertFalse($result);
    }

    /**
     * isValidTaxId returns false for an empty string.
     *
     * @test
     */
    public function testIsValidTaxIdEmptyString(): void
    {
        $result = $this->service->isValidTaxId('');

        $this->assertFalse($result);
    }

    // ──────────────────────────────────────────────────────────────
    // extractIdentityFromPem
    // ──────────────────────────────────────────────────────────────

    /**
     * extractIdentityFromPem extracts correct identity from a
     * self-signed certificate with serialNumber in the subject DN.
     *
     * @test
     */
    public function testExtractIdentityFromPem(): void
    {
        // Generate a self-signed certificate with FNMT-like attributes
        $dn = [
            'countryName'            => 'ES',
            'organizationName'       => 'FNMT-RCM',
            'organizationalUnitName' => 'CERES',
            'commonName'             => 'MARIA GARCIA LOPEZ',
            'serialNumber'           => '12345678A',
        ];

        $privKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $csr     = openssl_csr_new($dn, $privKey);
        $cert    = openssl_csr_sign($csr, null, $privKey, 365);

        $pemCert = '';
        openssl_x509_export($cert, $pemCert);

        $identity = $this->service->extractIdentityFromPem($pemCert);

        $this->assertNotNull($identity, 'extractIdentityFromPem returned null');
        $this->assertSame('physical', $identity['identityType']);
        $this->assertSame('12345678A', $identity['taxId']);
        $this->assertSame('high', $identity['guaranteeLevel']);
    }

    /**
     * extractIdentityFromPem returns null for invalid PEM data.
     *
     * @test
     */
    public function testExtractIdentityFromPemInvalidCert(): void
    {
        $identity = $this->service->extractIdentityFromPem('NOT A VALID CERTIFICATE');

        $this->assertNull($identity);
    }

    // ──────────────────────────────────────────────────────────────
    // extractIdentityFromNginx
    // ──────────────────────────────────────────────────────────────

    /**
     * extractIdentityFromNginx returns identity for valid mTLS headers.
     *
     * @test
     */
    public function testExtractIdentityFromNginxWithPhysicalPerson(): void
    {
        $sslHeaders = [
            'SSL_CLIENT_VERIFY' => 'SUCCESS',
            'SSL_CLIENT_S_DN'   => '/C=ES/O=FNMT-RCM/OU=CERES/SN=GARCIA/GN=MARIA/serialNumber=12345678A',
        ];

        $identity = $this->service->extractIdentityFromNginx($sslHeaders);

        $this->assertNotNull($identity);
        $this->assertSame('physical', $identity['identityType']);
        $this->assertSame('MARIA', $identity['firstName']);
        $this->assertSame('GARCIA', $identity['lastName']);
        $this->assertSame('12345678A', $identity['taxId']);
        $this->assertSame('high', $identity['guaranteeLevel']);
        $this->assertNull($identity['legalName']);
    }

    /**
     * extractIdentityFromNginx returns identity for legal entity certificate.
     *
     * @test
     */
    public function testExtractIdentityFromNginxWithLegalEntity(): void
    {
        $sslHeaders = [
            'SSL_CLIENT_VERIFY' => 'SUCCESS',
            'SSL_CLIENT_S_DN'   => '/C=ES/O=FNMT-RCM/OU=CERES/CN=EMPRESA SL/serialNumber=B12345678',
        ];

        $identity = $this->service->extractIdentityFromNginx($sslHeaders);

        $this->assertNotNull($identity);
        $this->assertSame('legal', $identity['identityType']);
        $this->assertSame('EMPRESA SL', $identity['legalName']);
        $this->assertSame('B12345678', $identity['taxId']);
        $this->assertSame('high', $identity['guaranteeLevel']);
    }

    /**
     * extractIdentityFromNginx returns null when SSL_CLIENT_VERIFY is not SUCCESS.
     *
     * @test
     */
    public function testExtractIdentityFromNginxNotVerified(): void
    {
        $sslHeaders = [
            'SSL_CLIENT_VERIFY' => 'NONE',
            'SSL_CLIENT_S_DN'   => '/C=ES/SN=GARCIA/GN=MARIA/serialNumber=12345678A',
        ];

        $identity = $this->service->extractIdentityFromNginx($sslHeaders);

        $this->assertNull($identity);
    }

    /**
     * extractIdentityFromNginx returns null when DN is empty.
     *
     * @test
     */
    public function testExtractIdentityFromNginxEmptyDN(): void
    {
        $sslHeaders = [
            'SSL_CLIENT_VERIFY' => 'SUCCESS',
            'SSL_CLIENT_S_DN'   => '',
        ];

        $identity = $this->service->extractIdentityFromNginx($sslHeaders);

        $this->assertNull($identity);
    }
}
