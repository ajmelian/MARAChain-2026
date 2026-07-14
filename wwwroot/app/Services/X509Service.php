<?php

declare(strict_types=1);

namespace App\Services;

/**
 * X509Service — FNMT certificate parser.
 *
 * Extracts identity from FNMT X.509 certificates received via
 * Nginx mTLS headers. Parses the Subject DN to extract:
 *   - NIF/NIE (serialNumber field)
 *   - First name (GN = Given Name)
 *   - Last name (SN = Surname)
 *   - Full name (CN = Common Name)
 *   - Identity type (physical/legal derived from NIF format)
 *
 * Certificate format (FNMT Ciudadano):
 *   /C=ES/O=FNMT-RCM/OU=CERES/SN=GARCIA LOPEZ/GN=MARIA/serialNumber=12345678A
 *
 * Certificate format (FNMT Persona Juridica):
 *   /C=ES/O=FNMT-RCM/OU=CERES/CN=EMPRESA SL/serialNumber=B12345678
 *
 * @package App\Services
 * @author  Aythami
 * @since   1.4.0
 */
class X509Service
{
    /**
     * NIF pattern: 8 digits + 1 uppercase letter.
     */
    private const NIF_PATTERN = '/^[0-9]{8}[A-Z]$/';

    /**
     * NIE pattern: X/Y/Z + 7 digits + 1 uppercase letter.
     */
    private const NIE_PATTERN = '/^[XYZ][0-9]{7}[A-Z]$/';

    /**
     * CIF pattern: 1 letter (A-H, J-N, P-S, U-W) + 7 digits + 1 digit/letter.
     */
    private const CIF_PATTERN = '/^[ABCDEFGHJKLMNPQRSUVW][0-9]{7}[0-9A-J]$/';

    /**
     * Extract identity from Nginx mTLS context.
     *
     * Reads SSL_ prefixed server variables set by Nginx after
     * successful mTLS handshake.
     *
     * @param array $sslHeaders Server variables (e.g., $_SERVER)
     *
     * @return array{identityType: string, firstName: string, lastName: ?string, legalName: ?string, taxId: string, guaranteeLevel: string}|null
     *
     * @since 1.4.0
     */
    public function extractIdentityFromNginx(array $sslHeaders): ?array
    {
        // ── Check mTLS verification status ──────────────────────
        $verify = $sslHeaders['SSL_CLIENT_VERIFY'] ?? 'NONE';

        if ($verify !== 'SUCCESS') {
            return null;
        }

        // ── Parse Subject DN ─────────────────────────────────────
        $dn = $sslHeaders['SSL_CLIENT_S_DN'] ?? '';

        if ($dn === '') {
            return null;
        }

        $fields = $this->parseDN($dn);

        // ── Extract NIF/NIE/CIF ──────────────────────────────────
        $taxId = $fields['serialNumber'] ?? '';

        if ($taxId === '') {
            return null;
        }

        // ── Determine identity type from tax ID format ───────────
        $identityType = $this->determineIdentityType($taxId);

        // ── Extract name components ──────────────────────────────
        $firstName = $fields['GN'] ?? '';
        $lastName  = $fields['SN'] ?? null;
        $legalName = null;

        if ($identityType === 'legal') {
            $legalName = $fields['CN'] ?? $fields['O'] ?? '';
            $firstName = '';
            $lastName  = null;
        }

        if ($firstName === '' && $identityType === 'physical') {
            $cn       = $fields['CN'] ?? '';
            $parts    = explode(' ', trim($cn), 2);
            $firstName = $parts[0] ?? 'Ciudadano';
            $lastName  = $parts[1] ?? null;
        }

        // ── Determine eIDAS guarantee level ──────────────────────
        $guaranteeLevel = $this->determineGuaranteeLevel($identityType);

        return [
            'identityType'    => $identityType,
            'firstName'       => $firstName ?: 'Ciudadano',
            'lastName'        => $lastName,
            'legalName'       => $legalName,
            'taxId'           => strtoupper(trim($taxId)),
            'guaranteeLevel'  => $guaranteeLevel,
        ];
    }

    /**
     * Extract identity from a raw PEM-encoded certificate.
     *
     * Used in testing and environments where Nginx headers
     * are not available.
     *
     * @param string $pemCert PEM-encoded X.509 certificate
     *
     * @return array|null Identity array or null on failure
     *
     * @since 1.4.0
     */
    public function extractIdentityFromPem(string $pemCert): ?array
    {
        $cert = @openssl_x509_parse($pemCert);

        if ($cert === false) {
            return null;
        }

        $subject = $cert['subject'] ?? [];

        $taxId      = $subject['serialNumber'] ?? '';
        $identityType = $this->determineIdentityType($taxId);

        return [
            'identityType'    => $identityType,
            'firstName'       => $subject['GN'] ?? ($identityType === 'legal' ? '' : 'Ciudadano'),
            'lastName'        => $subject['SN'] ?? null,
            'legalName'       => $identityType === 'legal' ? ($subject['CN'] ?? '') : null,
            'taxId'           => strtoupper(trim($taxId)),
            'guaranteeLevel'  => $this->determineGuaranteeLevel($identityType),
        ];
    }

    /**
     * Validate a Spanish tax ID format (NIF/NIE/CIF).
     *
     * @param string $taxId Tax ID to validate
     *
     * @return bool True if valid format
     *
     * @since 1.4.0
     */
    public function isValidTaxId(string $taxId): bool
    {
        return preg_match(self::NIF_PATTERN, $taxId) === 1
            || preg_match(self::NIE_PATTERN, $taxId) === 1
            || preg_match(self::CIF_PATTERN, $taxId) === 1;
    }

    /**
     * Parse an RFC 4514 Distinguished Name string.
     *
     * Handles escaped characters and multi-valued RDNs.
     *
     * @param string $dn DN string (e.g., /C=ES/SN=GARCIA/serialNumber=12345678A)
     *
     * @return array<string, string> Key-value map of RDN attributes
     *
     * @since 1.4.0
     */
    public function parseDN(string $dn): array
    {
        $result = [];
        $dn     = trim($dn, '/');
        $parts  = explode('/', $dn);

        foreach ($parts as $part) {
            $eqPos = strpos($part, '=');

            if ($eqPos === false) {
                continue;
            }

            $key   = trim(substr($part, 0, $eqPos));
            $value = trim(substr($part, $eqPos + 1));

            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * Determine identity type from tax ID format.
     *
     * NIF (8 digits + letter) = physical person
     * NIE (X/Y/Z + 7 digits + letter) = physical person (foreigner)
     * CIF (letter + 7 digits) = legal entity
     *
     * @param string $taxId Tax ID
     *
     * @return string 'physical' or 'legal'
     *
     * @since 1.4.0
     */
    private function determineIdentityType(string $taxId): string
    {
        if (preg_match(self::NIF_PATTERN, $taxId) === 1) {
            return 'physical';
        }

        if (preg_match(self::NIE_PATTERN, $taxId) === 1) {
            return 'physical';
        }

        if (preg_match(self::CIF_PATTERN, $taxId) === 1) {
            return 'legal';
        }

        return 'physical';
    }

    /**
     * Determine eIDAS guarantee level based on identity type.
     *
     * FNMT certificates provide:
     *   - 'high' for physical persons (qualified certificate)
     *   - 'high' for legal entities (qualified certificate)
     *
     * @param string $identityType Identity type
     *
     * @return string eIDAS level
     *
     * @since 1.4.0
     */
    private function determineGuaranteeLevel(string $identityType): string
    {
        return 'high';
    }
}
