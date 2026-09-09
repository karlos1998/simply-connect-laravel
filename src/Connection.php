<?php

namespace SimplyConnect\Laravel;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use SimplyConnect\Laravel\Contracts\SimplyConnectClient;
use SimplyConnect\Laravel\Data\MessageDetails;
use SimplyConnect\Laravel\Data\OutgoingSms;
use SimplyConnect\Laravel\Data\SmsEndpoint;
use SimplyConnect\Laravel\Data\SmsReceipt;
use SimplyConnect\Laravel\Exceptions\AuthenticationException;
use SimplyConnect\Laravel\Exceptions\ConfigurationException;
use SimplyConnect\Laravel\Exceptions\IdempotencyConflictException;
use SimplyConnect\Laravel\Exceptions\NotFoundException;
use SimplyConnect\Laravel\Exceptions\RateLimitException;
use SimplyConnect\Laravel\Exceptions\ServerException;
use SimplyConnect\Laravel\Exceptions\SimplyConnectException;
use SimplyConnect\Laravel\Exceptions\UnknownOutcomeException;
use SimplyConnect\Laravel\Exceptions\ValidationException;

final class Connection implements SimplyConnectClient
{
    /** @param array<string, mixed> $config */
    public function __construct(
        private readonly Factory $http,
        private readonly array $config,
    ) {}

    public function sms(): PendingSms
    {
        return new PendingSms($this);
    }

    public function sendSms(OutgoingSms $sms): SmsReceipt
    {
        $idempotencyKey = $sms->idempotencyKey ?? (string) Str::uuid();

        try {
            $response = $this->request()
                ->withHeader('Idempotency-Key', $idempotencyKey)
                ->post('/api/v1/external/messages', [
                    'endpointId' => $this->resolveEndpoint($sms->endpoint),
                    'to' => $sms->to,
                    'body' => $sms->text,
                ]);
        } catch (ConnectionException $exception) {
            throw new UnknownOutcomeException(
                'The SMS request lost its response. Retry with the same idempotency key to avoid a duplicate.',
                $idempotencyKey,
                $exception,
            );
        }

        $this->throwForResponse($response);
        $data = $response->json();

        if (! is_array($data)) {
            throw new ServerException('Simply Connect returned an invalid SMS response.', $response->status());
        }

        return SmsReceipt::fromArray($data, $idempotencyKey, $response->header('X-Correlation-Id'));
    }

    public function endpoints(): array
    {
        $response = $this->get('/api/v1/external/endpoints');
        $data = $response->json();

        if (! is_array($data)) {
            throw new ServerException('Simply Connect returned an invalid endpoint response.', $response->status());
        }

        return array_values(array_map(static function (mixed $endpoint): SmsEndpoint {
            if (! is_array($endpoint)) {
                throw new \UnexpectedValueException('Simply Connect returned an invalid endpoint item.');
            }

            return SmsEndpoint::fromArray($endpoint);
        }, $data));
    }

    public function message(string $messageId): MessageDetails
    {
        $response = $this->get('/api/v1/external/messages/'.rawurlencode($messageId));
        $data = $response->json();

        if (! is_array($data)) {
            throw new ServerException('Simply Connect returned an invalid message response.', $response->status());
        }

        return MessageDetails::fromArray($data);
    }

    private function get(string $path): Response
    {
        try {
            $response = $this->request()->get($path);
        } catch (ConnectionException $exception) {
            throw new SimplyConnectException('Could not connect to Simply Connect.', previous: $exception);
        }

        $this->throwForResponse($response);

        return $response;
    }

    private function request(): PendingRequest
    {
        $baseUrl = $this->stringConfig('base_url');
        $apiKey = $this->stringConfig('api_key');

        return $this->http
            ->baseUrl(rtrim($baseUrl, '/'))
            ->acceptJson()
            ->asJson()
            ->withHeader('X-API-Key', $apiKey)
            ->withHeader('X-Correlation-Id', (string) Str::uuid())
            ->timeout($this->integerConfig('timeout', 10))
            ->connectTimeout($this->integerConfig('connect_timeout', 3));
    }

    private function resolveEndpoint(?string $requested): string
    {
        $endpoint = $requested ?? $this->config['default_endpoint'] ?? null;

        if (! is_string($endpoint) || $endpoint === '') {
            throw new ConfigurationException(
                'No SMS endpoint is configured. Set SIMPLY_CONNECT_SMS_ENDPOINT_ID or call via().',
            );
        }

        $aliases = $this->config['endpoints'] ?? [];

        if (is_array($aliases) && array_key_exists($endpoint, $aliases)) {
            $resolved = $aliases[$endpoint];

            if (! is_string($resolved) || $resolved === '') {
                throw new ConfigurationException("Simply Connect endpoint alias [{$endpoint}] is empty.");
            }

            return $resolved;
        }

        return $endpoint;
    }

    private function throwForResponse(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        $data = $response->json();
        $problem = is_array($data) ? $data : [];
        $detail = is_string($problem['detail'] ?? null)
            ? $problem['detail']
            : "Simply Connect request failed with HTTP {$response->status()}.";
        $code = is_string($problem['code'] ?? null) ? $problem['code'] : null;
        $correlationId = is_string($problem['correlationId'] ?? null)
            ? $problem['correlationId']
            : $response->header('X-Correlation-Id');

        throw match ($response->status()) {
            400, 422 => new ValidationException($detail, $response->status(), $code, $correlationId, $problem),
            401, 403 => new AuthenticationException($detail, $response->status(), $code, $correlationId, $problem),
            404 => new NotFoundException($detail, $response->status(), $code, $correlationId, $problem),
            409 => new IdempotencyConflictException($detail, $response->status(), $code, $correlationId, $problem),
            429 => new RateLimitException(
                $detail,
                $this->retryAfter($response),
                $response->status(),
                $code,
                $correlationId,
            ),
            default => $response->serverError()
                ? new ServerException($detail, $response->status(), $code, $correlationId, $problem)
                : new SimplyConnectException($detail, $response->status(), $code, $correlationId, $problem),
        };
    }

    private function retryAfter(Response $response): ?int
    {
        $value = $response->header('Retry-After');

        return ctype_digit($value) ? (int) $value : null;
    }

    private function stringConfig(string $key): string
    {
        $value = $this->config[$key] ?? null;

        if (! is_string($value) || $value === '') {
            throw new ConfigurationException("Simply Connect connection setting [{$key}] is missing.");
        }

        return $value;
    }

    private function integerConfig(string $key, int $default): int
    {
        $value = $this->config[$key] ?? $default;

        return is_int($value) && $value > 0 ? $value : $default;
    }
}
