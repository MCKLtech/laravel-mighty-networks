<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\GraphQL;

use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\GraphQLNetwork;
use MCKLtech\MightyNetworks\GraphQL\Selection;
use MCKLtech\MightyNetworks\GraphQL\Selections;
use Saloon\Http\Response;

/**
 * `query { network { ... } }` — the current Network's public settings.
 */
final class NetworkQuery extends GraphQLRequest
{
    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function document(): string
    {
        return Selection::make('query Network')
            ->field('network', selection: Selections::network())
            ->render();
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): GraphQLNetwork
    {
        $network = $this->dataFrom($response)['network'] ?? null;

        return GraphQLNetwork::fromArray(is_array($network) ? $network : []);
    }
}
