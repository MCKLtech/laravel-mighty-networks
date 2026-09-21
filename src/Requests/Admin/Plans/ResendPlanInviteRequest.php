<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Plans;

use MCKLtech\MightyNetworks\DataTransferObjects\Invite;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * `PUT networks/{network_id}/plans/{plan_id}/invites/{id}/`
 *
 * Re-sends an existing invite. The spec documents no request body.
 */
final class ResendPlanInviteRequest extends AdminRequest
{
    protected Method $method = Method::PUT;

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
