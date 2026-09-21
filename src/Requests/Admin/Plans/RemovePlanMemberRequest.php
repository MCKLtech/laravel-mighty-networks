<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Plans;

use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;

/**
 * `DELETE networks/{network_id}/plans/{plan_id}/members/{id}/`
 *
 * Removes a member from a plan by cancelling their subscription or purchase.
 */
final class RemovePlanMemberRequest extends AdminRequest
{
    protected Method $method = Method::DELETE;

    public function __construct(
        int|string $networkId,
        protected readonly int $planId,
        protected readonly int $memberId,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('plans/%s/members/%s/', $this->planId, $this->memberId));
    }
}
