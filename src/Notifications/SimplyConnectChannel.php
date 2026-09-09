<?php

namespace SimplyConnect\Laravel\Notifications;

use Illuminate\Notifications\Notification;
use SimplyConnect\Laravel\Data\SmsReceipt;
use SimplyConnect\Laravel\Exceptions\ValidationException;
use SimplyConnect\Laravel\SimplyConnectManager;

final class SimplyConnectChannel
{
    public function __construct(private readonly SimplyConnectManager $manager) {}

    public function send(object $notifiable, Notification $notification): SmsReceipt
    {
        if (! is_callable([$notification, 'toSimplyConnect'])) {
            throw new ValidationException(
                sprintf('Notification [%s] must define a toSimplyConnect() method.', $notification::class),
            );
        }

        $message = call_user_func([$notification, 'toSimplyConnect'], $notifiable);

        if (is_string($message)) {
            $message = SmsMessage::make($message);
        }

        if (! $message instanceof SmsMessage) {
            throw new ValidationException('toSimplyConnect() must return an SmsMessage or string.');
        }

        $recipient = $message->recipient ?? $this->route($notifiable, $notification);
        $pending = $this->manager->connection($message->connection)
            ->sms()
            ->to($recipient)
            ->text($message->content);

        if ($message->endpoint !== null) {
            $pending->via($message->endpoint);
        }

        if ($message->idempotencyKey !== null) {
            $pending->withIdempotencyKey($message->idempotencyKey);
        }

        return $pending->send();
    }

    private function route(object $notifiable, Notification $notification): string
    {
        if (! is_callable([$notifiable, 'routeNotificationFor'])) {
            throw new ValidationException('The notifiable does not provide a Simply Connect SMS route.');
        }

        $route = call_user_func([$notifiable, 'routeNotificationFor'], 'simply-connect', $notification);

        if (! is_string($route) || trim($route) === '') {
            throw new ValidationException('The Simply Connect notification route must be a phone number.');
        }

        return $route;
    }
}
