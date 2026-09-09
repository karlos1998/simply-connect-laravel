<?php

namespace SimplyConnect\Laravel\Data;

final readonly class SmsEndpoint
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $phoneNumber,
        public bool $enabled,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $phoneNumber = $data['phoneNumber'] ?? null;

        return new self(
            id: self::string($data, 'id'),
            name: self::string($data, 'name'),
            phoneNumber: is_string($phoneNumber) ? $phoneNumber : null,
            enabled: ($data['enabled'] ?? false) === true,
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
