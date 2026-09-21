<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use MCKLtech\MightyNetworks\Connectors\AdminConnector;
use MCKLtech\MightyNetworks\Connectors\GraphQLConnector;
use MCKLtech\MightyNetworks\Exceptions\MightyNetworksException;
use MCKLtech\MightyNetworks\Facades\MightyNetworks as MightyNetworksFacade;
use MCKLtech\MightyNetworks\GraphQL\GraphQLClient;
use MCKLtech\MightyNetworks\MightyNetworks;
use MCKLtech\MightyNetworks\MightyNetworksManager;
use MCKLtech\MightyNetworks\Requests\Admin\Members\GetMemberRequest;
use MCKLtech\MightyNetworks\Resources\AssetsResource;
use MCKLtech\MightyNetworks\Resources\BadgesResource;
use MCKLtech\MightyNetworks\Resources\CollectionsResource;
use MCKLtech\MightyNetworks\Resources\CommentsResource;
use MCKLtech\MightyNetworks\Resources\CustomFieldsResource;
use MCKLtech\MightyNetworks\Resources\EventsResource;
use MCKLtech\MightyNetworks\Resources\InvitesResource;
use MCKLtech\MightyNetworks\Resources\MembersResource;
use MCKLtech\MightyNetworks\Resources\PlansResource;
use MCKLtech\MightyNetworks\Resources\PollsResource;
use MCKLtech\MightyNetworks\Resources\PostsResource;
use MCKLtech\MightyNetworks\Resources\PurchasesResource;
use MCKLtech\MightyNetworks\Resources\SpacesResource;
use MCKLtech\MightyNetworks\Resources\SubscriptionsResource;
use MCKLtech\MightyNetworks\Resources\TagsResource;
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

    public function test_every_resource_accessor_returns_its_typed_and_memoised_resource(): void
    {
        $connection = $this->manager()->connection('default');

        $accessors = [
            'members' => MembersResource::class,
            'posts' => PostsResource::class,
            'comments' => CommentsResource::class,
            'events' => EventsResource::class,
            'plans' => PlansResource::class,
            'subscriptions' => SubscriptionsResource::class,
            'purchases' => PurchasesResource::class,
            'invites' => InvitesResource::class,
            'spaces' => SpacesResource::class,
            'collections' => CollectionsResource::class,
            'tags' => TagsResource::class,
            'badges' => BadgesResource::class,
            'customFields' => CustomFieldsResource::class,
            'polls' => PollsResource::class,
            'assets' => AssetsResource::class,
        ];

        foreach ($accessors as $method => $expected) {
            $this->assertInstanceOf($expected, $connection->{$method}(), $method);
            $this->assertSame($connection->{$method}(), $connection->{$method}(), $method.' is memoised');
        }

        $this->assertInstanceOf(AdminConnector::class, $connection->admin());
        $this->assertSame($connection->admin(), $connection->admin());
        $this->assertInstanceOf(GraphQLConnector::class, $connection->graphql());
        $this->assertSame($connection->graphql(), $connection->graphql());
        $this->assertInstanceOf(GraphQLClient::class, $connection->graphqlClient());
        $this->assertSame($connection->graphqlClient(), $connection->graphqlClient());
    }

    public function test_network_id_throws_when_it_is_not_configured(): void
    {
        $connection = new MightyNetworks([], 'missing');

        $this->expectException(MightyNetworksException::class);

        $connection->networkId();
    }

    public function test_subdomain_is_null_when_not_configured(): void
    {
        $this->assertNull((new MightyNetworks(['network_id' => 1]))->subdomain());
    }

    public function test_oauth_client_is_null_when_a_static_access_token_wins(): void
    {
        $connection = new MightyNetworks([
            'network_id' => 1,
            'subdomain' => 'acme',
            'oauth' => ['client_id' => 'client', 'access_token' => 'static-token'],
        ]);

        $this->assertNull($connection->oauthClient());
    }

    public function test_token_store_is_null_without_a_cache_repository(): void
    {
        $this->assertNull((new MightyNetworks(['network_id' => 1]))->tokenStore());
    }
}
