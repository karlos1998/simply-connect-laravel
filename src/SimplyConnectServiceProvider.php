<?php

namespace SimplyConnect\Laravel;

use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use SimplyConnect\Laravel\Console\InstallCommand;
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

        $this->publishes([
            __DIR__.'/../stubs/SimplyConnectServiceProvider.stub' => app_path('Providers/SimplyConnectServiceProvider.php'),
        ], 'simply-connect-provider');

        if ($this->app->runningInConsole()) {
            $this->commands([InstallCommand::class]);
        }

        if (! config('simply-connect.panel.enabled', false)) {
            return;
        }

        Route::middlewareGroup('simply-connect-panel', config('simply-connect.panel.middleware', ['web']));

        Route::group([
            'domain' => config('simply-connect.panel.domain'),
            'prefix' => trim((string) config('simply-connect.panel.path', 'simply-connect'), '/'),
            'middleware' => 'simply-connect-panel',
        ], function (): void {
            $this->loadRoutesFrom(__DIR__.'/../routes/panel.php');
        });

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'simply-connect');
    }
}
