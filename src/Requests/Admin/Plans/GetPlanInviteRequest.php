<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Plans;

use MCKLtech\MightyNetworks\DataTransferObjects\Invite;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * `GET networks/{network_id}/plans/{plan_id}/invites/{id}/`
 *
 * Note the trailing slash on single-resource Admin REST routes.
 */
final class GetPlanInviteRequest extends AdminRequest
{
    protected Method $method = Method::GET;

    public function __construct(
        int|string $networkId,
        protected readonly int $planId,
        protected readonly int $inviteId,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('plans/%s/invites/%s/', $this->planId, $this->inviteId));
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): Invite
    {
        $data = $response->json();

        return Invite::fromArray(is_array($data) ? $data : []);
    }
}
