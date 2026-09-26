<?php

namespace SimplyConnect\Laravel\Testing;

use DateTimeImmutable;
use Illuminate\Support\Str;
use PHPUnit\Framework\Assert;
use SimplyConnect\Laravel\Contracts\SimplyConnectClient;
use SimplyConnect\Laravel\Data\CallQueueEndpoint;
use SimplyConnect\Laravel\Data\CallQueueItem;
use SimplyConnect\Laravel\Data\CallQueuePage;
use SimplyConnect\Laravel\Data\MessageDetails;
use SimplyConnect\Laravel\Data\MessagePage;
use SimplyConnect\Laravel\Data\OutgoingCall;
use SimplyConnect\Laravel\Data\OutgoingSms;
use SimplyConnect\Laravel\Data\PublishedCallFlow;
use SimplyConnect\Laravel\Data\SmsEndpoint;
use SimplyConnect\Laravel\Data\SmsReceipt;
use SimplyConnect\Laravel\Enums\MessageStatus;
use SimplyConnect\Laravel\Exceptions\NotFoundException;
use SimplyConnect\Laravel\PendingSms;

final class FakeSimplyConnectClient implements SimplyConnectClient
{
    /** @var list<OutgoingSms> */
    private array $sent = [];

    /** @var list<SmsEndpoint> */
    private array $endpoints = [];

    /** @var array<string, MessageDetails> */
    private array $messages = [];

    /** @var list<CallQueueEndpoint> */
    private array $callQueueEndpoints = [];

    /** @var list<PublishedCallFlow> */
    private array $publishedCallFlows = [];

    /** @var list<CallQueueItem> */
    private array $queuedCalls = [];

    /** @var list<OutgoingCall> */
    private array $queuedCallRequests = [];

    public function sms(): PendingSms
    {
        return new PendingSms($this);
    }

    public function sendSms(OutgoingSms $sms): SmsReceipt
    {
        $this->sent[] = $sms;

        return new SmsReceipt(
            messageId: (string) Str::uuid(),
            dispatchId: (string) Str::uuid(),
            commandId: (string) Str::uuid(),
            status: MessageStatus::Queued,
            idempotencyKey: $sms->idempotencyKey ?? (string) Str::uuid(),
        );
    }

    public function endpoints(): array
    {
        return $this->endpoints;
    }

    public function message(string $messageId): MessageDetails
    {
        return $this->messages[$messageId]
            ?? throw new NotFoundException("Fake Simply Connect message [{$messageId}] was not provided.", 404);
    }

    public function messages(array $filters = []): MessagePage
    {
        return new MessagePage([], 0, 25, 0, 0);
    }

    public function callQueueEndpoints(): array
    {
        return $this->callQueueEndpoints;
    }

    public function publishedCallFlows(): array
    {
        return $this->publishedCallFlows;
    }

    public function callQueue(array $filters = []): CallQueuePage
    {
        return new CallQueuePage($this->queuedCalls, 0, count($this->queuedCalls), $this->queuedCalls === [] ? 0 : 1);
    }

    public function queueCall(OutgoingCall $call): CallQueueItem
    {
        $now = new DateTimeImmutable;
        $created = new CallQueueItem(
            id: (string) Str::uuid(),
            endpointId: $call->endpointId,
            flowVersionId: $call->flowVersionId,
            flowName: 'Fake flow',
            flowVersion: 1,
            destination: $call->destination,
            source: 'API',
            status: 'QUEUED',
            notBefore: $call->scheduledFor ?? $now,
            timeZone: $call->timeZone,
            intervalSeconds: $call->intervalSeconds,
            createdAt: $now,
            updatedAt: $now,
            callId: null,
            reason: null,
        );

        $this->queuedCallRequests[] = $call;
        $this->queuedCalls[] = $created;

        return $created;
    }

    /** @param list<SmsEndpoint> $endpoints */
    public function withEndpoints(array $endpoints): self
    {
        $this->endpoints = $endpoints;

        return $this;
    }

    public function withMessage(MessageDetails $message): self
    {
        $this->messages[$message->id] = $message;

        return $this;
    }

    /** @return list<OutgoingSms> */
    public function sent(): array
    {
        return $this->sent;
    }

    public function assertSmsSentTo(string $recipient, ?callable $callback = null): void
    {
        $matches = array_filter(
            $this->sent,
            static fn (OutgoingSms $sms): bool => $sms->to === $recipient && ($callback === null || $callback($sms) === true),
        );

        Assert::assertNotEmpty($matches, "No SMS matching recipient [{$recipient}] was sent.");
    }

    public function assertSmsSentCount(int $count): void
    {
        Assert::assertCount($count, $this->sent, "Expected {$count} SMS messages to be sent.");
    }

    public function assertNothingSent(): void
    {
        Assert::assertCount(0, $this->sent, 'Expected no SMS messages to be sent.');
    }

    public function assertCallQueuedTo(string $destination, ?callable $callback = null): void
    {
        $matches = array_filter(
            $this->queuedCallRequests,
            static fn (OutgoingCall $call): bool => $call->destination === $destination
                && ($callback === null || $callback($call) === true),
        );

        Assert::assertNotEmpty($matches, "No call matching destination [{$destination}] was queued.");
    }

    public function assertCallQueuedCount(int $count): void
    {
        Assert::assertCount($count, $this->queuedCallRequests, "Expected {$count} calls to be queued.");
    }
}
