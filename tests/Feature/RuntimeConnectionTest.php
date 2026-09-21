<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use MCKLtech\MightyNetworks\DataTransferObjects\Member;
use MCKLtech\MightyNetworks\Exceptions\MightyNetworksException;
use MCKLtech\MightyNetworks\Facades\MightyNetworks as Facade;
use MCKLtech\MightyNetworks\MightyNetworks;
use MCKLtech\MightyNetworks\Tests\Support\TestGraphQLRequest;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;
use Saloon\Http\Response;

/**
 * A runtime connection lets credentials be supplied per call rather than read
 * from the config file — the multi-tenant path, where each network's credentials
 * live in a database.
 */
final class RuntimeConnectionTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function memberPayload(int $id = 7): array
    {
        return [
            'id' => $id,
            'email' => 'jane@example.com',
            'member_type' => 'full',
            'permalink' => 'jane',
            'created_at' => '2026-01-01T00:00:00+00:00',
            'updated_at' => '2026-01-01T00:00:00+00:00',
        ];
    }

    /**
     * @return callable(Request, Response): bool
     */
    private function bearerIs(string $token, string $endpointFragment): callable
    {
        return static fn (Request $request, Response $response): bool => $response
            ->getPendingRequest()
            ->headers()
            ->get('Authorization') === 'Bearer '.$token
            && str_contains($request->resolveEndpoint(), $endpointFragment);
    }

    public function test_it_builds_a_connection_from_explicit_credentials_without_the_container(): void
    {
        $connection = MightyNetworks::make('runtime-token', 98765);

        $mock = new MockClient([MockResponse::make($this->memberPayload())]);

        $connection->admin()->withMockClient($mock);

        $member = $connection->members()->findById(7);

        $this->assertInstanceOf(Member::class, $member);
        $this->assertSame('jane@example.com', $member->email);
        $this->assertSame(98765, $connection->networkId());
        $this->assertSame('runtime', $connection->getName());

        $mock->assertSent($this->bearerIs('runtime-token', 'networks/98765/'));
    }

    public function test_it_applies_overrides_to_a_runtime_connection(): void
    {
        $connection = MightyNetworks::make('runtime-token', 42, [
            'base_url' => 'https://eu.api.mn.co',
            'user_agent' => 'tenant-app/2.0',
        ]);

        $this->assertSame('https://eu.api.mn.co', $connection->baseUrl());
        $this->assertSame('tenant-app/2.0', $connection->userAgent());
    }

    public function test_a_null_admin_token_yields_a_graphql_only_connection(): void
    {
        $connection = MightyNetworks::make(null, 42, [
            'subdomain' => 'acme',
            'oauth' => ['access_token' => 'oauth-token'],
        ]);

        $mock = new MockClient([MockResponse::make(['data' => ['ok' => true]], 200)]);

        $connection->graphql()->withMockClient($mock);
        $connection->graphql()->send(new TestGraphQLRequest);

        $mock->assertSent(static fn (Request $request, Response $response): bool => $response
            ->getPendingRequest()
            ->headers()
            ->get('Authorization') === 'Bearer oauth-token');

        // The Admin API genuinely has no credential here, so it must fail loudly
        // rather than silently sending an empty token.
        $this->expectException(MightyNetworksException::class);

        $connection->admin();
    }

    public function test_runtime_connections_do_not_share_credentials(): void
    {
        $first = MightyNetworks::make('token-one', 1);
        $second = MightyNetworks::make('token-two', 2);

        $firstMock = new MockClient([MockResponse::make($this->memberPayload(1))]);
        $secondMock = new MockClient([MockResponse::make($this->memberPayload(2))]);

        $first->admin()->withMockClient($firstMock);
        $second->admin()->withMockClient($secondMock);

        $first->members()->findById(1);
        $second->members()->findById(2);

        $firstMock->assertSent($this->bearerIs('token-one', 'networks/1/'));
        $secondMock->assertSent($this->bearerIs('token-two', 'networks/2/'));
    }

    public function test_the_facade_inherits_non_credential_settings_but_never_secrets(): void
    {
        $connection = Facade::withCredentials('tenant-token', 555);

        // Inherited from the configured default connection.
        $this->assertSame('laravel-mighty-networks-tests/1.0', $connection->userAgent());
        $this->assertSame('https://api.mn.co', $connection->baseUrl());

        $mock = new MockClient([MockResponse::make($this->memberPayload())]);

        $connection->admin()->withMockClient($mock);
        $connection->members()->findById(7);

        // Uses the runtime credential, and never the configured connection's secret.
        $mock->assertSent($this->bearerIs('tenant-token', 'networks/555/'));
        $mock->assertNotSent(fn (Request $request, Response $response): bool => $response
            ->getPendingRequest()
            ->headers()
            ->get('Authorization') === 'Bearer test-admin-token');
    }

    public function test_the_facade_can_build_a_connection_from_a_full_config_array(): void
    {
        $connection = Facade::withConfig([
            'network_id' => 321,
            'admin_token' => 'config-array-token',
            'base_url' => 'https://api.mn.co',
            'user_agent' => 'config-array/1.0',
        ], 'from-array');

        $this->assertSame('from-array', $connection->getName());
        $this->assertSame(321, $connection->networkId());
        $this->assertSame('config-array/1.0', $connection->userAgent());
    }
}
