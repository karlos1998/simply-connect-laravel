<?php

namespace SimplyConnect\Laravel;

use Illuminate\Http\Client\Factory;
use Illuminate\Support\ServiceProvider;
use SimplyConnect\Laravel\Contracts\SimplyConnectClient;
use SimplyConnect\Laravel\Notifications\SimplyConnectChannel;

final class SimplyConnectServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/simply-connect.php', 'simply-connect');

        $this->app->singleton(SimplyConnectManager::class, fn ($app): SimplyConnectManager => new SimplyConnectManager(
            $app,
            $app->make(Factory::class),
        ));
        $this->app->alias(SimplyConnectManager::class, SimplyConnectClient::class);
        $this->app->singleton(SimplyConnectChannel::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/simply-connect.php' => config_path('simply-connect.php'),
        ], 'simply-connect-config');
    }
}
