<?php

namespace SimplyConnect\Laravel\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use SimplyConnect\Laravel\Enums\MessageStatus;
use SimplyConnect\Laravel\Exceptions\IdempotencyConflictException;
use SimplyConnect\Laravel\Exceptions\RateLimitException;

final class ConnectionTest extends TestCase
{
    public function test_it_sends_an_idempotent_sms_through_the_default_endpoint(): void
    {
        Http::fake([
            'api.simply-connect.test/api/v1/external/messages' => Http::response([
                'messageId' => '01994f44-755a-7d10-bb59-597ac6123d5f',
                'dispatchId' => '01994f44-755a-7d10-bb59-597ac6123d60',
                'commandId' => '01994f44-755a-7d10-bb59-597ac6123d61',
                'status' => 'QUEUED',
            ], 202, ['X-Correlation-Id' => 'server-correlation']),
        ]);

        $receipt = $this->simplyConnect()
            ->sms()
            ->to('+48500100200')
            ->text('Your order is ready.')
            ->withIdempotencyKey('order-1842-ready')
            ->send();

        self::assertSame(MessageStatus::Queued, $receipt->status);
        self::assertSame('order-1842-ready', $receipt->idempotencyKey);
        self::assertSame('server-correlation', $receipt->correlationId);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://api.simply-connect.test/api/v1/external/messages'
                && $request->hasHeader('X-API-Key', 'msc_live_test_secret')
                && $request->hasHeader('Idempotency-Key', 'order-1842-ready')
                && $request['endpointId'] === '01994f44-755a-7d10-bb59-597ac6123d50'
                && $request['to'] === '+48500100200'
                && $request['body'] === 'Your order is ready.';
        });
    }

    public function test_it_resolves_a_named_endpoint(): void
    {
        Http::fake([
            '*' => Http::response([
                'messageId' => 'message-id',
                'dispatchId' => 'dispatch-id',
                'commandId' => 'command-id',
                'status' => 'QUEUED',
            ], 202),
        ]);

        $this->simplyConnect()
            ->sms()
            ->via('support')
            ->to('+48500100200')
            ->text('Hello')
            ->send();

        Http::assertSent(fn (Request $request): bool => $request['endpointId'] === '01994f44-755a-7d10-bb59-597ac6123d51');
    }

    public function test_it_maps_idempotency_conflicts_to_a_typed_exception(): void
    {
        Http::fake([
            '*' => Http::response([
                'type' => 'https://simply-connect.ovh/problems/idempotency-key-reused',
                'title' => 'Conflict',
                'status' => 409,
                'detail' => 'Idempotency-Key was already used for a different SMS request',
                'code' => 'IDEMPOTENCY_KEY_REUSED',
                'correlationId' => 'correlation-1',
            ], 409),
        ]);

        try {
            $this->simplyConnect()
                ->sms()
                ->to('+48500100200')
                ->text('Changed text')
                ->withIdempotencyKey('order-1842-ready')
                ->send();

            self::fail('Expected an idempotency exception.');
        } catch (IdempotencyConflictException $exception) {
            self::assertSame(409, $exception->statusCode);
            self::assertSame('IDEMPOTENCY_KEY_REUSED', $exception->problemCode);
            self::assertSame('correlation-1', $exception->correlationId);
        }
    }

    public function test_it_exposes_rate_limit_retry_information(): void
    {
        Http::fake([
            '*' => Http::response([
                'detail' => 'API key rate limit exceeded',
                'code' => 'RATE_LIMITED',
            ], 429, ['Retry-After' => '42']),
        ]);

        $this->expectException(RateLimitException::class);

        try {
            $this->simplyConnect()
                ->sms()
                ->to('+48500100200')
                ->text('Hello')
                ->send();
        } catch (RateLimitException $exception) {
            self::assertSame(42, $exception->retryAfter);

            throw $exception;
        }
    }

    public function test_it_hydrates_endpoints_and_message_status_history(): void
    {
        Http::fake([
            '*/api/v1/external/endpoints' => Http::response([[
                'id' => 'endpoint-id',
                'name' => 'Support SIM',
                'phoneNumber' => '+48511929271',
                'enabled' => true,
            ]]),
            '*/api/v1/external/messages/message-id' => Http::response([
                'id' => 'message-id',
                'conversationId' => 'conversation-id',
                'direction' => 'OUTBOUND',
                'channel' => 'SMS',
                'remoteAddress' => '+48500100200',
                'body' => 'Hello',
                'occurredAt' => '2026-09-09T00:00:00Z',
                'createdAt' => '2026-09-09T00:00:01Z',
                'status' => 'DELIVERED',
                'endpoint' => [
                    'id' => 'endpoint-id',
                    'name' => 'Support SIM',
                    'phoneNumber' => '+48511929271',
                    'enabled' => true,
                ],
                'statusHistory' => [[
                    'status' => 'DELIVERED',
                    'source' => 'GATEWAY',
                    'eventType' => 'SEGMENT_DELIVERED',
                    'errorCode' => null,
                    'occurredAt' => '2026-09-09T00:00:02Z',
                    'recordedAt' => '2026-09-09T00:00:03Z',
                ]],
            ]),
        ]);

        $client = $this->simplyConnect();
        $endpoints = $client->endpoints();
        $message = $client->message('message-id');

        self::assertSame('Support SIM', $endpoints[0]->name);
        self::assertTrue($message->isDelivered());
        self::assertSame('SEGMENT_DELIVERED', $message->statusHistory[0]->eventType);
    }

    public function test_it_maps_future_api_statuses_to_unknown(): void
    {
        Http::fake([
            '*/api/v1/external/messages' => Http::response([
                'messageId' => '01994f44-755a-7d10-bb59-597ac6123d5f',
                'dispatchId' => '01994f44-755a-7d10-bb59-597ac6123d60',
                'commandId' => '01994f44-755a-7d10-bb59-597ac6123d61',
                'status' => 'A_FUTURE_STATUS',
            ], 202),
        ]);

        $receipt = $this->simplyConnect()->sms()
            ->to('+48500100200')
            ->text('Forward compatible')
            ->send();

        self::assertSame(MessageStatus::Unknown, $receipt->status);
    }
}
