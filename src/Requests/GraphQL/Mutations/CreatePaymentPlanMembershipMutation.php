<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\GraphQL\Mutations;

use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\Inputs\CreatePaymentPlanMembershipInput;
use MCKLtech\MightyNetworks\Enums\GraphQLMutation;
use MCKLtech\MightyNetworks\GraphQL\MutationSelections;
use MCKLtech\MightyNetworks\GraphQL\Selection;

/**
 * `mutation { createPaymentPlanMembership(input: $input) { ... } }`.
 */
final class CreatePaymentPlanMembershipMutation extends AbstractTypedMutation
{
    public function __construct(
        int|string $networkIdOrSubdomain,
        private readonly CreatePaymentPlanMembershipInput $input,
        ?Selection $selection = null,
    ) {
        parent::__construct($networkIdOrSubdomain, $selection);
    }

    #[\Override]
    public function operation(): GraphQLMutation
    {
        return GraphQLMutation::CreatePaymentPlanMembership;
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
            ->field('paymentPlan', selection: MutationSelections::paymentPlan())
            ->field('paymentSubscription', selection: MutationSelections::paymentSubscription());
    }
}
