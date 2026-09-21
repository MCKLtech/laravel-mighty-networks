<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Contracts;

use MCKLtech\MightyNetworks\DataTransferObjects\OAuth\AccessToken;

/**
 * Persistence for OAuth 2.0 access/refresh token pairs.
 *
 * Refresh tokens rotate on every refresh, so an implementation must overwrite
 * the stored value rather than treat `put` as insert-only. Keys are opaque and
 * scoped per connection by the caller.
 */
interface TokenStore
{
    /**
     * The token stored under `$key`, or null when none has been stored.
     */
    public function get(string $key): ?AccessToken;

    /**
     * Persist (or replace) the token stored under `$key`.
     */
    public function put(string $key, AccessToken $token): void;

    /**
     * Remove any token stored under `$key`.
     */
    public function forget(string $key): void;
}
