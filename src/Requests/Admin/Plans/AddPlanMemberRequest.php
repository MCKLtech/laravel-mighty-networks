<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Plans;

use MCKLtech\MightyNetworks\DataTransferObjects\Plan;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * `POST networks/{network_id}/plans/{plan_id}/members?user_id=...`
 *
 * Grants a member access to a free/nonpaid plan immediately. No invite is sent.
 * The `user_id` is passed as a query parameter, per the spec.
 */
final class AddPlanMemberRequest extends AdminRequest
{
    protected Method $method = Method::POST;

    public function __construct(
        int|string $networkId,
        protected readonly int $planId,
        protected readonly int $userId,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('plans/%s/members', $this->planId));
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, int>
     */
    #[\Override]
    protected function defaultQuery(): array
    {
        return ['user_id' => $this->userId];
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): Plan
    {
        $data = $response->json();

        return Plan::fromArray(is_array($data) ? $data : []);
    }
}
