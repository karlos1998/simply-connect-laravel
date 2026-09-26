<?php

namespace SimplyConnect\Laravel\Data;

final readonly class CallQueueEndpoint
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $phoneNumber,
        public string $gatewayId,
        public string $gatewayName,
        public string $gatewayStatus,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id: Values::string($data, 'id'),
            name: Values::string($data, 'name'),
            phoneNumber: Values::nullableString($data, 'phoneNumber'),
            gatewayId: Values::string($data, 'gatewayId'),
            gatewayName: Values::string($data, 'gatewayName'),
            gatewayStatus: Values::string($data, 'gatewayStatus'),
        );
    }
}
