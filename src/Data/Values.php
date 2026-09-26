<?php

namespace SimplyConnect\Laravel\Data;

use DateTimeImmutable;

final class Values
{
    /** @param array<string, mixed> $data */
    public static function string(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new \UnexpectedValueException("Simply Connect response is missing a valid {$key} field.");
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    public static function nullableString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** @param array<string, mixed> $data */
    public static function integer(array $data, string $key): int
    {
        $value = $data[$key] ?? null;

        if (! is_int($value)) {
            throw new \UnexpectedValueException("Simply Connect response is missing a valid {$key} field.");
        }

        return $value;
    }

    /** @param array<string, mixed> $data */
    public static function date(array $data, string $key): DateTimeImmutable
    {
        return new DateTimeImmutable(self::string($data, $key));
    }

    /** @param array<string, mixed> $data
     * @return list<mixed>
     */
    public static function list(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        if (! is_array($value) || ! array_is_list($value)) {
            throw new \UnexpectedValueException("Simply Connect response is missing a valid {$key} list.");
        }

        return $value;
    }
}
