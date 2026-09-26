<?php

namespace SimplyConnect\Laravel\Tests;

use Illuminate\Support\Facades\Http;
use SimplyConnect\Laravel\SimplyConnectApplicationServiceProvider;
use SimplyConnect\Laravel\SimplyConnectServiceProvider;

final class CustomPanelPathTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            SimplyConnectApplicationServiceProvider::class,
            SimplyConnectServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['env'] = 'local';
        $app['config']->set('simply-connect.panel.enabled', true);
        $app['config']->set('simply-connect.panel.path', 'tools/connect');
    }

    public function test_panel_path_is_configurable(): void
    {
        Http::fake(['*' => Http::response([], 403)]);

        $this->get('/simply-connect')->assertNotFound();
        $this->get('/tools/connect')->assertOk();
    }
}
