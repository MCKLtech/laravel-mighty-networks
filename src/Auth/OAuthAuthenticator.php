<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Auth;

use MCKLtech\MightyNetworks\Contracts\TokenStore;
use MCKLtech\MightyNetworks\DataTransferObjects\OAuth\AccessToken;
use MCKLtech\MightyNetworks\Exceptions\AuthenticationException;
use MCKLtech\MightyNetworks\Exceptions\MightyNetworksException;
use MCKLtech\MightyNetworks\Support\OAuthClient;
use Saloon\Contracts\Authenticator;
use Saloon\Http\PendingRequest;

/**
 * Saloon authenticator for the Mighty API's OAuth 2.0 access tokens.
 *
 * Reads the stored token for the connection, transparently refreshes it when it
 * is expired (within a safety margin), persists the rotated refresh token, and
 * attaches `Authorization: Bearer <token>`.
 *
 * When no token is stored, or the refresh is rejected, the user must
 * re-authorize; a rejected refresh surfaces as the SDK's
 * {@see AuthenticationException}.
 */
final class OAuthAuthenticator implements Authenticator
{
    public function __construct(
        private readonly OAuthClient $client,
        private readonly TokenStore $store,
        private readonly string $key = 'default',
    ) {}

    /**
     * {@inheritDoc}
     *
     * @throws MightyNetworksException When no token is stored for the connection.
     */
    #[\Override]
    public function set(PendingRequest $pendingRequest): void
    {
        $token = $this->store->get($this->key);

        if ($token === null || $token->accessToken === '') {
            throw new MightyNetworksException(
                sprintf('No OAuth access token is stored under [%s]; the user must re-authorize.', $this->key),
            );
        }

        if ($token->isExpired()) {
            $token = $this->refresh($token);
        }

        $pendingRequest->headers()->add('Authorization', trim($token->tokenType.' '.$token->accessToken));
    }

    /**
     * Refresh and persist the rotated token pair.
     */
    private function refresh(AccessToken $token): AccessToken
    {
        $refreshed = $this->client->refresh($token);

        $this->store->put($this->key, $refreshed);

        return $refreshed;
    }
}
