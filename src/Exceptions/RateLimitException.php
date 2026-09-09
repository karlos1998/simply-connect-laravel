<?php

namespace SimplyConnect\Laravel\Exceptions;

final class RateLimitException extends SimplyConnectException
{
    public function __construct(
        string $message,
        public readonly ?int $retryAfter = null,
        ?int $statusCode = null,
        ?string $problemCode = null,
        ?string $correlationId = null,
    ) {
        parent::__construct($message, $statusCode, $problemCode, $correlationId);
    }
}
