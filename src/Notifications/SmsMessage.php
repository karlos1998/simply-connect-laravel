<?php

namespace SimplyConnect\Laravel\Notifications;

final class SmsMessage
{
    public ?string $recipient = null;

    public ?string $endpoint = null;

    public ?string $idempotencyKey = null;

    public ?string $connection = null;

    private function __construct(public string $content) {}

    public static function make(string $content): self
    {
        return new self($content);
    }

    public function to(string $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function via(string $endpoint): self
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    public function onConnection(string $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    public function withIdempotencyKey(string $key): self
    {
        $this->idempotencyKey = $key;

        return $this;
    }
}
