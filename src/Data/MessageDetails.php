<?php

namespace SimplyConnect\Laravel\Data;

use DateTimeImmutable;
use SimplyConnect\Laravel\Enums\MessageStatus;

final readonly class MessageDetails
{
    /** @param list<MessageStatusEvent> $statusHistory */
    public function __construct(
        public string $id,
        public string $conversationId,
        public string $direction,
        public string $channel,
        public string $remoteAddress,
        public string $body,
        public DateTimeImmutable $occurredAt,
        public DateTimeImmutable $createdAt,
        public MessageStatus $status,
        public SmsEndpoint $endpoint,
        public array $statusHistory,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $endpoint = $data['endpoint'] ?? null;
        $history = $data['statusHistory'] ?? null;

        if (! is_array($endpoint) || ! is_array($history)) {
            throw new \UnexpectedValueException('Simply Connect message response is incomplete.');
        }

        return new self(
            id: self::string($data, 'id'),
            conversationId: self::string($data, 'conversationId'),
            direction: self::string($data, 'direction'),
            channel: self::string($data, 'channel'),
            remoteAddress: self::string($data, 'remoteAddress'),
            body: self::string($data, 'body'),
            occurredAt: new DateTimeImmutable(self::string($data, 'occurredAt')),
            createdAt: new DateTimeImmutable(self::string($data, 'createdAt')),
            status: MessageStatus::fromApi(self::string($data, 'status')),
            endpoint: SmsEndpoint::fromArray($endpoint),
            statusHistory: array_values(array_map(
                static function (mixed $item): MessageStatusEvent {
                    if (! is_array($item)) {
                        throw new \UnexpectedValueException('Simply Connect status history contains an invalid item.');
                    }

                    return MessageStatusEvent::fromArray($item);
                },
                $history,
            )),
        );
    }

    public function isDelivered(): bool
    {
        return $this->status === MessageStatus::Delivered;
    }

    public function hasFailed(): bool
    {
        return $this->status->hasFailed();
    }

    /** @param array<string, mixed> $data */
    private static function string(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new \UnexpectedValueException("Simply Connect response is missing a valid {$key} field.");
        }

        return $value;
    }
}
