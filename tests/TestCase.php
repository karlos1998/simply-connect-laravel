<?php

namespace SimplyConnect\Laravel\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SimplyConnect\Laravel\Contracts\SimplyConnectClient;
use SimplyConnect\Laravel\Notifications\SimplyConnectChannel;
use SimplyConnect\Laravel\SimplyConnectServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [SimplyConnectServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('simply-connect.default', 'default');
        $app['config']->set('simply-connect.connections.default', [
            'base_url' => 'https://api.simply-connect.test',
            'api_key' => 'msc_live_test_secret',
            'default_endpoint' => '01994f44-755a-7d10-bb59-597ac6123d50',
            'endpoints' => [
                'support' => '01994f44-755a-7d10-bb59-597ac6123d51',
            ],
            'timeout' => 10,
            'connect_timeout' => 3,
        ]);
    }

    protected function simplyConnect(): SimplyConnectClient
    {
        if ($this->app === null) {
            throw new \LogicException('The Testbench application has not been created.');
        }

        return $this->app->make(SimplyConnectClient::class);
    }

    protected function simplyConnectChannel(): SimplyConnectChannel
    {
        if ($this->app === null) {
            throw new \LogicException('The Testbench application has not been created.');
        }

        return $this->app->make(SimplyConnectChannel::class);
    }
}
