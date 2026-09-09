<p align="center">
  <img src="docs/simply-connect-mark.svg" width="112" alt="Simply Connect">
</p>

<h1 align="center">Simply Connect for Laravel</h1>

[![Tests](https://github.com/karlos1998/simply-connect-laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/karlos1998/simply-connect-laravel/actions/workflows/tests.yml)
[![Latest Version](https://img.shields.io/packagist/v/simply-connect/laravel.svg)](https://packagist.org/packages/simply-connect/laravel)
[![PHP](https://img.shields.io/packagist/php-v/simply-connect/laravel.svg)](https://packagist.org/packages/simply-connect/laravel)
[![License](https://img.shields.io/packagist/l/simply-connect/laravel.svg)](LICENSE.md)

The official, expressive Laravel SDK for the [Simply Connect](https://simply-connect.ovh) External API. Send SMS
messages through your Android phones and modem gateways using familiar Laravel conventions.

**English** · [Polski](README.pl.md) · [API documentation](https://api.simply-connect.ovh/api/external/docs)

```php
use SimplyConnect\Laravel\Facades\SimplyConnect;

$receipt = SimplyConnect::sms()
    ->to('+48500100200')
    ->text('Your order is ready for collection.')
    ->send();
```

## Why it feels at home in Laravel

- fluent SMS sending with sensible defaults;
- automatic package discovery and environment-based configuration;
- native Laravel notification channel and queue support;
- named connections and friendly endpoint aliases;
- typed, immutable responses and delivery-status enums;
- safe idempotency keys with explicit unknown-outcome handling;
- focused exceptions for authentication, validation, rate limits and server failures;
- a first-class fake with expressive test assertions;
- no automatic retry that could accidentally duplicate an SMS.

## Requirements

| Package | Supported versions |
| --- | --- |
| PHP | 8.2, 8.3, 8.4+ |
| Laravel | 11, 12, 13 |

Your Simply Connect API key needs `SMS_SEND`. Add `MESSAGES_READ` if the application retrieves delivery status.

## Installation

Install the package through Composer:

```bash
composer require simply-connect/laravel
```

Laravel discovers the service provider and facade automatically. Add the API key and default SMS endpoint to `.env`:

```dotenv
SIMPLY_CONNECT_API_KEY=msc_live_your_secret
SIMPLY_CONNECT_SMS_ENDPOINT_ID=01994f44-755a-7d10-bb59-597ac6123d50
```

Create API keys in **Developer → API keys** in the Simply Connect panel. The secret is shown once; store it in your
secret manager and never commit it.

Publish the configuration only when you need multiple connections or named endpoints:

```bash
php artisan vendor:publish --tag=simply-connect-config
```

## Sending SMS

The default endpoint comes from `SIMPLY_CONNECT_SMS_ENDPOINT_ID`:

```php
$receipt = SimplyConnect::sms()
    ->to($customer->phone_number)
    ->text("Order {$order->number} has been shipped.")
    ->send();

$receipt->messageId;
$receipt->dispatchId;
$receipt->commandId;
$receipt->status;          // MessageStatus::Queued
$receipt->idempotencyKey;  // generated for this request
$receipt->correlationId;   // useful when contacting support
```

### Named endpoints

Give gateway UUIDs stable names in `config/simply-connect.php`:

```php
'endpoints' => [
    'support' => env('SIMPLY_CONNECT_SUPPORT_ENDPOINT_ID'),
    'notifications' => env('SIMPLY_CONNECT_NOTIFICATIONS_ENDPOINT_ID'),
],
```

Then select a sender without leaking UUIDs into business code:

```php
SimplyConnect::sms()
    ->via('support')
    ->to('+48500100200')
    ->text('We will call you back within 15 minutes.')
    ->send();
```

`via()` also accepts a raw endpoint UUID. List endpoints available to the current API key with:

```php
$endpoints = SimplyConnect::endpoints();

foreach ($endpoints as $endpoint) {
    echo $endpoint->name;
    echo $endpoint->phoneNumber;
    echo $endpoint->enabled ? 'ready' : 'disabled';
}
```

### Domain objects as recipients

Implement `HasSmsNumber` to pass a domain object directly:

```php
use SimplyConnect\Laravel\Contracts\HasSmsNumber;

final class Customer implements HasSmsNumber
{
    public function smsNumber(): string
    {
        return $this->phone_number;
    }
}

SimplyConnect::sms()
    ->to($customer)
    ->text('Thank you for registering.')
    ->send();
```

## Laravel Notifications

Return an `SmsMessage` from `toSimplyConnect()`:

```php
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use SimplyConnect\Laravel\Notifications\SimplyConnectChannel;
use SimplyConnect\Laravel\Notifications\SmsMessage;

final class OrderShipped extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $orderNumber) {}

    public function via(object $notifiable): array
    {
        return [SimplyConnectChannel::class];
    }

    public function toSimplyConnect(object $notifiable): SmsMessage
    {
        return SmsMessage::make("Order {$this->orderNumber} has been shipped.")
            ->via('notifications')
            ->withIdempotencyKey("order-{$this->orderNumber}-shipped");
    }
}
```

Expose the route on your notifiable model:

```php
public function routeNotificationForSimplyConnect(): string
{
    return $this->phone_number;
}
```

Send it like every other Laravel notification:

```php
$customer->notify(new OrderShipped($order->number));
```

Laravel queues the notification when it implements `ShouldQueue`. Configure and run your queue worker as usual.

## Dependency injection

The facade is optional. Inject the public contract into application services:

```php
use SimplyConnect\Laravel\Contracts\SimplyConnectClient;

final class SendVerificationCode
{
    public function __construct(private SimplyConnectClient $simplyConnect) {}

    public function handle(string $phone, string $code): void
    {
        $this->simplyConnect
            ->sms()
            ->to($phone)
            ->text("Your verification code is {$code}.")
            ->send();
    }
}
```

## Delivery status

The API key needs `MESSAGES_READ`:

```php
use SimplyConnect\Laravel\Enums\MessageStatus;

$message = SimplyConnect::message($receipt->messageId);

if ($message->status === MessageStatus::Delivered) {
    // The gateway reported successful delivery.
}

if ($message->hasFailed()) {
    // FAILED, UNKNOWN or DEAD_LETTER needs application-specific handling.
}

foreach ($message->statusHistory as $event) {
    logger()->info('SMS status evidence', [
        'status' => $event->status->value,
        'source' => $event->source,
        'error_code' => $event->errorCode,
        'occurred_at' => $event->occurredAt,
    ]);
}
```

`UNKNOWN` is intentional: the platform cannot prove whether the carrier performed the side effect. Do not turn it into
an automatic retry.

## Idempotency and network failures

The SDK generates an `Idempotency-Key` for every send. Provide a business key when the same operation can be retried by
a queue or command:

```php
SimplyConnect::sms()
    ->to($customer->phone_number)
    ->text('Your invoice is available.')
    ->withIdempotencyKey("invoice-{$invoice->id}-available")
    ->send();
```

If the connection disappears after submission, the SDK throws `UnknownOutcomeException`. Retry only with the key from
the exception and the exact same endpoint, recipient and body:

```php
use SimplyConnect\Laravel\Exceptions\UnknownOutcomeException;

try {
    $receipt = SimplyConnect::sms()
        ->to($phone)
        ->text($text)
        ->send();
} catch (UnknownOutcomeException $exception) {
    RetrySms::dispatch($phone, $text, $exception->idempotencyKey);
}
```

Reusing a key with different content throws `IdempotencyConflictException` and never queues another SMS.

## Error handling

```php
use SimplyConnect\Laravel\Exceptions\AuthenticationException;
use SimplyConnect\Laravel\Exceptions\RateLimitException;
use SimplyConnect\Laravel\Exceptions\ValidationException;

try {
    $receipt = SimplyConnect::sms()->to($phone)->text($text)->send();
} catch (AuthenticationException $exception) {
    // Invalid/expired key, missing scope or forbidden endpoint.
} catch (ValidationException $exception) {
    // Invalid recipient or request content.
} catch (RateLimitException $exception) {
    $exception->retryAfter;    // seconds, when supplied by the API
    $exception->correlationId;
}
```

All package exceptions extend `SimplyConnectException` and expose `statusCode`, `problemCode`, `correlationId` and a
sanitized `context` array.

## Testing

No HTTP request is performed after enabling the fake:

```php
use SimplyConnect\Laravel\Data\OutgoingSms;
use SimplyConnect\Laravel\Facades\SimplyConnect;

SimplyConnect::fake();

$this->post('/orders/1842/notify');

SimplyConnect::assertSmsSentTo(
    '+48500100200',
    fn (OutgoingSms $sms): bool => $sms->endpoint === 'notifications'
        && str_contains($sms->text, '1842'),
);

SimplyConnect::assertSmsSentCount(1);
```

You can also use `SimplyConnect::assertNothingSent()`.

## Multiple Simply Connect accounts

Define additional connections in the published configuration, then select one explicitly:

```php
SimplyConnect::connection('client-a')
    ->sms()
    ->to('+48500100200')
    ->text('Hello from client A.')
    ->send();
```

## Security

- Keep API keys in environment variables or a secret manager.
- Grant only the required scopes and endpoint access.
- Never log request headers, SMS bodies or complete phone numbers.
- Rotate a key immediately after suspected exposure.
- Report vulnerabilities through GitHub private vulnerability reporting; see [SECURITY.md](SECURITY.md).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). Run the full quality gate with:

```bash
composer check
```

## License

Simply Connect for Laravel is open-source software licensed under the [MIT license](LICENSE.md).
