<p align="center">
  <img src="docs/simply-connect-mark.svg" width="112" alt="Simply Connect">
</p>

<h1 align="center">Simply Connect dla Laravel</h1>

[![Testy](https://github.com/karlos1998/simply-connect-laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/karlos1998/simply-connect-laravel/actions/workflows/tests.yml)
[![Najnowsza wersja](https://img.shields.io/packagist/v/simply-connect/laravel.svg)](https://packagist.org/packages/simply-connect/laravel)
[![PHP](https://img.shields.io/packagist/php-v/simply-connect/laravel.svg)](https://packagist.org/packages/simply-connect/laravel)
[![Licencja](https://img.shields.io/packagist/l/simply-connect/laravel.svg)](LICENSE.md)

Oficjalna, wygodna biblioteka Laravel dla [External API Simply Connect](https://simply-connect.ovh). Wysyłaj SMS-y
przez telefony Android i bramki modemowe, korzystając ze znajomych konwencji Laravela.

[English](README.md) · **Polski** · [Dokumentacja API](https://api.simply-connect.ovh/api/external/docs)

```php
use SimplyConnect\Laravel\Facades\SimplyConnect;

$receipt = SimplyConnect::sms()
    ->to('+48500100200')
    ->text('Twoje zamówienie jest gotowe do odbioru.')
    ->send();
```

## Dlaczego pasuje do Laravela

- płynne wysyłanie SMS-ów z rozsądnymi wartościami domyślnymi;
- automatyczne wykrywanie pakietu i konfiguracja przez zmienne środowiskowe;
- natywny kanał Laravel Notifications i obsługa kolejek;
- nazwane połączenia oraz czytelne aliasy endpointów;
- typowane, niemutowalne odpowiedzi i enumy statusów;
- bezpieczna idempotencja oraz jawna obsługa nieznanego wyniku;
- osobne wyjątki dla autoryzacji, walidacji, limitów i błędów serwera;
- pełny fake z wygodnymi asercjami testowymi;
- brak automatycznych ponowień, które mogłyby zdublować SMS.

## Wymagania

| Pakiet | Obsługiwane wersje |
| --- | --- |
| PHP | 8.2, 8.3, 8.4+ |
| Laravel | 10, 11, 12, 13 |

Klucz API Simply Connect potrzebuje uprawnienia `SMS_SEND`. Do odczytu statusu doręczenia dodaj `MESSAGES_READ`.

## Instalacja

Zainstaluj pakiet przez Composer:

```bash
composer require simply-connect/laravel
```

Laravel automatycznie wykryje Service Provider i fasadę. Dodaj klucz API oraz domyślny endpoint SMS do `.env`:

```dotenv
SIMPLY_CONNECT_API_KEY=msc_live_twoj_sekret
SIMPLY_CONNECT_SMS_ENDPOINT_ID=01994f44-755a-7d10-bb59-597ac6123d50
```

Klucz utworzysz w panelu Simply Connect w **Developer → Klucze API**. Sekret jest wyświetlany tylko raz — przechowuj
go w menedżerze sekretów i nigdy nie zapisuj w repozytorium.

Opublikuj konfigurację tylko wtedy, gdy potrzebujesz wielu połączeń lub nazwanych endpointów:

```bash
php artisan vendor:publish --tag=simply-connect-config
```

## Wysyłanie SMS

Domyślny endpoint pochodzi z `SIMPLY_CONNECT_SMS_ENDPOINT_ID`:

```php
$receipt = SimplyConnect::sms()
    ->to($customer->phone_number)
    ->text("Zamówienie {$order->number} zostało wysłane.")
    ->send();

$receipt->messageId;
$receipt->dispatchId;
$receipt->commandId;
$receipt->status;          // MessageStatus::Queued
$receipt->idempotencyKey;  // wygenerowany dla tego żądania
$receipt->correlationId;   // przydatny przy kontakcie ze wsparciem
```

### Nazwane endpointy

Przypisz UUID-om bramek trwałe nazwy w `config/simply-connect.php`:

```php
'endpoints' => [
    'support' => env('SIMPLY_CONNECT_SUPPORT_ENDPOINT_ID'),
    'notifications' => env('SIMPLY_CONNECT_NOTIFICATIONS_ENDPOINT_ID'),
],
```

Teraz możesz wybrać numer nadawczy bez umieszczania UUID-ów w kodzie biznesowym:

```php
SimplyConnect::sms()
    ->via('support')
    ->to('+48500100200')
    ->text('Oddzwonimy do Ciebie w ciągu 15 minut.')
    ->send();
```

`via()` przyjmuje także surowy UUID. Endpointy dostępne dla bieżącego klucza pobierzesz tak:

```php
$endpoints = SimplyConnect::endpoints();

foreach ($endpoints as $endpoint) {
    echo $endpoint->name;
    echo $endpoint->phoneNumber;
    echo $endpoint->enabled ? 'gotowy' : 'wyłączony';
}
```

### Obiekty domenowe jako odbiorcy

Zaimplementuj `HasSmsNumber`, aby przekazywać obiekt bezpośrednio:

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
    ->text('Dziękujemy za rejestrację.')
    ->send();
```

## Laravel Notifications

Zwróć `SmsMessage` z metody `toSimplyConnect()`:

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
        return SmsMessage::make("Zamówienie {$this->orderNumber} zostało wysłane.")
            ->via('notifications')
            ->withIdempotencyKey("order-{$this->orderNumber}-shipped");
    }
}
```

Udostępnij numer w modelu odbiorcy:

```php
public function routeNotificationForSimplyConnect(): string
{
    return $this->phone_number;
}
```

Powiadomienie wysyłasz standardowo:

```php
$customer->notify(new OrderShipped($order->number));
```

Laravel umieści je w kolejce, gdy implementuje `ShouldQueue`. Worker konfigurujesz tak samo jak dla innych powiadomień.

## Dependency injection

Fasada nie jest obowiązkowa. Wstrzyknij publiczny kontrakt do serwisu aplikacyjnego:

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
            ->text("Twój kod weryfikacyjny to {$code}.")
            ->send();
    }
}
```

## Status doręczenia

Klucz API potrzebuje uprawnienia `MESSAGES_READ`:

```php
use SimplyConnect\Laravel\Enums\MessageStatus;

$message = SimplyConnect::message($receipt->messageId);

if ($message->status === MessageStatus::Delivered) {
    // Bramka zgłosiła prawidłowe doręczenie.
}

if ($message->hasFailed()) {
    // FAILED, UNKNOWN lub DEAD_LETTER wymaga decyzji aplikacji.
}

foreach ($message->statusHistory as $event) {
    logger()->info('Status SMS', [
        'status' => $event->status->value,
        'source' => $event->source,
        'error_code' => $event->errorCode,
        'occurred_at' => $event->occurredAt,
    ]);
}
```

`UNKNOWN` jest celowym statusem: platforma nie potrafi udowodnić, czy operator wykonał operację. Nie należy zamieniać go
w automatyczne ponowienie wysyłki.

## Idempotencja i problemy sieciowe

Biblioteka generuje `Idempotency-Key` dla każdej wysyłki. Podaj własny klucz biznesowy, gdy operacja może zostać
ponowiona przez kolejkę lub komendę:

```php
SimplyConnect::sms()
    ->to($customer->phone_number)
    ->text('Twoja faktura jest dostępna.')
    ->withIdempotencyKey("invoice-{$invoice->id}-available")
    ->send();
```

Jeśli po wysłaniu żądania zniknie połączenie, biblioteka rzuci `UnknownOutcomeException`. Ponów operację wyłącznie z
kluczem z wyjątku oraz identycznym endpointem, odbiorcą i treścią:

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

Ponowne użycie klucza z inną treścią rzuci `IdempotencyConflictException` i nie utworzy kolejnego SMS-a.

## Obsługa błędów

```php
use SimplyConnect\Laravel\Exceptions\AuthenticationException;
use SimplyConnect\Laravel\Exceptions\RateLimitException;
use SimplyConnect\Laravel\Exceptions\ValidationException;

try {
    $receipt = SimplyConnect::sms()->to($phone)->text($text)->send();
} catch (AuthenticationException $exception) {
    // Nieprawidłowy klucz, brak uprawnienia lub niedozwolony endpoint.
} catch (ValidationException $exception) {
    // Nieprawidłowy odbiorca albo treść żądania.
} catch (RateLimitException $exception) {
    $exception->retryAfter;    // liczba sekund, jeśli API ją zwróciło
    $exception->correlationId;
}
```

Wszystkie wyjątki dziedziczą po `SimplyConnectException` i udostępniają `statusCode`, `problemCode`, `correlationId`
oraz oczyszczoną tablicę `context`.

## Testowanie

Po włączeniu fake'a nie zostanie wykonane żadne żądanie HTTP:

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

Dostępna jest również asercja `SimplyConnect::assertNothingSent()`.

## Wiele kont Simply Connect

Dodaj kolejne połączenia w opublikowanej konfiguracji i wybierz właściwe:

```php
SimplyConnect::connection('client-a')
    ->sms()
    ->to('+48500100200')
    ->text('Wiadomość od klienta A.')
    ->send();
```

## Bezpieczeństwo

- Przechowuj klucze API w zmiennych środowiskowych lub menedżerze sekretów.
- Nadawaj tylko niezbędne uprawnienia i dostęp do właściwych endpointów.
- Nie loguj nagłówków, treści SMS ani pełnych numerów telefonów.
- Po podejrzeniu wycieku natychmiast obróć klucz.
- Podatności zgłaszaj prywatnie przez GitHub; zobacz [SECURITY.md](SECURITY.md).

## Rozwój pakietu

Instrukcje znajdują się w [CONTRIBUTING.md](CONTRIBUTING.md). Pełną kontrolę jakości uruchomisz poleceniem:

```bash
composer check
```

## Licencja

Simply Connect dla Laravel jest dostępny na [licencji MIT](LICENSE.md).
