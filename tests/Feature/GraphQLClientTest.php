<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Tests\Feature;

use MCKLtech\MightyNetworks\Collections\GraphQLMemberCollection;
use MCKLtech\MightyNetworks\Collections\GraphQLNodeCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\GraphQLMember;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\GraphQLNetwork;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\GraphQLNode;
use MCKLtech\MightyNetworks\Enums\MemberSort;
use MCKLtech\MightyNetworks\Enums\SortOrder;
use MCKLtech\MightyNetworks\GraphQL\GraphQLClient;
use MCKLtech\MightyNetworks\Requests\GraphQL\MeQuery;
use MCKLtech\MightyNetworks\Requests\GraphQL\NetworkMembersQuery;
use MCKLtech\MightyNetworks\Requests\GraphQL\NetworkQuery;
use MCKLtech\MightyNetworks\Requests\GraphQL\NodeQuery;
use MCKLtech\MightyNetworks\Requests\GraphQL\NodesQuery;
use MCKLtech\MightyNetworks\Requests\GraphQL\RawGraphQLRequest;
use MCKLtech\MightyNetworks\Tests\TestCase;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

final class GraphQLClientTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function memberPayload(array $overrides = []): array
    {
        return array_merge([
            'id' => 'gid://mighty/Member/42',
            'resourceId' => '42',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            'avatarUrl' => 'https://cdn.mn.co/avatars/42.jpg',
            'memberType' => 'FULL_MEMBER',
            'networkRole' => 'HOST',
            'isLimitedMember' => false,
            'referralCount' => 3,
            'joinedAt' => '2024-01-15T10:30:00+00:00',
            'url' => 'https://test-network.mn.co/members/42',
        ], $overrides);
    }

    private function client(MockClient $mock): GraphQLClient
    {
        return new GraphQLClient($this->graphql($mock), '12345');
    }

    public function test_me_returns_a_typed_graphql_member(): void
    {
        $mock = new MockClient([MockResponse::make(['data' => ['me' => $this->memberPayload()]], 200)]);

        $member = $this->client($mock)->me();

        $this->assertInstanceOf(GraphQLMember::class, $member);
        $this->assertSame('gid://mighty/Member/42', $member->id);
        $this->assertSame('42', $member->resourceId);
        $this->assertSame('FULL_MEMBER', $member->memberType);
        $this->assertSame('HOST', $member->networkRole);
        $this->assertSame(3, $member->referralCount);
        $this->assertSame('2024-01-15T10:30:00+00:00', $member->joinedAt?->toIso8601String());

        $mock->assertSent(function ($request): bool {
            return $request instanceof MeQuery
                && $request->getMethod() === Method::POST
                && $request->resolveEndpoint() === 'networks/12345/graphql'
                && str_contains($request->body()->all()['query'], 'me {');
        });
    }

    public function test_me_returns_null_when_the_viewer_has_no_member_node(): void
    {
        $mock = new MockClient([MockResponse::make(['data' => ['me' => null]], 200)]);

        $this->assertNull($this->client($mock)->me());
    }

    public function test_network_returns_a_typed_graphql_network(): void
    {
        $mock = new MockClient([MockResponse::make([
            'data' => ['network' => [
                'id' => 'gid://mighty/Network/1',
                'resourceId' => '1',
                'title' => 'Test Network',
                'slug' => 'test-network',
                'url' => 'https://test-network.mn.co',
                'discoverable' => true,
                'joinable' => true,
            ]],
        ], 200)]);

        $network = $this->client($mock)->network();

        $this->assertInstanceOf(GraphQLNetwork::class, $network);
        $this->assertSame('Test Network', $network->title);
        $this->assertTrue($network->discoverable);

        $mock->assertSent(static fn ($request): bool => $request instanceof NetworkQuery);
    }

    public function test_members_returns_a_collection_and_passes_filter_variables(): void
    {
        $mock = new MockClient([MockResponse::make([
            'data' => ['network' => ['members' => [
                'nodes' => [$this->memberPayload(['id' => 'a']), $this->memberPayload(['id' => 'b'])],
                'pageInfo' => ['endCursor' => 'C1', 'hasNextPage' => false],
                'totalCount' => 2,
            ]]],
        ], 200)]);

        $members = $this->client($mock)->members(
            perPage: 25,
            sort: MemberSort::ResourceId,
            sortOrder: SortOrder::Asc,
            term: 'jane',
        );

        $this->assertInstanceOf(GraphQLMemberCollection::class, $members);
        $this->assertCount(2, $members);
        $this->assertSame('a', $members->first()?->id);

        $mock->assertSent(function ($request): bool {
            if (! $request instanceof NetworkMembersQuery) {
                return false;
            }

            $body = $request->body()->all();

            return $body['variables']['first'] === 25
                && $body['variables']['sort'] === 'RESOURCE_ID'
                && $body['variables']['sortOrder'] === 'ASC'
                && $body['variables']['term'] === 'jane'
                && ! array_key_exists('after', $body['variables']);
        });
    }

    public function test_it_paginates_a_network_members_connection(): void
    {
        $mock = new MockClient([
            MockResponse::make([
                'data' => ['network' => ['members' => [
                    'nodes' => [$this->memberPayload(['id' => '1']), $this->memberPayload(['id' => '2'])],
                    'pageInfo' => ['endCursor' => 'CURSOR1', 'hasNextPage' => true],
                    'totalCount' => 3,
                ]]],
            ], 200),
            MockResponse::make([
                'data' => ['network' => ['members' => [
                    'nodes' => [$this->memberPayload(['id' => '3'])],
                    'pageInfo' => ['endCursor' => 'CURSOR2', 'hasNextPage' => false],
                    'totalCount' => 3,
                ]]],
            ], 200),
        ]);

        $client = $this->client($mock);

        $ids = [];

        foreach ($client->paginate(new NetworkMembersQuery('12345'), perPage: 50)->items() as $member) {
            $this->assertInstanceOf(GraphQLMember::class, $member);
            $ids[] = $member->id;
        }

        $this->assertSame(['1', '2', '3'], $ids);
        $mock->assertSentCount(2);

        $requests = array_map(
            static fn ($response) => $response->getPendingRequest()->getRequest(),
            $mock->getRecordedResponses(),
        );

        $firstBody = $requests[0] instanceof HasBody ? $requests[0]->body()->all() : [];
        $secondBody = $requests[1] instanceof HasBody ? $requests[1]->body()->all() : [];

        $this->assertSame(50, $firstBody['variables']['first']);
        $this->assertArrayNotHasKey('after', $firstBody['variables']);
        $this->assertSame('CURSOR1', $secondBody['variables']['after']);
    }

    public function test_node_returns_a_typed_node(): void
    {
        $mock = new MockClient([MockResponse::make([
            'data' => ['node' => ['__typename' => 'Member', 'id' => 'gid://mighty/Member/42']],
        ], 200)]);

        $node = $this->client($mock)->node('gid://mighty/Member/42');

        $this->assertInstanceOf(GraphQLNode::class, $node);
        $this->assertSame('Member', $node->typename);

        $mock->assertSent(function ($request): bool {
            return $request instanceof NodeQuery
                && $request->body()->all()['variables']['id'] === 'gid://mighty/Member/42';
        });
    }

    public function test_node_returns_null_when_not_found(): void
    {
        $mock = new MockClient([MockResponse::make(['data' => ['node' => null]], 200)]);

        $this->assertNull($this->client($mock)->node('missing'));
    }

    public function test_nodes_returns_a_collection_and_drops_unresolved_entries(): void
    {
        $mock = new MockClient([MockResponse::make([
            'data' => ['nodes' => [
                ['__typename' => 'Member', 'id' => '1'],
                null,
                ['__typename' => 'Space', 'id' => '3'],
            ]],
        ], 200)]);

        $nodes = $this->client($mock)->nodes(['1', '2', '3']);

        $this->assertInstanceOf(GraphQLNodeCollection::class, $nodes);
        $this->assertCount(2, $nodes);

        $mock->assertSent(function ($request): bool {
            return $request instanceof NodesQuery
                && $request->body()->all()['variables']['ids'] === ['1', '2', '3'];
        });
    }

    public function test_raw_executes_an_arbitrary_document(): void
    {
        $mock = new MockClient([MockResponse::make(['data' => ['ping' => 'pong']], 200)]);

        $response = $this->client($mock)->raw('query { ping }', ['foo' => 'bar']);

        $this->assertSame('pong', $response->json('data.ping'));

        $mock->assertSent(function ($request): bool {
            return $request instanceof RawGraphQLRequest
                && $request->body()->all()['variables'] === ['foo' => 'bar'];
        });
    }
}
