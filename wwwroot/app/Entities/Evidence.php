<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

/**
 * Evidence entity — canonicalized, immutable append-only record.
 *
 * Never edited. Common fields: eventId, eventType, schemaVersion,
 * occurredAt, aggregateId, correlationId, causationId.
 * Evidence records are incorporated into the internal ledger via hashes.
 *
 * @property string      $id                   UUID v4
 * @property string      $eventId              Unique event UUID
 * @property string      $eventType            Event type (e.g. LoginSuccess, TransferSent)
 * @property string      $schemaVersion        Schema version
 * @property string      $occurredAt           Exact event timestamp
 * @property string      $aggregateType        Aggregate type (User, Document, etc.)
 * @property string      $aggregateId          Aggregate root UUID
 * @property string|null $correlationId        Correlation ID for distributed tracing
 * @property string|null $causationId          Causing event ID
 * @property string|null $actorId              Actor user UUID
 * @property string|null $actorType            Actor type (user, system, provider, worker)
 * @property string      $payloadJson          Minimal payload (no PII, keys, or content)
 * @property string      $payloadHash          SHA-256 of canonicalized payload
 * @property string|null $ipAddressTruncated   Truncated IP for privacy
 * @property string|null $userAgentTruncated   Truncated User-Agent
 * @property int|null    $ledgerBlockNumber    Ledger block number where incorporated
 * @property string      $createdAt            Creation timestamp
 *
 * @since 1.1.1
 * @author Aythami Melián Perdomo <ajmelper@gmail.com>
 */
class Evidence extends Entity
{
    protected $casts = [
        'id'                 => 'string',
        'eventId'            => 'string',
        'eventType'          => 'string',
        'schemaVersion'      => 'string',
        'occurredAt'         => 'datetime',
        'aggregateType'      => 'string',
        'aggregateId'        => 'string',
        'correlationId'      => '?string',
        'causationId'        => '?string',
        'actorId'            => '?string',
        'actorType'          => '?string',
        'payloadJson'        => 'string',
        'payloadHash'        => 'string',
        'ipAddressTruncated' => '?string',
        'userAgentTruncated' => '?string',
        'ledgerBlockNumber'  => '?int',
        'createdAt'          => 'datetime',
    ];

    protected $datamap = [
        'event_id'              => 'eventId',
        'event_type'            => 'eventType',
        'schema_version'        => 'schemaVersion',
        'occurred_at'           => 'occurredAt',
        'aggregate_type'        => 'aggregateType',
        'aggregate_id'          => 'aggregateId',
        'correlation_id'        => 'correlationId',
        'causation_id'          => 'causationId',
        'actor_id'              => 'actorId',
        'actor_type'            => 'actorType',
        'payload_json'          => 'payloadJson',
        'payload_hash'          => 'payloadHash',
        'ip_address_truncated'  => 'ipAddressTruncated',
        'user_agent_truncated'  => 'userAgentTruncated',
        'ledger_block_number'   => 'ledgerBlockNumber',
        'created_at'            => 'createdAt',
    ];

    /**
     * Check if this evidence has been incorporated into the ledger.
     */
    public function isInLedger(): bool
    {
        return $this->ledgerBlockNumber !== null;
    }

    /**
     * Check if this evidence was triggered by a user action.
     */
    public function isUserAction(): bool
    {
        return $this->actorType === 'user';
    }

    /**
     * Check if this evidence was triggered by the system.
     */
    public function isSystemAction(): bool
    {
        return $this->actorType === 'system';
    }
}
