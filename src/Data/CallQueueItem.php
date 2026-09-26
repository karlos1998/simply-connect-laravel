<?php

namespace SimplyConnect\Laravel\Data;

use DateTimeImmutable;

final readonly class CallQueueItem
{
    public function __construct(
        public string $id,
        public string $endpointId,
        public string $flowVersionId,
        public string $flowName,
        public int $flowVersion,
        public string $destination,
        public string $source,
        public string $status,
        public DateTimeImmutable $notBefore,
        public string $timeZone,
        public int $intervalSeconds,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public ?string $callId,
        public ?string $reason,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Values::string($data, 'id'),
            endpointId: Values::string($data, 'endpointId'),
            flowVersionId: Values::string($data, 'flowVersionId'),
            flowName: Values::string($data, 'flowName'),
            flowVersion: Values::integer($data, 'flowVersion'),
            destination: Values::string($data, 'destination'),
            source: Values::string($data, 'source'),
            status: Values::string($data, 'status'),
            notBefore: Values::date($data, 'notBefore'),
            timeZone: Values::string($data, 'timeZone'),
            intervalSeconds: Values::integer($data, 'intervalSeconds'),
            createdAt: Values::date($data, 'createdAt'),
            updatedAt: Values::date($data, 'updatedAt'),
            callId: Values::nullableString($data, 'callId'),
            reason: Values::nullableString($data, 'reason'),
        );
    }
}
