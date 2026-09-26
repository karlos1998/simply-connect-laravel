<?php

namespace SimplyConnect\Laravel\Data;

use DateTimeImmutable;
use SimplyConnect\Laravel\Enums\MessageStatus;

final readonly class MessageSummary
{
    public function __construct(
        public string $id,
        public string $conversationId,
        public string $direction,
        public string $channel,
        public string $remoteAddress,
        public string $body,
        public DateTimeImmutable $occurredAt,
        public MessageStatus $status,
        public SmsEndpoint $endpoint,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $endpoint = $data['endpoint'] ?? null;

        if (! is_array($endpoint)) {
            throw new \UnexpectedValueException('Simply Connect message response is missing its endpoint.');
        }

        return new self(
            id: Values::string($data, 'id'),
            conversationId: Values::string($data, 'conversationId'),
            direction: Values::string($data, 'direction'),
            channel: Values::string($data, 'channel'),
            remoteAddress: Values::string($data, 'remoteAddress'),
            body: Values::string($data, 'body'),
            occurredAt: Values::date($data, 'occurredAt'),
            status: MessageStatus::fromApi(Values::string($data, 'status')),
            endpoint: SmsEndpoint::fromArray($endpoint),
        );
    }
}
