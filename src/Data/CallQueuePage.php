<?php

namespace SimplyConnect\Laravel\Data;

final readonly class CallQueuePage
{
    /** @param list<CallQueueItem> $items */
    public function __construct(
        public array $items,
        public int $page,
        public int $totalElements,
        public int $totalPages,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            items: array_map(static function (mixed $item): CallQueueItem {
                if (! is_array($item)) {
                    throw new \UnexpectedValueException('Simply Connect call queue contains an invalid item.');
                }

                return CallQueueItem::fromArray($item);
            }, Values::list($data, 'items')),
            page: Values::integer($data, 'page'),
            totalElements: Values::integer($data, 'totalElements'),
            totalPages: Values::integer($data, 'totalPages'),
        );
    }
}
