<?php

namespace SimplyConnect\Laravel\Exceptions;

use RuntimeException;
use Throwable;

class SimplyConnectException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        public readonly ?string $problemCode = null,
        public readonly ?string $correlationId = null,
        public readonly array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode ?? 0, $previous);
    }
}
