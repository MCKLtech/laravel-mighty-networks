<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\GraphQL\Mutations;

use MCKLtech\MightyNetworks\Enums\GraphQLMutation;
use MCKLtech\MightyNetworks\GraphQL\Selection;

/**
 * `mutation { createInvites(input: $input) { ... } }`.
 *
 * Pass a camelCase `CreateInvitesInput` variables array; the input mixes
 * single IDs, recipient objects and redirect options, so a DTO would only
 * mirror the SDL.
 */
final class CreateInvitesMutation extends AbstractTypedMutation
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function __construct(
        int|string $networkIdOrSubdomain,
        private readonly array $input,
        ?Selection $selection = null,
    ) {
        parent::__construct($networkIdOrSubdomain, $selection);
    }

    #[\Override]
    public function operation(): GraphQLMutation
    {
        return GraphQLMutation::CreateInvites;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function variables(): array
    {
        return ['input' => $this->input];
    }

    #[\Override]
    protected function defaultSelection(): Selection
    {
        return Selection::make()
            ->field('clientMutationId')
            ->field('errors')
            ->field('count')
            ->field('mode')
            ->field('ignoredRecipients');
    }
}
