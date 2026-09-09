<?php

namespace SimplyConnect\Laravel\Data;

use SimplyConnect\Laravel\Enums\MessageStatus;

final readonly class SmsReceipt
{
    public function __construct(
        public string $messageId,
        public string $dispatchId,
        public string $commandId,
        public MessageStatus $status,
        public string $idempotencyKey,
        public ?string $correlationId = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data, string $idempotencyKey, ?string $correlationId = null): self
    {
        return new self(
            messageId: self::string($data, 'messageId'),
            dispatchId: self::string($data, 'dispatchId'),
            commandId: self::string($data, 'commandId'),
            status: MessageStatus::fromApi(self::string($data, 'status')),
            idempotencyKey: $idempotencyKey,
            correlationId: $correlationId,
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
}
