<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Connectors;

use Illuminate\Contracts\Cache\Repository;
use MCKLtech\MightyNetworks\Auth\OAuthAuthenticator;
use MCKLtech\MightyNetworks\Contracts\TokenStore;
use MCKLtech\MightyNetworks\Exceptions\AuthenticationException;
use MCKLtech\MightyNetworks\Exceptions\ComplexityException;
use MCKLtech\MightyNetworks\Exceptions\ForbiddenException;
use MCKLtech\MightyNetworks\Exceptions\GraphQLException;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Exceptions\RateLimitException;
use MCKLtech\MightyNetworks\Exceptions\ValidationException;
use MCKLtech\MightyNetworks\Pagination\Contracts\GraphQLCursorPaginatable;
use MCKLtech\MightyNetworks\Pagination\GraphQLCursorPaginator;
use MCKLtech\MightyNetworks\Support\OAuthClient;
use Saloon\Contracts\Authenticator;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Request;
use Saloon\Http\Response;
use SensitiveParameter;
use Throwable;

/**
 * Connector for the Mighty Networks GraphQL (Mighty API).
 *
 * Requests POST to `/networks/{network}/graphql`. Unlike REST, GraphQL failures
 * arrive with HTTP 200 and a non-empty top-level `errors` array, so this
 * connector overrides `hasRequestFailed()` instead of trusting the status code.
 */
final class GraphQLConnector extends ApiConnector
{
    /**
     * @param  array<string, mixed>  $retry
     * @param  array<string, mixed>  $rateLimits
     */
    public function __construct(
        #[SensitiveParameter]
        private readonly string $accessToken = '',
        string $baseUrl = 'https://api.mn.co',
        string $userAgent = self::DEFAULT_USER_AGENT,
        array $retry = [],
        array $rateLimits = [],
        ?Repository $cache = null,
        private readonly ?OAuthClient $oauth = null,
        private readonly ?TokenStore $tokenStore = null,
        private readonly string $oauthKey = 'default',
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
        return $this->normalizedBaseUrl();
    }

    /**
     * {@inheritDoc}
     *
     * When an OAuth client and token store are configured, requests use the
     * stored (and transparently refreshed) OAuth access token. Otherwise the
     * static `access_token` from configuration is used as a Bearer token, which
     * keeps the simple token path working.
     */
    #[\Override]
    protected function defaultAuth(): Authenticator
    {
        if ($this->oauth !== null && $this->tokenStore !== null) {
            return new OAuthAuthenticator($this->oauth, $this->tokenStore, $this->oauthKey);
        }

        return new TokenAuthenticator($this->accessToken);
    }

    /**
     * Treat a HTTP 200 response containing GraphQL `errors` as a failure.
     */
    #[\Override]
    public function hasRequestFailed(Response $response): ?bool
    {
        $errors = GraphQLException::extractErrors($response);

        return $errors === [] ? null : true;
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function getRequestException(Response $response, ?Throwable $senderException): ?Throwable
    {
        $errors = GraphQLException::extractErrors($response);

        if ($errors !== []) {
            return $this->graphqlException($response, $errors);
        }

        return $this->httpException($response);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function paginate(Request $request): GraphQLCursorPaginator
    {
        $path = $request instanceof GraphQLCursorPaginatable ? $request->getCursorPath() : '';

        return new GraphQLCursorPaginator($this, $request, $path);
    }

    /**
     * @param  list<array<string, mixed>>  $errors
     */
    private function graphqlException(Response $response, array $errors): Throwable
    {
        $first = $errors[0];

        $code = $first['extensions']['code'] ?? null;

        $message = isset($first['message']) && is_string($first['message']) ? $first['message'] : null;

        if (! is_string($code)) {
            // A complexity-cap rejection is a top-level error with no `path`
            // and no `extensions.code`.
            if (! array_key_exists('path', $first)) {
                return new ComplexityException($response, $message);
            }

            return GraphQLException::fromResponse($response);
        }

        return match ($code) {
            'UNAUTHENTICATED' => new AuthenticationException($response, $message),
            'FORBIDDEN' => new ForbiddenException($response, $message),
            'NOT_FOUND' => new NotFoundException($response, $message),
            'BAD_USER_INPUT' => new ValidationException($response, $message),
            'THROTTLED' => new RateLimitException($response, $message),
            default => new GraphQLException($response, $errors, $message),
        };
    }
}
