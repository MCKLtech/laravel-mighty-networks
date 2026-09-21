<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Subscriptions;

use MCKLtech\MightyNetworks\DataTransferObjects\Subscription;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * `GET networks/{network_id}/subscriptions/{id}`
 *
 * Unlike most single-resource Admin routes, this one has **no trailing slash**.
 */
final class GetSubscriptionRequest extends AdminRequest
{
    protected Method $method = Method::GET;

    public function __construct(
        int|string $networkId,
        protected readonly int $subscriptionId,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('subscriptions/%s', $this->subscriptionId));
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): Subscription
    {
        $data = $response->json();

        return Subscription::fromArray(is_array($data) ? $data : []);
    }
}
