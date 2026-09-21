<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\GraphQL;

use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\GraphQLMember;
use MCKLtech\MightyNetworks\GraphQL\Selection;
use MCKLtech\MightyNetworks\GraphQL\Selections;
use Saloon\Http\Response;

/**
 * `query { me { ... } }` — the currently authenticated member.
 *
 * Returns null when the token resolves to a viewer without a member node.
 */
final class MeQuery extends GraphQLRequest
{
    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function document(): string
    {
        return Selection::make('query Me')
            ->field('me', selection: Selections::member())
            ->render();
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): ?GraphQLMember
    {
        $me = $this->dataFrom($response)['me'] ?? null;

        return is_array($me) ? GraphQLMember::fromArray($me) : null;
    }
}
