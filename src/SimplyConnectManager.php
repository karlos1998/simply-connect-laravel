<?php

namespace SimplyConnect\Laravel;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Client\Factory;
use SimplyConnect\Laravel\Contracts\SimplyConnectClient;
use SimplyConnect\Laravel\Data\MessageDetails;
use SimplyConnect\Laravel\Data\OutgoingSms;
use SimplyConnect\Laravel\Data\SmsReceipt;
use SimplyConnect\Laravel\Exceptions\ConfigurationException;
use SimplyConnect\Laravel\Testing\FakeSimplyConnectClient;

final class SimplyConnectManager implements SimplyConnectClient
{
    /** @var array<string, SimplyConnectClient> */
    private array $connections = [];

    public function __construct(
        private readonly Container $container,
        private readonly Factory $http,
    ) {}

    public function connection(?string $name = null): SimplyConnectClient
    {
        $name ??= $this->defaultConnectionName();

        return $this->connections[$name] ??= new Connection($this->http, $this->connectionConfig($name));
    }

    public function sms(): PendingSms
    {
        return new PendingSms($this);
    }

    public function sendSms(OutgoingSms $sms): SmsReceipt
    {
        return $this->connection()->sendSms($sms);
    }

    public function endpoints(): array
    {
        return $this->connection()->endpoints();
    }

    public function message(string $messageId): MessageDetails
    {
        return $this->connection()->message($messageId);
    }

    public function fake(?string $connection = null): FakeSimplyConnectClient
    {
        $name = $connection ?? $this->defaultConnectionName();
        $fake = new FakeSimplyConnectClient;
        $this->connections[$name] = $fake;

        return $fake;
    }

    public function assertSmsSentTo(string $recipient, ?callable $callback = null): void
    {
        $this->fakeConnection()->assertSmsSentTo($recipient, $callback);
    }

    public function assertSmsSentCount(int $count): void
    {
        $this->fakeConnection()->assertSmsSentCount($count);
    }

    public function assertNothingSent(): void
    {
        $this->fakeConnection()->assertNothingSent();
    }

    private function fakeConnection(): FakeSimplyConnectClient
    {
        $connection = $this->connection();

        if (! $connection instanceof FakeSimplyConnectClient) {
            throw new \LogicException('Call SimplyConnect::fake() before making SMS assertions.');
        }

        return $connection;
    }

    private function defaultConnectionName(): string
    {
        $name = $this->config()->get('simply-connect.default', 'default');

        return is_string($name) && $name !== '' ? $name : 'default';
    }

    /** @return array<string, mixed> */
    private function connectionConfig(string $name): array
    {
        $config = $this->config()->get("simply-connect.connections.{$name}");

        if (! is_array($config)) {
            throw new ConfigurationException("Simply Connect connection [{$name}] is not configured.");
        }

        return $config;
    }

    private function config(): Repository
    {
        return $this->container->make(Repository::class);
    }
}
