<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Auth;

use Saloon\Http\Connector;

/**
 * Connector for a Network's OAuth 2.0 authorization server.
 *
 * OAuth endpoints are served from the Network's community host
 * (`https://{subdomain}.mn.co`), not from `api.mn.co`.
 */
final class OAuthConnector extends Connector
{
    public function __construct(
        private readonly string $subdomain,
    ) {}

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveBaseUrl(): string
    {
        return rtrim(sprintf('https://%s.mn.co', $this->subdomain), '/');
    }

    /**
     * The RFC 8414 discovery document URL.
     */
    public function discoveryUrl(): string
    {
        return $this->resolveBaseUrl().'/.well-known/oauth-authorization-server';
    }

    /**
     * The user-facing authorization endpoint.
     */
    public function authorizationEndpoint(): string
    {
        return $this->resolveBaseUrl().'/oauth/authorize';
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, string>
     */
    #[\Override]
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
        ];
    }
}
