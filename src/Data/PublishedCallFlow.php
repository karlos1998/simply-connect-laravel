<?php

namespace SimplyConnect\Laravel\Data;

use DateTimeImmutable;

final readonly class PublishedCallFlow
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description,
        public string $publishedVersionId,
        public DateTimeImmutable $updatedAt,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Values::string($data, 'id'),
            name: Values::string($data, 'name'),
            description: Values::nullableString($data, 'description'),
            publishedVersionId: Values::string($data, 'publishedVersionId'),
            updatedAt: Values::date($data, 'updatedAt'),
        );
    }
}
