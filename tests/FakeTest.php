<?php

namespace SimplyConnect\Laravel\Tests;

use SimplyConnect\Laravel\Contracts\HasSmsNumber;
use SimplyConnect\Laravel\Data\OutgoingSms;
use SimplyConnect\Laravel\Facades\SimplyConnect;

final class FakeTest extends TestCase
{
    public function test_it_records_sms_messages_without_network_requests(): void
    {
        SimplyConnect::fake();

        $receipt = SimplyConnect::sms()
            ->via('support')
            ->to('+48500100200')
            ->text('Test message')
            ->send();

        SimplyConnect::assertSmsSentCount(1);
        SimplyConnect::assertSmsSentTo(
            '+48500100200',
            fn (OutgoingSms $sms): bool => $sms->endpoint === 'support' && $sms->text === 'Test message',
        );
        self::assertSame('QUEUED', $receipt->status->value);
    }

    public function test_it_can_assert_that_nothing_was_sent(): void
    {
        SimplyConnect::fake();

        SimplyConnect::assertNothingSent();
    }

    public function test_it_accepts_domain_recipients_and_reuses_the_builder_idempotency_key(): void
    {
        $fake = SimplyConnect::fake();
        $recipient = new class implements HasSmsNumber
        {
            public function smsNumber(): string
            {
                return '+48500100200';
            }
        };
        $sms = SimplyConnect::sms()->to($recipient)->text('One logical operation');

        $first = $sms->send();
        $second = $sms->send();

        self::assertSame($first->idempotencyKey, $second->idempotencyKey);
        self::assertSame($first->idempotencyKey, $fake->sent()[0]->idempotencyKey);
        self::assertSame($fake->sent()[0]->idempotencyKey, $fake->sent()[1]->idempotencyKey);
    }
}
