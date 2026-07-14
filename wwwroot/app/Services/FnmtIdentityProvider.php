<?php

declare(strict_types=1);

namespace App\Services;

/**
 * FnmtIdentityProvider — FNMT digital certificate identity.
 *
 * Implements IdentityProviderInterface for Spanish FNMT certificates.
 * Resolves user identity from X.509 certificate data passed by Nginx
 * via mTLS headers (SSL_CLIENT_VERIFY, SSL_CLIENT_S_DN).
 *
 * Guarantee level: 'high' (eIDAS qualified certificate).
 *
 * @package App\Services
 * @author  Aythami
 * @since   1.4.0
 */
class FnmtIdentityProvider implements IdentityProviderInterface
{
    private X509Service $x509;

    public function __construct()
    {
        $this->x509 = new X509Service();
    }

    /**
     * Resolve identity from FNMT certificate data.
     *
     * $providerData should contain either:
     *   - 'server' => $_SERVER array (Nginx mTLS headers), OR
     *   - 'pem' => PEM-encoded certificate string
     *
     * @param array $providerData Provider-specific data
     *
     * @return array{identityType: string, firstName: string, lastName: ?string, legalName: ?string, taxId: string, guaranteeLevel: string}
     *
     * @throws \RuntimeException When certificate is invalid or identity cannot be resolved
     */
    public function resolveIdentity(array $providerData): array
    {
        $identity = null;

        if (isset($providerData['server'])) {
            $identity = $this->x509->extractIdentityFromNginx($providerData['server']);
        } elseif (isset($providerData['pem'])) {
            $identity = $this->x509->extractIdentityFromPem($providerData['pem']);
        }

        if ($identity === null) {
            throw new \RuntimeException(
                'No se pudo resolver la identidad desde el certificado FNMT.'
            );
        }

        if ($identity['taxId'] === '' || ! $this->x509->isValidTaxId($identity['taxId'])) {
            throw new \RuntimeException(
                'El NIF/NIE/CIF del certificado FNMT no es valido: ' . $identity['taxId']
            );
        }

        return $identity;
    }

    /**
     * @return string Provider identifier
     */
    public function getName(): string
    {
        return 'fnmt';
    }
}
