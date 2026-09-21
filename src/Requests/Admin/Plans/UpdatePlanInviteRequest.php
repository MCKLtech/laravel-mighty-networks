<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Plans;

use MCKLtech\MightyNetworks\DataTransferObjects\Invite;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateInviteData;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * `PATCH networks/{network_id}/plans/{plan_id}/invites/{id}/`
 */
final class UpdatePlanInviteRequest extends AdminRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PATCH;

    public function __construct(
        int|string $networkId,
        protected readonly int $planId,
        protected readonly int $inviteId,
        protected readonly UpdateInviteData $data,
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
     *
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->data->toArray();
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
