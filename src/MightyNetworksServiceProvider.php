<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use MCKLtech\MightyNetworks\Contracts\TokenStore;
use MCKLtech\MightyNetworks\Support\CacheTokenStore;

final class MightyNetworksServiceProvider extends ServiceProvider
{
    /**
     * Register the package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/mighty-networks.php',
            'mighty-networks',
        );

        $this->app->singleton(
            MightyNetworksManager::class,
            static fn (Application $app): MightyNetworksManager => new MightyNetworksManager($app),
        );

        $this->app->alias(MightyNetworksManager::class, 'mighty-networks');

        $this->app->bind(TokenStore::class, static function (Application $app): TokenStore {
            $connection = config('mighty-networks.default', 'default');

            $oauth = config(sprintf(
                'mighty-networks.connections.%s.oauth',
                is_string($connection) && $connection !== '' ? $connection : 'default',
            ));

            $oauth = is_array($oauth) ? $oauth : [];

            $store = $oauth['store'] ?? null;
            $ttl = $oauth['token_ttl'] ?? null;

            $cache = $app->make(CacheFactory::class);

            return new CacheTokenStore(
                cache: $cache->store(is_string($store) && $store !== '' ? $store : null),
                ttl: is_int($ttl) ? $ttl : null,
            );
        });
    }

    /**
     * Bootstrap the package services.
     */
    public function boot(): void
    {
        $this->publishes(
            [__DIR__.'/../config/mighty-networks.php' => config_path('mighty-networks.php')],
            'mighty-networks-config',
        );

        $this->registerWebhookRoutes();
    }

    /**
     * Webhook routes are only loaded when the feature is enabled, so consuming
     * applications opt in explicitly.
     */
    private function registerWebhookRoutes(): void
    {
        if (config('mighty-networks.webhooks.enabled', false) !== true) {
            return;
        }

        $routes = __DIR__.'/../routes/webhooks.php';

        if (file_exists($routes)) {
            $this->loadRoutesFrom($routes);
        }
    }
}
