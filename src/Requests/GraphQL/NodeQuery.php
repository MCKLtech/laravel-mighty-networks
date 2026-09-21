<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\GraphQL;

use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\GraphQLNode;
use MCKLtech\MightyNetworks\GraphQL\Selection;
use MCKLtech\MightyNetworks\GraphQL\Variable;
use Saloon\Http\Response;

/**
 * `query { node(id: $id) { ... } }` — a single Relay node by GlobalID or resource ID.
 *
 * The root `Node` interface guarantees only `id`, so `__typename` is selected
 * alongside it. Add leaf field names via `$fields` when the concrete type is
 * known; use a raw query for nested selections.
 */
final class NodeQuery extends GraphQLRequest
{
    /**
     * @param  list<string>  $fields
     */
    public function __construct(
        int|string $networkIdOrSubdomain,
        private readonly string $id,
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

        return Selection::make('query Node($id: ID!)')
            ->field('node', arguments: ['id' => new Variable('id')], selection: $selection)
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
        return ['id' => $this->id];
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): ?GraphQLNode
    {
        $node = $this->dataFrom($response)['node'] ?? null;

        return is_array($node) ? GraphQLNode::fromArray($node) : null;
    }
}
