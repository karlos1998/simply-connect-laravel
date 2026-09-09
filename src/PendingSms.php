<?php

namespace SimplyConnect\Laravel;

use Illuminate\Support\Str;
use SimplyConnect\Laravel\Contracts\HasSmsNumber;
use SimplyConnect\Laravel\Contracts\SimplyConnectClient;
use SimplyConnect\Laravel\Data\OutgoingSms;
use SimplyConnect\Laravel\Data\SmsReceipt;
use SimplyConnect\Laravel\Exceptions\ValidationException;

final class PendingSms
{
    private ?string $to = null;

    private ?string $text = null;

    private ?string $endpoint = null;

    private ?string $idempotencyKey = null;

    public function __construct(private readonly SimplyConnectClient $client) {}

    public function to(string|HasSmsNumber $recipient): self
    {
        $this->to = $recipient instanceof HasSmsNumber ? $recipient->smsNumber() : $recipient;

        return $this;
    }

    public function text(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function via(string $endpoint): self
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    public function withIdempotencyKey(string $key): self
    {
        $this->idempotencyKey = $key;

        return $this;
    }

    public function send(): SmsReceipt
    {
        if ($this->to === null || trim($this->to) === '') {
            throw new ValidationException('An SMS recipient is required. Call to() before send().');
        }

        if ($this->text === null || trim($this->text) === '') {
            throw new ValidationException('SMS text is required. Call text() before send().');
        }

        $this->idempotencyKey ??= (string) Str::uuid();

        if (trim($this->idempotencyKey) === '' || mb_strlen($this->idempotencyKey) > 200) {
            throw new ValidationException('The SMS idempotency key must contain between 1 and 200 characters.');
        }

        return $this->client->sendSms(new OutgoingSms(
            to: $this->to,
            text: $this->text,
            endpoint: $this->endpoint,
            idempotencyKey: $this->idempotencyKey,
        ));
    }
}
