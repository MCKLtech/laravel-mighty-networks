<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests;

use MCKLtech\MightyNetworks\Connectors\AdminConnector;
use MCKLtech\MightyNetworks\Connectors\GraphQLConnector;
use MCKLtech\MightyNetworks\MightyNetworksManager;
use MCKLtech\MightyNetworks\MightyNetworksServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Saloon\Http\Faking\MockClient;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [MightyNetworksServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('mighty-networks.default', 'default');

        $app['config']->set('mighty-networks.connections.default', [
            'network_id' => '12345',
            'subdomain' => 'test-network',
            'admin_token' => 'test-admin-token',
            'base_url' => 'https://api.mn.co',
            'user_agent' => 'laravel-mighty-networks-tests/1.0',
            'oauth' => [
                'client_id' => 'test-client-id',
                'client_secret' => 'test-client-secret',
                'redirect_uri' => 'https://example.com/callback',
                'scopes' => [],
                'access_token' => 'test-access-token',
            ],
            'retry' => [
                'tries' => null,
                'interval' => null,
                'exponential_backoff' => false,
            ],
            'rate_limits' => [
                'enabled' => false,
                'per_minute' => 60,
                'per_day' => null,
                'store' => null,
            ],
            'cache' => [
                'enabled' => false,
                'store' => null,
                'ttl' => 300,
            ],
        ]);
    }

    protected function manager(): MightyNetworksManager
    {
        return $this->app->make(MightyNetworksManager::class);
    }

    protected function admin(MockClient $mockClient): AdminConnector
    {
        return $this->manager()->connection('default')->admin()->withMockClient($mockClient);
    }

    protected function graphql(MockClient $mockClient): GraphQLConnector
    {
        return $this->manager()->connection('default')->graphql()->withMockClient($mockClient);
    }
}
