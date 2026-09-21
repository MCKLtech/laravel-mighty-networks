<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use MCKLtech\MightyNetworks\Connectors\AdminConnector;
use MCKLtech\MightyNetworks\Facades\MightyNetworks as MightyNetworksFacade;
use MCKLtech\MightyNetworks\MightyNetworks;
use MCKLtech\MightyNetworks\MightyNetworksManager;
use MCKLtech\MightyNetworks\Requests\Admin\Members\GetMemberRequest;
use MCKLtech\MightyNetworks\Resources\MembersResource;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class MightyNetworksManagerTest extends TestCase
{
    public function test_it_registers_the_manager_as_a_singleton(): void
    {
        $this->assertSame(
            $this->app->make(MightyNetworksManager::class),
            $this->app->make('mighty-networks'),
        );
    }

    public function test_it_resolves_the_default_connection(): void
    {
        $connection = $this->manager()->connection();

        $this->assertInstanceOf(MightyNetworks::class, $connection);
        $this->assertSame('default', $connection->getName());
        $this->assertSame('12345', $connection->networkId());
    }

    public function test_the_manager_proxies_methods_to_the_default_connection(): void
    {
        $this->assertInstanceOf(
            MembersResource::class,
            $this->manager()->members(),
        );
    }

    public function test_the_facade_resolves_through_the_manager(): void
    {
        $this->assertSame('default', MightyNetworksFacade::getDefaultDriver());
        $this->assertInstanceOf(MembersResource::class, MightyNetworksFacade::members());
    }

    public function test_the_admin_connector_resolves_the_admin_base_url(): void
    {
        $connector = new AdminConnector(adminToken: 'token', userAgent: 'test-agent');

        $this->assertSame('https://api.mn.co/admin/v1', $connector->resolveBaseUrl());
        $this->assertSame(Method::GET, (new GetMemberRequest('1', 1))->getMethod());
    }

    public function test_it_sends_the_mandatory_user_agent_and_bearer_token(): void
    {
        $mock = new MockClient([MockResponse::make(['id' => 1], 200)]);

        $this->admin($mock)->send(new GetMemberRequest('12345', 1));

        $mock->assertSent(function ($request, $response): bool {
            $pending = $response->getPendingRequest();

            return $pending->headers()->get('User-Agent') === 'laravel-mighty-networks-tests/1.0'
                && $pending->headers()->get('Authorization') === 'Bearer test-admin-token'
                && $pending->headers()->get('Accept') === 'application/json';
        });
    }
}
