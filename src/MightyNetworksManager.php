<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks;

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Manager;
use InvalidArgumentException;
use SensitiveParameter;

/**
 * Resolves named Mighty Networks connections from configuration.
 */
final class MightyNetworksManager extends Manager
{
    private readonly ConfigRepository $configRepository;

    public function __construct(Container $container)
    {
        parent::__construct($container);

        $config = $container->make('config');

        if (! $config instanceof ConfigRepository) {
            throw new InvalidArgumentException('Unable to resolve the configuration repository.');
        }

        $this->configRepository = $config;

        foreach (array_keys($this->connectionConfigs()) as $name) {
            $this->extend($name, fn (): MightyNetworks => $this->createConnection($name));
        }
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function getDefaultDriver(): string
    {
        $default = $this->configRepository->get('mighty-networks.default', 'default');

        return is_string($default) && $default !== '' ? $default : 'default';
    }

    /**
     * Resolve a named connection (or the default when none is given).
     */
    public function connection(?string $name = null): MightyNetworks
    {
        $driver = $this->driver($name);

        if (! $driver instanceof MightyNetworks) {
            throw new InvalidArgumentException('The resolved Mighty Networks driver is not a connection instance.');
        }

        return $driver;
    }

    /**
     * Build an ad-hoc connection from explicit credentials.
     *
     * Non-credential settings are inherited from a configured connection — the
     * default one, unless `$basedOn` names another. Secrets are **never**
     * inherited: both `admin_token` and `oauth.access_token` are stripped from
     * the base config, so a runtime tenant can never pick up another network's
     * credentials. OAuth *application* credentials (`client_id`,
     * `client_secret`, `redirect_uri`, `scopes`) are inherited, since those are
     * normally shared app-wide.
     *
     * @param  array<string, mixed>  $overrides
     */
    public function withCredentials(
        #[SensitiveParameter]
        ?string $adminToken,
        int|string $networkId,
        array $overrides = [],
        ?string $basedOn = null,
    ): MightyNetworks {
        $base = $this->connectionConfigs()[$basedOn ?? $this->getDefaultDriver()] ?? [];

        return MightyNetworks::make(
            adminToken: $adminToken,
            networkId: $networkId,
            overrides: array_replace_recursive($this->withoutSecrets($base), $overrides),
            cache: $this->resolveCacheRepository($base),
        );
    }

    /**
     * Build an ad-hoc connection from a complete config array.
     *
     * Use this when the whole connection definition is stored somewhere other
     * than the config file (a database column, a JSON payload, a test fixture).
     *
     * @param  array<string, mixed>  $config
     */
    public function withConfig(array $config, string $name = 'runtime'): MightyNetworks
    {
        return new MightyNetworks($config, $name, $this->resolveCacheRepository($config));
    }

    /**
     * Strip secrets from an inherited connection config.
     *
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function withoutSecrets(array $config): array
    {
        unset($config['admin_token']);

        $oauth = $config['oauth'] ?? null;

        if (is_array($oauth)) {
            unset($oauth['access_token']);

            $config['oauth'] = $oauth;
        }

        return $config;
    }

    /**
     * All configured connections.
     *
     * @return array<string, array<string, mixed>>
     */
    private function connectionConfigs(): array
    {
        $connections = $this->configRepository->get('mighty-networks.connections', []);

        if (! is_array($connections)) {
            return [];
        }

        $configs = [];

        foreach ($connections as $name => $config) {
            if (is_string($name) && is_array($config)) {
                $configs[$name] = $config;
            }
        }

        return $configs;
    }

    private function createConnection(string $name): MightyNetworks
    {
        $config = $this->connectionConfigs()[$name] ?? null;

        if ($config === null) {
            throw new InvalidArgumentException(
                sprintf('The [%s] Mighty Networks connection is not configured.', $name),
            );
        }

        return new MightyNetworks($config, $name, $this->resolveCacheRepository($config));
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function resolveCacheRepository(array $config): ?Repository
    {
        $factory = $this->container->make('cache');

        if (! $factory instanceof CacheManager) {
            return null;
        }

        $cache = $config['cache'] ?? null;

        $store = is_array($cache) ? ($cache['store'] ?? null) : null;

        return is_string($store) && $store !== ''
            ? $factory->store($store)
            : $factory->store();
    }
}
