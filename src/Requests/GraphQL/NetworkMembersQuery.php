<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\GraphQL;

use MCKLtech\MightyNetworks\Collections\GraphQLMemberCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\GraphQLMember;
use MCKLtech\MightyNetworks\Enums\MembershipRole;
use MCKLtech\MightyNetworks\Enums\MemberSort;
use MCKLtech\MightyNetworks\Enums\SortOrder;
use MCKLtech\MightyNetworks\GraphQL\Selection;
use MCKLtech\MightyNetworks\GraphQL\Selections;
use MCKLtech\MightyNetworks\GraphQL\Variable;
use MCKLtech\MightyNetworks\Pagination\Contracts\GraphQLCursorPaginatable;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\MapPaginatedResponseItems;

/**
 * Paginated `network { members(first, after, sort, sortOrder, ...) }`.
 *
 * The `Network.members` connection caps a page at 50; page from
 * `pageInfo.endCursor` until `pageInfo.hasNextPage` is false.
 */
final class NetworkMembersQuery extends GraphQLRequest implements GraphQLCursorPaginatable, MapPaginatedResponseItems
{
    /**
     * @param  list<string>|null  $segmentIds
     */
    public function __construct(
        int|string $networkIdOrSubdomain,
        private readonly ?int $first = null,
        private readonly ?string $after = null,
        private readonly ?MemberSort $sort = null,
        private readonly ?SortOrder $sortOrder = null,
        private readonly ?string $term = null,
        private readonly ?MembershipRole $role = null,
        private readonly ?string $memberType = null,
        private readonly ?string $spaceId = null,
        private readonly ?array $segmentIds = null,
    ) {
        parent::__construct($networkIdOrSubdomain);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function document(): string
    {
        $members = Selection::make()
            ->field('nodes', selection: Selections::member())
            ->field('pageInfo', selection: Selection::make()->field('endCursor')->field('hasNextPage'))
            ->field('totalCount');

        $arguments = [];

        foreach (['first', 'after', 'sort', 'sortOrder', 'term', 'role', 'memberType', 'spaceId', 'segmentIds'] as $name) {
            $arguments[$name] = new Variable($name);
        }

        return Selection::make(
            'query NetworkMembers($first: Int, $after: String, $sort: MemberSort, $sortOrder: SortOrder, '
            .'$term: String, $role: MembershipRole, $memberType: MemberType, $spaceId: ID, $segmentIds: [ID!])',
        )
            ->field('network', selection: Selection::make()->field('members', arguments: $arguments, selection: $members))
            ->render();
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function variables(): array
    {
        $variables = [
            'first' => $this->first,
            'after' => $this->after,
            'sort' => $this->sort?->value,
            'sortOrder' => $this->sortOrder?->value,
            'term' => $this->term,
            'role' => $this->role?->value,
            'memberType' => $this->memberType,
            'spaceId' => $this->spaceId,
            'segmentIds' => $this->segmentIds,
        ];

        return array_filter($variables, static fn (mixed $value): bool => $value !== null && $value !== []);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function getCursorPath(): string
    {
        return 'network.members';
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): GraphQLMemberCollection
    {
        return new GraphQLMemberCollection($this->mapPaginatedResponseItems($response));
    }

    /**
     * Map each connection node to a {@see GraphQLMember}, so paginators yield DTOs.
     *
     * @return array<int, GraphQLMember>
     */
    #[\Override]
    public function mapPaginatedResponseItems(Response $response): array
    {
        $nodes = $this->dataFrom($response)['network']['members']['nodes'] ?? null;

        if (! is_array($nodes)) {
            return [];
        }

        $members = [];

        foreach ($nodes as $node) {
            if (is_array($node)) {
                $members[] = GraphQLMember::fromArray($node);
            }
        }

        return $members;
    }
}
