<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\GraphQL;

use MCKLtech\MightyNetworks\DataTransferObjects\GraphQL\GraphQLBillingPlan;
use MCKLtech\MightyNetworks\GraphQL\Selection;
use MCKLtech\MightyNetworks\GraphQL\Variable;
use Saloon\Http\Response;

/**
 * `query { billingPlan(canonicalName: $canonicalName) { ... } }` — a billing
 * plan tier by canonical name.
 *
 * Returns null when no plan matches the supplied canonical name.
 */
final class BillingPlanQuery extends GraphQLRequest
{
    public function __construct(
        int|string $networkIdOrSubdomain,
        private readonly string $canonicalName,
    ) {
        parent::__construct($networkIdOrSubdomain);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function document(): string
    {
        $features = Selection::make()
            ->field('key')
            ->field('label')
            ->field('state')
            ->field('value');

        $selection = Selection::make()
            ->field('canonicalName')
            ->field('displayName')
            ->field('group')
            ->field('level')
            ->field('features', selection: $features);

        return Selection::make('query BillingPlan($canonicalName: String!)')
            ->field('billingPlan', arguments: ['canonicalName' => new Variable('canonicalName')], selection: $selection)
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
        return ['canonicalName' => $this->canonicalName];
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): ?GraphQLBillingPlan
    {
        $plan = $this->dataFrom($response)['billingPlan'] ?? null;

        return is_array($plan) ? GraphQLBillingPlan::fromArray($plan) : null;
    }
}
