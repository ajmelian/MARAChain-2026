<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * Signature request entity for delegated electronic signature.
 *
 * The signing provider receives exclusively the digest (hash of the
 * canonicalized manifest). It never receives the clear document, DEK,
 * private keys, CID, or envelopes.
 *
 * @property string      $id                    UUID v4
 * @property string      $documentId            Document UUID
 * @property string      $userId                Signing user UUID
 * @property string      $signatureIntent       Operation type (e.g. document_send)
 * @property string      $status                CREATED|PROVIDER_REQUESTED|...|EXPIRED
 * @property int         $manifestVersion       Manifest schema version
 * @property string      $manifestJson          Canonicalized manifest JSON (RFC 8785)
 * @property string      $manifestHash          SHA-256 of canonicalized manifest
 * @property string      $digestAlgorithm       Hash algorithm used
 * @property string      $signatureProvider     Signature provider used
 * @property string|null $providerRequestId     Provider request ID
 * @property string|null $providerResponseJson  Provider response (sensitive)
 * @property string|null $signedDigest          Signed digest result (sensitive)
 * @property string|null $signerIdentity        Signer identity from provider
 * @property string|null $signatureAlgorithm    Signature algorithm used
 * @property string      $nonce                 Replay-prevention nonce (64 chars)
 * @property string      $issuedAt              Issue timestamp
 * @property string      $expiresAt             Expiration (single-use)
 * @property string|null $completedAt           Completion timestamp
 * @property string|null $failedAt              Failure timestamp
 * @property string|null $failureReason         Failure reason (max 500 chars)
 * @property string      $createdAt             Creation timestamp
 * @property string      $updatedAt             Last update timestamp
 *
 * @since 1.1.1
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
class SignatureRequest extends Entity
{
    protected $casts = [
        'id'                   => 'string',
        'documentId'           => 'string',
        'userId'               => 'string',
        'signatureIntent'      => 'string',
        'status'               => 'string',
        'manifestVersion'      => 'int',
        'manifestJson'         => 'string',
        'manifestHash'         => 'string',
        'digestAlgorithm'      => 'string',
        'signatureProvider'    => 'string',
        'providerRequestId'    => '?string',
        'providerResponseJson' => '?string',
        'signedDigest'         => '?string',
        'signerIdentity'       => '?string',
        'signatureAlgorithm'   => '?string',
        'nonce'                => 'string',
        'issuedAt'             => 'datetime',
        'expiresAt'            => 'datetime',
        'completedAt'          => '?datetime',
        'failedAt'             => '?datetime',
        'failureReason'        => '?string',
        'createdAt'            => 'datetime',
        'updatedAt'            => 'datetime',
    ];

    protected $datamap = [
        'document_id'             => 'documentId',
        'user_id'                 => 'userId',
        'signature_intent'        => 'signatureIntent',
        'manifest_version'        => 'manifestVersion',
        'manifest_json'           => 'manifestJson',
        'manifest_hash'           => 'manifestHash',
        'digest_algorithm'        => 'digestAlgorithm',
        'signature_provider'      => 'signatureProvider',
        'provider_request_id'     => 'providerRequestId',
        'provider_response_json'  => 'providerResponseJson',
        'signed_digest'           => 'signedDigest',
        'signer_identity'         => 'signerIdentity',
        'signature_algorithm'     => 'signatureAlgorithm',
        'issued_at'               => 'issuedAt',
        'expires_at'              => 'expiresAt',
        'completed_at'            => 'completedAt',
        'failed_at'               => 'failedAt',
        'failure_reason'          => 'failureReason',
        'created_at'              => 'createdAt',
        'updated_at'              => 'updatedAt',
    ];

    /**
     * Check if the signature request is still pending completion.
     */
    public function isPending(): bool
    {
        $pending = ['CREATED', 'PROVIDER_REQUESTED'];

        return in_array($this->status, $pending, true);
    }

    /**
     * Check if the signature request has been completed successfully.
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, ['PROVIDER_COMPLETED', 'VALIDATED', 'CONSUMED'], true);
    }

    /**
     * Check if the signature request has failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'FAILED';
    }

    /**
     * Check if the signature request has expired.
     */
    public function isExpired(): bool
    {
        if ($this->status === 'EXPIRED') {
            return true;
        }

        if ($this->expiresAt !== null) {
            return strtotime((string) $this->expiresAt) < time();
        }

        return false;
    }

    /**
     * Check if the nonce has already been consumed.
     */
    public function isConsumed(): bool
    {
        return $this->status === 'CONSUMED';
    }
}
