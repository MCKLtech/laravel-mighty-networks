<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Connectors;

use Illuminate\Contracts\Cache\Repository;
use MCKLtech\MightyNetworks\Pagination\AdminPagedPaginator;
use Saloon\Contracts\Authenticator;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Request;
use Saloon\Http\Response;
use SensitiveParameter;
use Throwable;

/**
 * Connector for the Mighty Networks Admin REST API.
 *
 * Base URL: `{base_url}/admin/v1`. Every path lives beneath
 * `networks/{network_id}/...` and is authenticated with a long-lived API key
 * sent as `Authorization: Bearer <key>`.
 */
final class AdminConnector extends ApiConnector
{
    /**
     * @param  array<string, mixed>  $retry
     * @param  array<string, mixed>  $rateLimits
     */
    public function __construct(
        #[SensitiveParameter]
        private readonly string $adminToken,
        string $baseUrl = 'https://api.mn.co',
        string $userAgent = self::DEFAULT_USER_AGENT,
        array $retry = [],
        array $rateLimits = [],
        ?Repository $cache = null,
    ) {
        parent::__construct(
            baseUrl: $baseUrl,
            userAgent: $userAgent,
            retry: $retry,
            rateLimits: $rateLimits,
            cache: $cache,
        );
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveBaseUrl(): string
    {
        return $this->normalizedBaseUrl().'/admin/v1';
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    protected function defaultAuth(): Authenticator
    {
        return new TokenAuthenticator($this->adminToken);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function getRequestException(Response $response, ?Throwable $senderException): ?Throwable
    {
        return $this->httpException($response);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function paginate(Request $request): AdminPagedPaginator
    {
        return new AdminPagedPaginator($this, $request);
    }
}
