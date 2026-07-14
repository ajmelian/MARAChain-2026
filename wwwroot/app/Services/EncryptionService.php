<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Encryption service — manages the marachain-envelope format.
 *
 * The backend NEVER receives the Data Encryption Key (DEK).
 * All encryption happens client-side (WebCrypto).
 * The backend only stores ciphertext, envelopes, and CIDs.
 *
 * The marachain-envelope follows this structure:
 * ```json
 * {
 *   "format": "marachain-envelope",
 *   "version": 1,
 *   "contentCipher": "AES-256-GCM",
 *   "manifestHash": "sha256:...",
 *   "recipients": []
 * }
 * ```
 *
 * @package App\Services
 * @since   1.3.0
 * @author  Aythami
 */
class EncryptionService
{
    private const string FORMAT = 'marachain-envelope';
    private const int VERSION = 1;

    /**
     * Validate the structure of a marachain-envelope.
     *
     * Checks that the envelope contains all required keys and that
     * format and version match the expected values.
     *
     * @param  array<string, mixed> $envelope The envelope to validate.
     * @return bool                           True if envelope structure is valid.
     *
     * @since 1.3.0
     * @author Aythami
     */
    public function validateEnvelope(array $envelope): bool
    {
        $required = ['format', 'version', 'contentCipher', 'manifestHash', 'recipients'];

        foreach ($required as $key) {
            if (! isset($envelope[$key])) {
                return false;
            }
        }

        if ($envelope['format'] !== self::FORMAT) {
            return false;
        }

        if ($envelope['version'] !== self::VERSION) {
            return false;
        }

        return true;
    }

    /**
     * Extract the recipient envelope for a specific user.
     *
     * Iterates the recipients array and returns the envelope matching
     * the given userId, or null if no match is found.
     *
     * @param  array  $envelope Full marachain-envelope.
     * @param  string $userId   Recipient UUID to look up.
     * @return array|null       The recipient envelope, or null if not found.
     *
     * @since 1.3.0
     * @author Aythami
     */
    public function getRecipientEnvelope(array $envelope, string $userId): ?array
    {
        foreach ($envelope['recipients'] ?? [] as $recipient) {
            if (($recipient['userId'] ?? '') === $userId) {
                return $recipient;
            }
        }

        return null;
    }

    /**
     * Verify that the manifest hash matches the canonicalized JSON.
     *
     * Computes the SHA-256 hash of the provided JSON string and
     * compares it against the expected hash using timing-safe comparison.
     *
     * @param  string $expectedHash SHA-256 hex-encoded hash from envelope.
     * @param  string $manifestJson Canonicalized JSON string (RFC 8785).
     * @return bool                 True if the hash matches.
     *
     * @since 1.3.0
     * @author Aythami
     */
    public function verifyManifestHash(string $expectedHash, string $manifestJson): bool
    {
        return hash_equals($expectedHash, hash('sha256', $manifestJson));
    }
}
