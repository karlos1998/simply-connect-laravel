<?php

namespace SimplyConnect\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'simply-connect:install')]
final class InstallCommand extends Command
{
    protected $signature = 'simply-connect:install';

    protected $description = 'Install the Simply Connect configuration and developer panel provider';

    public function handle(): int
    {
        $this->comment('Publishing Simply Connect configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'simply-connect-config']);

        $this->comment('Publishing Simply Connect application provider...');
        $this->callSilent('vendor:publish', ['--tag' => 'simply-connect-provider']);

        $this->registerApplicationProvider();

        $this->info('Simply Connect scaffolding installed successfully.');

        return self::SUCCESS;
    }

    private function registerApplicationProvider(): void
    {
        $provider = $this->laravel->getNamespace().'Providers\\SimplyConnectServiceProvider';

        $bootstrapRegistration = [ServiceProvider::class, 'addProviderToBootstrapFile'];
        $serviceProvider = new ReflectionClass(ServiceProvider::class);

        if ($serviceProvider->hasMethod('addProviderToBootstrapFile') && $bootstrapRegistration($provider)) {
            return;
        }

        $configPath = config_path('app.php');
        $config = file_get_contents($configPath);

        if (! is_string($config) || Str::contains($config, $provider.'::class')) {
            return;
        }

        $namespace = Str::replaceLast('\\', '', $this->laravel->getNamespace());
        $anchor = $namespace.'\\Providers\\RouteServiceProvider::class,';
        $replacement = $anchor.PHP_EOL.'        '.$provider.'::class,';

        file_put_contents($configPath, Str::replace($anchor, $replacement, $config));

        $publishedProvider = app_path('Providers/SimplyConnectServiceProvider.php');
        $providerContents = file_get_contents($publishedProvider);

        if (is_string($providerContents)) {
            file_put_contents($publishedProvider, Str::replace(
                'namespace App\\Providers;',
                "namespace {$namespace}\\Providers;",
                $providerContents,
            ));
        }
    }
}
