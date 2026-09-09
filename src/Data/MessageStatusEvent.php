<?php

namespace SimplyConnect\Laravel\Data;

use DateTimeImmutable;
use SimplyConnect\Laravel\Enums\MessageStatus;

final readonly class MessageStatusEvent
{
    public function __construct(
        public MessageStatus $status,
        public string $source,
        public ?string $eventType,
        public ?string $errorCode,
        public DateTimeImmutable $occurredAt,
        public DateTimeImmutable $recordedAt,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            status: MessageStatus::fromApi(self::string($data, 'status')),
            source: self::string($data, 'source'),
            eventType: self::nullableString($data['eventType'] ?? null),
            errorCode: self::nullableString($data['errorCode'] ?? null),
            occurredAt: new DateTimeImmutable(self::string($data, 'occurredAt')),
            recordedAt: new DateTimeImmutable(self::string($data, 'recordedAt')),
        );
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

    private static function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }
}
