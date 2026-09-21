<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use MCKLtech\MightyNetworks\Connectors\AdminConnector;
use MCKLtech\MightyNetworks\Connectors\ApiConnector;
use MCKLtech\MightyNetworks\Connectors\GraphQLConnector;
use MCKLtech\MightyNetworks\Exceptions\AuthenticationException;
use MCKLtech\MightyNetworks\Exceptions\ForbiddenException;
use MCKLtech\MightyNetworks\Exceptions\NotFoundException;
use MCKLtech\MightyNetworks\Exceptions\ValidationException;
use MCKLtech\MightyNetworks\Tests\Support\TestGraphQLRequest;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\RateLimitPlugin\Exceptions\RateLimitReachedException;
use Saloon\RateLimitPlugin\Stores\LaravelCacheStore;
use Saloon\RateLimitPlugin\Stores\MemoryStore;

final class ApiConnectorBehaviourTest extends TestCase
{
    private function cache(): Repository
    {
        return new Repository(new ArrayStore);
    }

    public function test_it_applies_the_configured_retry_policy(): void
    {
        $connector = new AdminConnector(
            adminToken: 'token',
            retry: ['tries' => 3, 'interval' => 500, 'exponential_backoff' => true],
        );

        $this->assertSame(3, $connector->tries);
        $this->assertSame(500, $connector->retryInterval);
        $this->assertTrue($connector->useExponentialBackoff);
    }

    public function test_retry_defaults_are_null_when_unset_or_invalid(): void
    {
        $connector = new AdminConnector(
            adminToken: 'token',
            retry: ['tries' => 'nope', 'interval' => null, 'exponential_backoff' => 'yes'],
        );

        $this->assertNull($connector->tries);
        $this->assertNull($connector->retryInterval);
        $this->assertNull($connector->useExponentialBackoff);
    }

    public function test_exponential_backoff_can_be_explicitly_disabled(): void
    {
        $connector = new AdminConnector(
            adminToken: 'token',
            retry: ['tries' => 1, 'interval' => 100, 'exponential_backoff' => false],
        );

        $this->assertFalse($connector->useExponentialBackoff);
    }

    public function test_the_rate_limit_store_is_in_memory_without_a_cache_repository(): void
    {
        $connector = new AdminConnector(adminToken: 'token');

        $this->assertInstanceOf(MemoryStore::class, $connector->rateLimitStore());
    }

    public function test_the_rate_limit_store_uses_the_laravel_cache_when_provided(): void
    {
        $connector = new AdminConnector(adminToken: 'token', cache: $this->cache());

        $this->assertInstanceOf(LaravelCacheStore::class, $connector->rateLimitStore());
    }

    public function test_it_resolves_the_configured_rate_limits(): void
    {
        $connector = new AdminConnector(
            adminToken: 'token',
            rateLimits: ['enabled' => true, 'per_minute' => 60, 'per_day' => 1000],
        );

        $allows = array_map(
            static fn ($limit): int => $limit->getAllow(),
            $connector->getLimits(),
        );

        // The custom 429 detector is appended by the plugin as a third limit.
        $this->assertContains(60, $allows);
        $this->assertContains(1000, $allows);
        $this->assertCount(3, $allows);
    }

    public function test_invalid_rate_limit_values_are_ignored(): void
    {
        $connector = new AdminConnector(
            adminToken: 'token',
            rateLimits: ['enabled' => true, 'per_minute' => 0, 'per_day' => 'nope'],
        );

        // Only the plugin's automatic 429 detector remains.
        $this->assertCount(1, $connector->getLimits());
    }

    public function test_exceeding_the_per_minute_limit_blocks_the_next_request(): void
    {
        $connector = new AdminConnector(
            adminToken: 'token',
            rateLimits: ['enabled' => true, 'per_minute' => 1],
        );

        $connector->withMockClient(new MockClient([
            MockResponse::make(['data' => ['ok' => true]], 200),
            MockResponse::make(['data' => ['ok' => true]], 200),
        ]));

        $this->assertFalse($connector->hasReachedRateLimit());

        $connector->send(new TestGraphQLRequest);

        $this->assertTrue($connector->hasReachedRateLimit());

        $this->expectException(RateLimitReachedException::class);

        $connector->send(new TestGraphQLRequest);
    }

    public function test_rate_limits_are_shared_through_the_laravel_cache_store(): void
    {
        $cache = $this->cache();

        $first = new AdminConnector(
            adminToken: 'token',
            rateLimits: ['enabled' => true, 'per_minute' => 1],
            cache: $cache,
        );

        $second = new AdminConnector(
            adminToken: 'token',
            rateLimits: ['enabled' => true, 'per_minute' => 1],
            cache: $cache,
        );

        $first->withMockClient(new MockClient([MockResponse::make(['data' => ['ok' => true]], 200)]));
        $second->withMockClient(new MockClient([MockResponse::make(['data' => ['ok' => true]], 200)]));

        $first->send(new TestGraphQLRequest);

        // The counter lives in the shared cache, so a fresh connector sees it.
        $this->expectException(RateLimitReachedException::class);

        $second->send(new TestGraphQLRequest);
    }

    public function test_rate_limiting_can_be_disabled(): void
    {
        $connector = new AdminConnector(
            adminToken: 'token',
            rateLimits: ['enabled' => false, 'per_minute' => 1],
        );

        $connector->withMockClient(new MockClient([
            MockResponse::make(['data' => ['ok' => true]], 200),
            MockResponse::make(['data' => ['ok' => true]], 200),
        ]));

        $connector->send(new TestGraphQLRequest);
        $response = $connector->send(new TestGraphQLRequest);

        $this->assertTrue($response->successful());
    }

    public function test_a_graphql_http_401_without_an_errors_body_maps_to_authentication(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Unauthenticated'], 401)]);

        $this->expectException(AuthenticationException::class);

        $this->graphql($mock)->send(new TestGraphQLRequest);
    }

    public function test_a_graphql_http_403_maps_to_forbidden(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Forbidden'], 403)]);

        $this->expectException(ForbiddenException::class);

        $this->graphql($mock)->send(new TestGraphQLRequest);
    }

    public function test_a_graphql_http_404_maps_to_not_found(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Not found'], 404)]);

        $this->expectException(NotFoundException::class);

        $this->graphql($mock)->send(new TestGraphQLRequest);
    }

    public function test_a_graphql_http_422_maps_to_validation(): void
    {
        $mock = new MockClient([MockResponse::make(['error' => 'Invalid'], 422)]);

        $this->expectException(ValidationException::class);

        $this->graphql($mock)->send(new TestGraphQLRequest);
    }

    public function test_both_connectors_send_the_default_user_agent_when_none_is_configured(): void
    {
        $admin = new AdminConnector(adminToken: 'token');
        $graphql = new GraphQLConnector(accessToken: 'token');

        $adminMock = new MockClient([MockResponse::make(['data' => ['ok' => true]], 200)]);
        $graphqlMock = new MockClient([MockResponse::make(['data' => ['ok' => true]], 200)]);

        $admin->withMockClient($adminMock)->send(new TestGraphQLRequest);
        $graphql->withMockClient($graphqlMock)->send(new TestGraphQLRequest);

        $adminMock->assertSent(static fn ($request, $response): bool => $response
            ->getPendingRequest()
            ->headers()
            ->get('User-Agent') === ApiConnector::DEFAULT_USER_AGENT);

        $graphqlMock->assertSent(static fn ($request, $response): bool => $response
            ->getPendingRequest()
            ->headers()
            ->get('User-Agent') === ApiConnector::DEFAULT_USER_AGENT);
    }
}
