<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\GraphQL\Mutations;

use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\CreateMemberInput;
use MCKLtech\MightyNetworks\Enums\GraphQLMutation;
use MCKLtech\MightyNetworks\GraphQL\MutationSelections;
use MCKLtech\MightyNetworks\GraphQL\Selection;

/**
 * `mutation { createMember(input: $input) { ... } }`.
 */
final class CreateMemberMutation extends AbstractTypedMutation
{
    public function __construct(
        int|string $networkIdOrSubdomain,
        private readonly CreateMemberInput $input,
        ?Selection $selection = null,
    ) {
        parent::__construct($networkIdOrSubdomain, $selection);
    }

    #[\Override]
    public function operation(): GraphQLMutation
    {
        return GraphQLMutation::CreateMember;
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
        return MutationSelections::payload('member', MutationSelections::member());
    }
}
