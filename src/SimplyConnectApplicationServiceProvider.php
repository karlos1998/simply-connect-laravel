<?php

namespace SimplyConnect\Laravel;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class SimplyConnectApplicationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->authorization();
    }

    protected function authorization(): void
    {
        $this->gate();

        SimplyConnectPanel::auth(fn ($request): bool => $this->app->environment('local')
            || Gate::check('viewSimplyConnect', [$request->user()]));
    }

    /**
     * Register the default production gate.
     *
     * Applications should override this method in their published provider.
     */
    protected function gate(): void
    {
        Gate::define('viewSimplyConnect', static fn (): bool => false);
    }
}
