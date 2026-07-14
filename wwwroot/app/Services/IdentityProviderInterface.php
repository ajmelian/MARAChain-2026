<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Identity provider contract.
 *
 * Each external identity provider MUST implement this interface.
 * Resolves raw provider data into a canonical identity structure
 * consumed by the MARAChain domain.
 *
 * Identity resolution is performed server-side after the user
 * authenticates via the external provider.
 *
 * @package App\Services
 * @since   1.3.0
 * @author  Aythami Melián Perdomo <ajmelper@gmail.com>
 */
interface IdentityProviderInterface
{
    /**
     * Resolve a canonical identity from provider-specific data.
     *
     * Transforms the raw data returned by the external identity
     * provider into a normalised identity structure used internally
     * by the application.
     *
     * @param  array $providerData Provider-specific identity data
     *                             (e.g. SAML attributes, OIDC claims).
     * @return array{identityType: string, firstName: string, lastName: ?string, legalName: ?string, taxId: string, guaranteeLevel: string}
     *         Canonical identity with the following keys:
     *         - identityType:    Type of identity (person, legal_entity).
     *         - firstName:       First name or given name.
     *         - lastName:        Last name or surname (nullable).
     *         - legalName:       Full legal name if different (nullable).
     *         - taxId:           Tax identification number (NIF/CIF/NIE).
     *         - guaranteeLevel:  Level of identity guarantee (low, substantial, high).
     *
     * @throws \RuntimeException If the provider data cannot be resolved
     *                           or fails validation.
     *
     * @since 1.3.0
     * @author Aythami Melián Perdomo <ajmelper@gmail.com>
     */
    public function resolveIdentity(array $providerData): array;

    /**
     * Get the unique provider name.
     *
     * Returns a string identifier for this provider implementation
     * (e.g. "clave", "certificado", "oauth2").
     *
     * @return string Provider identifier.
     *
     * @since 1.3.0
     * @author Aythami Melián Perdomo <ajmelper@gmail.com>
     */
    public function getName(): string;
}
