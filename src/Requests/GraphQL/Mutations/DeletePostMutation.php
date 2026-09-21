<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\GraphQL\Mutations;

use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\DeletePostInput;
use MCKLtech\MightyNetworks\Enums\GraphQLMutation;
use MCKLtech\MightyNetworks\GraphQL\MutationSelections;
use MCKLtech\MightyNetworks\GraphQL\Selection;

/**
 * `mutation { deletePost(input: $input) { ... } }`.
 */
final class DeletePostMutation extends AbstractTypedMutation
{
    public function __construct(
        int|string $networkIdOrSubdomain,
        private readonly DeletePostInput $input,
        ?Selection $selection = null,
    ) {
        parent::__construct($networkIdOrSubdomain, $selection);
    }

    #[\Override]
    public function operation(): GraphQLMutation
    {
        return GraphQLMutation::DeletePost;
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
        return MutationSelections::deletePayload();
    }
}
