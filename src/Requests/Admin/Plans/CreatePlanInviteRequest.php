<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Plans;

use MCKLtech\MightyNetworks\DataTransferObjects\Invite;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * `POST networks/{network_id}/plans/{plan_id}/invites`
 *
 * The invitation target is passed as query parameters (`email` or `user_id`),
 * optionally with a `message` and a pre-applied `coupon_id`.
 */
final class CreatePlanInviteRequest extends AdminRequest
{
    protected Method $method = Method::POST;

    public function __construct(
        int|string $networkId,
        protected readonly int $planId,
        protected readonly ?string $email = null,
        protected readonly ?int $userId = null,
        protected readonly ?string $message = null,
        protected readonly ?int $couponId = null,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('plans/%s/invites', $this->planId));
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    #[\Override]
    protected function defaultQuery(): array
    {
        return array_filter(
            [
                'email' => $this->email,
                'user_id' => $this->userId,
                'message' => $this->message,
                'coupon_id' => $this->couponId,
            ],
            static fn (mixed $value): bool => $value !== null,
        );
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
