<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Timestamp provider contract.
 *
 * Each external timestamping authority MUST implement this interface.
 * The provider receives only a hash digest of the data to be timestamped
 * (typically the manifestHash), NEVER the clear document, DEK, CID, or
 * private keys.
 *
 * Supports RFC 3161 Time-Stamp Protocol (TSP) or equivalent.
 *
 * @package App\Services
 * @since   1.3.0
 * @author  Aythami
 */
interface TimestampProviderInterface
{
    /**
     * Request a timestamp token for the given hash.
     *
     * Submits the hash to the external Time-Stamping Authority (TSA)
     * and returns the timestamp token and metadata.
     *
     * @param  string $hash      Hex-encoded hash of the data to timestamp.
     * @param  string $algorithm Hash algorithm used (default: SHA-256).
     * @return array             Timestamp response containing:
     *                           - tokenId:     string
     *                           - timestampToken: string (base64-encoded DER)
     *                           - time:        int (Unix epoch seconds)
     *                           - accuracy:    ?int (precision in seconds)
     *                           - policyOid:   ?string
     *
     * @throws \RuntimeException If the TSA service is unavailable or rejects the request.
     *
     * @since 1.3.0
     * @author Aythami
     */
    public function requestTimestamp(string $hash, string $algorithm = 'SHA-256'): array;

    /**
     * Validate a timestamp token against the original hash.
     *
     * Verifies the cryptographic integrity of the timestamp token,
     * checks the TSA certificate chain, and confirms that the token
     * covers the provided hash.
     *
     * @param  string $timestampToken Base64-encoded DER timestamp token.
     * @param  string $hash           Original hex-encoded hash to verify.
     * @return bool                   True if the timestamp token is valid
     *                                and covers the given hash.
     *
     * @since 1.3.0
     * @author Aythami
     */
    public function validateTimestamp(string $timestampToken, string $hash): bool;

    /**
     * Get the unique provider name.
     *
     * Returns a string identifier for this TSA implementation
     * (e.g. "fnmt-tsa", "dgt-tsa", "letsencrypt-ct").
     *
     * @return string Provider identifier.
     *
     * @since 1.3.0
     * @author Aythami
     */
    public function getName(): string;
}
