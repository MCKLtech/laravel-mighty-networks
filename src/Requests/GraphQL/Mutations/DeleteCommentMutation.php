<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\GraphQL\Mutations;

use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\DeleteCommentInput;
use MCKLtech\MightyNetworks\Enums\GraphQLMutation;
use MCKLtech\MightyNetworks\GraphQL\MutationSelections;
use MCKLtech\MightyNetworks\GraphQL\Selection;

/**
 * `mutation { deleteComment(input: $input) { ... } }`.
 */
final class DeleteCommentMutation extends AbstractTypedMutation
{
    public function __construct(
        int|string $networkIdOrSubdomain,
        private readonly DeleteCommentInput $input,
        ?Selection $selection = null,
    ) {
        parent::__construct($networkIdOrSubdomain, $selection);
    }

    #[\Override]
    public function operation(): GraphQLMutation
    {
        return GraphQLMutation::DeleteComment;
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
