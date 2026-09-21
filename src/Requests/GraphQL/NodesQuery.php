<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\GraphQL;

use MCKLtech\MightyNetworks\Collections\GraphQLNodeCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\GraphQLNode;
use MCKLtech\MightyNetworks\GraphQL\Selection;
use MCKLtech\MightyNetworks\GraphQL\Variable;
use Saloon\Http\Response;

/**
 * `query { nodes(ids: $ids) { ... } }` — several Relay nodes in one round trip.
 *
 * The list preserves input order and may contain nulls for IDs that do not
 * resolve; those entries are dropped from the resulting collection.
 */
final class NodesQuery extends GraphQLRequest
{
    /**
     * @param  list<string>  $ids
     * @param  list<string>  $fields
     */
    public function __construct(
        int|string $networkIdOrSubdomain,
        private readonly array $ids,
        private readonly array $fields = ['__typename', 'id'],
    ) {
        parent::__construct($networkIdOrSubdomain);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function document(): string
    {
        $selection = Selection::make();

        foreach ($this->fields as $field) {
            $selection->field($field);
        }

        return Selection::make('query Nodes($ids: [ID!]!)')
            ->field('nodes', arguments: ['ids' => new Variable('ids')], selection: $selection)
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
        return ['ids' => array_values($this->ids)];
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): GraphQLNodeCollection
    {
        $nodes = $this->dataFrom($response)['nodes'] ?? null;

        if (! is_array($nodes)) {
            return new GraphQLNodeCollection;
        }

        $collection = [];

        foreach ($nodes as $node) {
            if (is_array($node)) {
                $collection[] = GraphQLNode::fromArray($node);
            }
        }

        return new GraphQLNodeCollection($collection);
    }
}
