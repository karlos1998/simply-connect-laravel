<?php

namespace SimplyConnect\Laravel\Tests;

use Illuminate\Notifications\Notification;
use SimplyConnect\Laravel\Data\OutgoingSms;
use SimplyConnect\Laravel\Facades\SimplyConnect;
use SimplyConnect\Laravel\Notifications\SmsMessage;

final class NotificationChannelTest extends TestCase
{
    public function test_it_sends_a_laravel_notification_through_simply_connect(): void
    {
        SimplyConnect::fake();
        $notifiable = new class
        {
            public function routeNotificationFor(string $driver, ?Notification $notification = null): string
            {
                return '+48500100200';
            }
        };
        $notification = new class extends Notification
        {
            public function toSimplyConnect(object $notifiable): SmsMessage
            {
                return SmsMessage::make('Your verification code is 1842.')
                    ->via('support')
                    ->withIdempotencyKey('verification-1842');
            }
        };

        $receipt = $this->simplyConnectChannel()->send($notifiable, $notification);

        SimplyConnect::assertSmsSentTo(
            '+48500100200',
            fn (OutgoingSms $sms): bool => $sms->endpoint === 'support'
                && $sms->idempotencyKey === 'verification-1842',
        );
        self::assertSame('verification-1842', $receipt->idempotencyKey);
    }
}
