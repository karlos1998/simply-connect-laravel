<?php

namespace SimplyConnect\Laravel\Data;

use DateTimeImmutable;

final readonly class OutgoingCall
{
    public function __construct(
        public string $endpointId,
        public string $flowVersionId,
        public string $destination,
        public string $requestId,
        public ?DateTimeImmutable $scheduledFor = null,
        public string $timeZone = 'Europe/Warsaw',
        public int $intervalSeconds = 30,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'requestId' => $this->requestId,
            'endpointId' => $this->endpointId,
            'flowVersionId' => $this->flowVersionId,
            'destination' => $this->destination,
            'scheduledFor' => $this->scheduledFor?->format('Y-m-d\\TH:i:s'),
            'timeZone' => $this->timeZone,
            'intervalSeconds' => $this->intervalSeconds,
        ];
    }
}
