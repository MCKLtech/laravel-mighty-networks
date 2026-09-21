<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\GraphQL;

use MCKLtech\MightyNetworks\Collections\GraphQLMemberCollection;
use MCKLtech\MightyNetworks\Collections\GraphQLNodeCollection;
use MCKLtech\MightyNetworks\Connectors\GraphQLConnector;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\GraphQLBillingPlan;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\GraphQLMember;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\GraphQLNetwork;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\GraphQLNode;
use MCKLtech\MightyNetworks\Enums\GraphQLMutation;
use MCKLtech\MightyNetworks\Enums\MembershipRole;
use MCKLtech\MightyNetworks\Enums\MemberSort;
use MCKLtech\MightyNetworks\Enums\SortOrder;
use MCKLtech\MightyNetworks\Exceptions\GraphQLException;
use MCKLtech\MightyNetworks\Exceptions\MightyNetworksException;
use MCKLtech\MightyNetworks\Pagination\Contracts\GraphQLCursorPaginatable;
use MCKLtech\MightyNetworks\Pagination\GraphQLCursorPaginator;
use MCKLtech\MightyNetworks\Requests\GraphQL\BillingPlanQuery;
use MCKLtech\MightyNetworks\Requests\GraphQL\GraphQLMutationRequest;
use MCKLtech\MightyNetworks\Requests\GraphQL\GraphQLRequest;
use MCKLtech\MightyNetworks\Requests\GraphQL\MeQuery;
use MCKLtech\MightyNetworks\Requests\GraphQL\NetworkMembersQuery;
use MCKLtech\MightyNetworks\Requests\GraphQL\NetworkQuery;
use MCKLtech\MightyNetworks\Requests\GraphQL\NodeQuery;
use MCKLtech\MightyNetworks\Requests\GraphQL\NodesQuery;
use MCKLtech\MightyNetworks\Requests\GraphQL\RawGraphQLRequest;
use Saloon\Http\Response;

/**
 * Ergonomic entry point to the Mighty API (GraphQL) for a single Network.
 *
 * Wraps a {@see GraphQLConnector} and the `network_id_or_subdomain` path segment
 * shared by every operation. Typed helpers cover the most common reads; use
 * {@see query()} with a typed request or {@see raw()} for everything else.
 */
final class GraphQLClient
{
    /**
     * The documented page cap for connections on the query root and `Network`.
     */
    private const ROOT_PAGE_LIMIT = 50;

    public function __construct(
        private readonly GraphQLConnector $connector,
        private readonly int|string $networkIdOrSubdomain,
    ) {}

    /**
     * The underlying connector (useful for advanced configuration or mocking).
     */
    public function connector(): GraphQLConnector
    {
        return $this->connector;
    }

    /**
     * Send a typed GraphQL request.
     */
    public function query(GraphQLRequest $request): Response
    {
        return $this->connector->send($request);
    }

    /**
     * Execute an arbitrary GraphQL document.
     *
     * @param  array<string, mixed>  $variables
     *
     * @throws GraphQLException
     */
    public function raw(string $query, array $variables = []): Response
    {
        return $this->connector->send(new RawGraphQLRequest($this->networkIdOrSubdomain, $query, $variables));
    }

    /**
     * Execute any mutation by operation, without a bespoke request class.
     *
     * Builds `mutation OpName($input: OpNameInput!) { opName(input: $input) { ... } }`.
     * Pass the mutation's `input` value under the `input` key of `$variables`.
     * A raw operation string is resolved against {@see GraphQLMutation}; when it
     * is unknown, supply `$inputType` or an {@see \InvalidArgumentException} is
     * thrown rather than sending an invalid document.
     *
     * @param  array<string, mixed>  $variables
     *
     * @throws \InvalidArgumentException
     * @throws GraphQLException
     */
    public function mutate(
        GraphQLMutation|string $operation,
        array $variables = [],
        ?Selection $selection = null,
        ?string $inputType = null,
    ): Response {
        return $this->connector->send(new GraphQLMutationRequest(
            networkIdOrSubdomain: $this->networkIdOrSubdomain,
            operation: $operation,
            variables: $variables,
            selection: $selection,
            inputType: $inputType,
        ));
    }

    /**
     * A billing plan tier by canonical name, or null when none matches.
     */
    public function billingPlan(string $canonicalName): ?GraphQLBillingPlan
    {
        $dto = $this->query(new BillingPlanQuery($this->networkIdOrSubdomain, $canonicalName))->dto();

        return $dto instanceof GraphQLBillingPlan ? $dto : null;
    }

    /**
     * The currently authenticated member, or null when the viewer has no member node.
     */
    public function me(): ?GraphQLMember
    {
        $dto = $this->query(new MeQuery($this->networkIdOrSubdomain))->dto();

        return $dto instanceof GraphQLMember ? $dto : null;
    }

    /**
     * The current Network's public settings.
     */
    public function network(): GraphQLNetwork
    {
        $dto = $this->query(new NetworkQuery($this->networkIdOrSubdomain))->dto();

        return $this->ensure($dto, GraphQLNetwork::class);
    }

    /**
     * Fetch the first page of the Network's member roster.
     */
    public function members(
        int $perPage = 25,
        ?MemberSort $sort = null,
        ?SortOrder $sortOrder = null,
        ?string $term = null,
        ?MembershipRole $role = null,
        ?string $memberType = null,
        ?string $spaceId = null,
    ): GraphQLMemberCollection {
        $request = new NetworkMembersQuery(
            networkIdOrSubdomain: $this->networkIdOrSubdomain,
            first: min($perPage, self::ROOT_PAGE_LIMIT),
            sort: $sort,
            sortOrder: $sortOrder,
            term: $term,
            role: $role,
            memberType: $memberType,
            spaceId: $spaceId,
        );

        $dto = $this->query($request)->dto();

        return $this->ensure($dto, GraphQLMemberCollection::class);
    }

    /**
     * Lazily paginate a cursor connection. The default page size respects the
     * API's 50-item cap for root/Network connections.
     */
    public function paginate(GraphQLRequest&GraphQLCursorPaginatable $request, int $perPage = 25): GraphQLCursorPaginator
    {
        return $this->connector
            ->paginate($request)
            ->setPerPageLimit(min($perPage, self::ROOT_PAGE_LIMIT));
    }

    /**
     * A single Relay node by GlobalID or resource ID.
     *
     * @param  list<string>  $fields
     */
    public function node(string $id, array $fields = ['__typename', 'id']): ?GraphQLNode
    {
        $dto = $this->query(new NodeQuery($this->networkIdOrSubdomain, $id, $fields))->dto();

        return $dto instanceof GraphQLNode ? $dto : null;
    }

    /**
     * Several Relay nodes in one round trip. Unresolved IDs are dropped.
     *
     * @param  list<string>  $ids
     * @param  list<string>  $fields
     */
    public function nodes(array $ids, array $fields = ['__typename', 'id']): GraphQLNodeCollection
    {
        $dto = $this->query(new NodesQuery($this->networkIdOrSubdomain, $ids, $fields))->dto();

        return $this->ensure($dto, GraphQLNodeCollection::class);
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $expected
     * @return T
     *
     * @throws MightyNetworksException
     */
    private function ensure(mixed $value, string $expected): object
    {
        if (! $value instanceof $expected) {
            throw new MightyNetworksException(
                sprintf('Expected a [%s] from the GraphQL endpoint.', $expected),
            );
        }

        return $value;
    }
}
