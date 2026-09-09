<?php

namespace SimplyConnect\Laravel\Exceptions;

use Throwable;

final class UnknownOutcomeException extends SimplyConnectException
{
    public function __construct(
        string $message,
        public readonly string $idempotencyKey,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, context: ['idempotencyKey' => $idempotencyKey], previous: $previous);
    }
}
