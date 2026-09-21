<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\GraphQL\Mutations;

use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\CreateSpaceMembershipsInput;
use MCKLtech\MightyNetworks\Enums\GraphQLMutation;
use MCKLtech\MightyNetworks\GraphQL\MutationSelections;
use MCKLtech\MightyNetworks\GraphQL\Selection;

/**
 * `mutation { createSpaceMemberships(input: $input) { ... } }`.
 */
final class CreateSpaceMembershipsMutation extends AbstractTypedMutation
{
    public function __construct(
        int|string $networkIdOrSubdomain,
        private readonly CreateSpaceMembershipsInput $input,
        ?Selection $selection = null,
    ) {
        parent::__construct($networkIdOrSubdomain, $selection);
    }

    #[\Override]
    public function operation(): GraphQLMutation
    {
        return GraphQLMutation::CreateSpaceMemberships;
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    #[\Override]
    public function variables(): array
    {
        return ['input' => $this->input->toArray()];
    }

    #[\Override]
    protected function defaultSelection(): Selection
    {
        return Selection::make()
            ->field('clientMutationId')
            ->field('errors')
            ->field('outcome')
            ->field('member', selection: MutationSelections::member())
            ->field('spaces', selection: MutationSelections::space());
    }
}
