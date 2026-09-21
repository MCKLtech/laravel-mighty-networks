<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\GraphQL\Mutations;

use MCKLtech\MightyNetworks\Enums\GraphQLMutation;
use MCKLtech\MightyNetworks\GraphQL\MutationSelections;
use MCKLtech\MightyNetworks\GraphQL\Selection;

/**
 * `mutation { updateEvent(input: $input) { ... } }`.
 *
 * Pass a camelCase `UpdateEventInput` variables array; see
 * {@see CreateEventMutation} for the rationale.
 */
final class UpdateEventMutation extends AbstractTypedMutation
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
        return GraphQLMutation::UpdateEvent;
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
        return MutationSelections::payload('event', MutationSelections::event());
    }
}
