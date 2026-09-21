<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Support;

use Illuminate\Contracts\Cache\Repository;
use MCKLtech\MightyNetworks\Contracts\TokenStore;
use MCKLtech\MightyNetworks\DataTransferObjects\OAuth\AccessToken;

/**
 * Laravel-cache-backed {@see TokenStore}.
 *
 * Tokens are stored as plain arrays under a configurable key prefix. When no
 * TTL is configured the entry is stored forever, which suits long-lived refresh
 * tokens; the access token's own expiry is enforced by {@see AccessToken::isExpired()}.
 */
final class CacheTokenStore implements TokenStore
{
    /**
     * @param  string  $prefix  Cache-key prefix, typically derived from the connection name.
     * @param  int|null  $ttl  Lifetime in seconds, or null to store forever.
     */
    public function __construct(
        private readonly Repository $cache,
        private readonly string $prefix = 'mighty-networks.oauth.',
        private readonly ?int $ttl = null,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function get(string $key): ?AccessToken
    {
        $value = $this->cache->get($this->prefix.$key);

        if (! is_array($value)) {
            return null;
        }

        /** @var array<string, mixed> $value */
        return AccessToken::fromArray($value);
    }

    /**
     * {@inheritDoc}
     */
    public function put(string $key, AccessToken $token): void
    {
        if ($this->ttl === null) {
            $this->cache->forever($this->prefix.$key, $token->toArray());

            return;
        }

        $this->cache->put($this->prefix.$key, $token->toArray(), $this->ttl);
    }

    /**
     * {@inheritDoc}
     */
    public function forget(string $key): void
    {
        $this->cache->forget($this->prefix.$key);
    }
}
