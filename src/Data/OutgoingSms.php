<?php

namespace SimplyConnect\Laravel\Data;

final readonly class OutgoingSms
{
    public function __construct(
        public string $to,
        public string $text,
        public ?string $endpoint = null,
        public ?string $idempotencyKey = null,
    ) {}
}
