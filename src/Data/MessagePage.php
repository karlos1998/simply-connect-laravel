<?php

namespace SimplyConnect\Laravel\Data;

final readonly class MessagePage
{
    /** @param list<MessageSummary> $items */
    public function __construct(
        public array $items,
        public int $page,
        public int $size,
        public int $totalElements,
        public int $totalPages,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            items: array_map(static function (mixed $item): MessageSummary {
                if (! is_array($item)) {
                    throw new \UnexpectedValueException('Simply Connect message page contains an invalid item.');
                }

                return MessageSummary::fromArray($item);
            }, Values::list($data, 'items')),
            page: Values::integer($data, 'page'),
            size: Values::integer($data, 'size'),
            totalElements: Values::integer($data, 'totalElements'),
            totalPages: Values::integer($data, 'totalPages'),
        );
    }
}
