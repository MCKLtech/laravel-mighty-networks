<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Plans;

use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;

/**
 * `DELETE networks/{network_id}/plans/{id}/`
 *
 * Archives the plan, cancelling all associated subscriptions and revoking access.
 */
final class DeletePlanRequest extends AdminRequest
{
    protected Method $method = Method::DELETE;

    public function __construct(
        int|string $networkId,
        protected readonly int $planId,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('plans/%s/', $this->planId));
    }
}
