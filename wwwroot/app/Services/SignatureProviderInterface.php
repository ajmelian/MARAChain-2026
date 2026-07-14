<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Signature provider contract.
 *
 * Each external signature provider MUST implement this interface.
 * The provider receives only the DIGEST (hash of the canonicalized
 * manifest), NEVER the clear document, DEK, private keys, or CID.
 *
 * This ensures zero-knowledge of document content by the provider.
 *
 * @package App\Services
 * @since   1.3.0
 * @author  Aythami
 */
interface SignatureProviderInterface
{
    /**
     * Request a signature over a digest.
     *
     * Sends the digest to the external signature provider for signing.
     * The provider returns a request ID that can be used to poll status.
     *
     * @param  string $digest    Hex-encoded hash of the canonicalized manifest.
     * @param  string $algorithm Hash algorithm used (SHA-256, SHA-512).
     * @param  string $intent    Operation type (document_send, approve, review).
     * @param  array  $options   Provider-specific options (e.g. certificate profile).
     * @return array{requestId: string, status: string}
     *                           Returns the provider request ID and initial status.
     *
     * @throws \RuntimeException On provider communication error or invalid input.
     *
     * @since 1.3.0
     * @author Aythami
     */
    public function requestSignature(string $digest, string $algorithm, string $intent, array $options = []): array;

    /**
     * Check the status of a previously submitted signature request.
     *
     * Polls the provider for the current state of the signature workflow.
     * When completed, the response includes the signed digest and signer identity.
     *
     * @param  string $requestId Provider request ID returned by requestSignature().
     * @return array{status: string, signedDigest?: string, signerIdentity?: string}
     *                           Current status and optionally the signed digest
     *                           and identity when completed.
     *
     * @since 1.3.0
     * @author Aythami
     */
    public function checkStatus(string $requestId): array;

    /**
     * Validate a signed digest against the original digest.
     *
     * Verifies that the signed digest corresponds to the original digest
     * using the provider's public key or certificate chain.
     *
     * @param  string $signedDigest   The signed digest result from the provider.
     * @param  string $originalDigest The original hex-encoded digest.
     * @param  string $algorithm      Hash algorithm used (SHA-256, SHA-512).
     * @return bool                   True if the signature is cryptographically valid.
     *
     * @since 1.3.0
     * @author Aythami
     */
    public function validateSignature(string $signedDigest, string $originalDigest, string $algorithm): bool;

    /**
     * Get the unique provider name.
     *
     * Returns a string identifier for this provider implementation
     * (e.g. "clave-firma", "viafirma", "docusign").
     *
     * @return string Provider identifier.
     *
     * @since 1.3.0
     * @author Aythami
     */
    public function getName(): string;
}
