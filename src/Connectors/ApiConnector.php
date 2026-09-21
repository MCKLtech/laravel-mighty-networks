<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Connectors;

use Illuminate\Contracts\Cache\Repository;
use MCKLtech\MightyNetworks\Exceptions\AuthenticationException;
use MCKLtech\MightyNetworks\Exceptions\ForbiddenException;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Exceptions\RateLimitException;
use MCKLtech\MightyNetworks\Exceptions\RequestException;
use MCKLtech\MightyNetworks\Exceptions\ServerException;
use MCKLtech\MightyNetworks\Exceptions\ValidationException;

use function rtrim;

use Saloon\Http\Connector;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\HasPagination;
use Saloon\PaginationPlugin\Paginator;
use Saloon\RateLimitPlugin\Contracts\RateLimitStore;
use Saloon\RateLimitPlugin\Limit;
use Saloon\RateLimitPlugin\Stores\LaravelCacheStore;
use Saloon\RateLimitPlugin\Stores\MemoryStore;
use Saloon\RateLimitPlugin\Traits\HasRateLimits;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;
use Throwable;

/**
 * Shared behaviour for the two Mighty Networks connectors (Admin REST and
 * GraphQL): retry policy, client-side rate limiting and the mandatory
 * `User-Agent` header.
 */
abstract class ApiConnector extends Connector implements HasPagination
{
    use AlwaysThrowOnErrors;
    use HasRateLimits;

    public const DEFAULT_USER_AGENT = 'laravel-mighty-networks/1.0 (+https://github.com/MCKLtech/laravel-mighty-networks)';

    /**
     * @param  array<string, mixed>  $retry
     * @param  array<string, mixed>  $rateLimits
     */
    public function __construct(
        protected readonly string $baseUrl = 'https://api.mn.co',
        protected readonly string $userAgent = self::DEFAULT_USER_AGENT,
        protected readonly array $retry = [],
        protected readonly array $rateLimits = [],
        protected readonly ?Repository $cache = null,
    ) {
        $this->configureRetries();

        if (($this->rateLimits['enabled'] ?? true) === false) {
            $this->useRateLimitPlugin(false);
        }
    }

    /**
     * The connector's base URL, without a trailing slash.
     */
    protected function normalizedBaseUrl(): string
    {
        return rtrim($this->baseUrl, '/');
    }

    /**
     * Map an HTTP failure status onto the SDK's exception hierarchy.
     */
    protected function httpException(Response $response): ?Throwable
    {
        return match (true) {
            $response->status() === 401 => new AuthenticationException($response),
            $response->status() === 403 => new ForbiddenException($response),
            $response->status() === 404 => new NotFoundException($response),
            $response->status() === 422 => new ValidationException($response),
            $response->status() === 429 => new RateLimitException($response),
            $response->serverError() => new ServerException($response),
            $response->clientError() => new RequestException($response),
            default => null,
        };
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    abstract public function paginate(Request $request): Paginator;

    /**
     * The default headers sent with every request.
     *
     * @return array<string, string>
     */
    #[\Override]
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'User-Agent' => $this->userAgent,
        ];
    }

    /**
     * Apply the configured retry policy to Saloon's core `HasTries` plugin.
     */
    private function configureRetries(): void
    {
        $tries = $this->retry['tries'] ?? null;
        $interval = $this->retry['interval'] ?? null;
        $backoff = $this->retry['exponential_backoff'] ?? null;

        $this->tries = is_int($tries) ? $tries : null;
        $this->retryInterval = is_int($interval) ? $interval : null;
        $this->useExponentialBackoff = is_bool($backoff) ? $backoff : null;
    }

    /**
     * Client-side rate limits. Mighty Networks exposes no rate-limit headers,
     * so these are derived entirely from configuration.
     *
     * @return array<int, Limit>
     */
    #[\Override]
    protected function resolveLimits(): array
    {
        $limits = [];

        $perMinute = $this->rateLimits['per_minute'] ?? null;

        if (is_numeric($perMinute) && (int) $perMinute > 0) {
            $limits[] = Limit::allow((int) $perMinute)->everyMinute();
        }

        $perDay = $this->rateLimits['per_day'] ?? null;

        if (is_numeric($perDay) && (int) $perDay > 0) {
            $limits[] = Limit::allow((int) $perDay)->everyDay();
        }

        return $limits;
    }

    /**
     * Persist rate-limit counters in the configured Laravel cache store, falling
     * back to an in-memory store when the SDK is used outside a Laravel app.
     */
    #[\Override]
    protected function resolveRateLimitStore(): RateLimitStore
    {
        return $this->cache === null
            ? new MemoryStore
            : new LaravelCacheStore($this->cache);
    }
}
