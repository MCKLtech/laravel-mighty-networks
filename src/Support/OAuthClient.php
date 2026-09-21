<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Support;

use MCKLtech\MightyNetworks\Auth\OAuthAuthenticator;
use MCKLtech\MightyNetworks\Auth\OAuthConnector;
use MCKLtech\MightyNetworks\Auth\Requests\ExchangeAuthorizationCodeRequest;
use MCKLtech\MightyNetworks\Auth\Requests\RefreshAccessTokenRequest;
use MCKLtech\MightyNetworks\DataTransferObjects\OAuth\AccessToken;
use MCKLtech\MightyNetworks\Exceptions\AuthenticationException;
use MCKLtech\MightyNetworks\Exceptions\MightyNetworksException;
use Saloon\Http\Response;
use SensitiveParameter;

/**
 * Client for a Network's OAuth 2.0 authorization server.
 *
 * Implements the Authorization Code + PKCE flow and the refresh-token flow
 * against the community host (`https://{subdomain}.mn.co`). Access tokens are
 * short-lived; refresh tokens rotate, so persist the returned pair after every
 * refresh — {@see OAuthAuthenticator} does this
 * automatically when it renews an expired token before a request.
 */
final class OAuthClient
{
    /**
     * @param  list<string>  $scopes  Default scopes used when {@see authorizationUrl()} is not given any.
     */
    public function __construct(
        private readonly OAuthConnector $connector,
        #[SensitiveParameter]
        private readonly string $clientId,
        #[SensitiveParameter]
        private readonly ?string $clientSecret = null,
        private readonly ?string $redirectUri = null,
        private readonly array $scopes = [],
    ) {}

    /**
     * The connector backing the OAuth endpoints (useful for mocking and discovery).
     */
    public function connector(): OAuthConnector
    {
        return $this->connector;
    }

    /**
     * Build the URL to redirect the user to for authorization.
     *
     * Generate `$codeChallenge` with {@see Pkce::challenge()} from a verifier you
     * hold; pass that same verifier to {@see exchangeCode()}. Always send and
     * verify a cryptographically random `$state`.
     *
     * @param  list<string>  $scopes
     */
    public function authorizationUrl(string $state, ?string $codeChallenge = null, array $scopes = []): string
    {
        $scopes = $scopes !== [] ? $scopes : $this->scopes;

        $query = [
            'response_type' => 'code',
            'client_id' => $this->clientId,
            'state' => $state,
        ];

        if ($this->redirectUri !== null && $this->redirectUri !== '') {
            $query['redirect_uri'] = $this->redirectUri;
        }

        if ($scopes !== []) {
            $query['scope'] = implode(' ', $scopes);
        }

        if ($codeChallenge !== null && $codeChallenge !== '') {
            $query['code_challenge'] = $codeChallenge;
            $query['code_challenge_method'] = 'S256';
        }

        return $this->connector->authorizationEndpoint().'?'.http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Exchange an authorization code for a token pair.
     *
     * @throws AuthenticationException When the token endpoint rejects the request.
     */
    public function exchangeCode(string $code, ?string $codeVerifier = null): AccessToken
    {
        $response = $this->connector->send(new ExchangeAuthorizationCodeRequest(
            clientId: $this->clientId,
            clientSecret: $this->clientSecret,
            redirectUri: $this->redirectUri,
            code: $code,
            codeVerifier: $codeVerifier ?? '',
        ));

        return $this->tokenFrom($response);
    }

    /**
     * Exchange a refresh token for a new token pair.
     *
     * @throws MightyNetworksException When the token has no refresh token.
     * @throws AuthenticationException When the authorization server rejects the refresh (re-authorization required).
     */
    public function refresh(AccessToken $token): AccessToken
    {
        if (! $token->isRefreshable()) {
            throw new MightyNetworksException(
                'The stored OAuth token has no refresh token; the user must re-authorize.',
            );
        }

        $response = $this->connector->send(new RefreshAccessTokenRequest(
            clientId: $this->clientId,
            clientSecret: $this->clientSecret,
            refreshToken: (string) $token->refreshToken,
        ));

        return $this->tokenFrom($response);
    }

    /**
     * Decode a token-endpoint response, mapping failures onto the SDK exceptions.
     *
     * @throws AuthenticationException
     */
    private function tokenFrom(Response $response): AccessToken
    {
        if ($response->failed()) {
            throw new AuthenticationException($response, 'The OAuth token endpoint rejected the request.');
        }

        // Decode defensively: a malformed body from the token endpoint must
        // surface as the documented AuthenticationException, not a raw
        // \JsonException escaping from Saloon's json() helper.
        $data = json_decode($response->body(), true);

        if (! is_array($data) || ! isset($data['access_token']) || ! is_string($data['access_token']) || $data['access_token'] === '') {
            throw new AuthenticationException($response, 'The OAuth token response did not contain an access token.');
        }

        /** @var array<string, mixed> $data */
        return AccessToken::fromArray($data);
    }
}
