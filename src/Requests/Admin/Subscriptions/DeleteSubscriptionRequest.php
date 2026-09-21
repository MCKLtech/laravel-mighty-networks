<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Subscriptions;

use MCKLtech\MightyNetworks\DataTransferObjects\Subscription;
use MCKLtech\MightyNetworks\Enums\CancelTiming;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * `DELETE networks/{network_id}/subscriptions/{id}`
 *
 * Cancels a payment subscription. `when` controls whether the cancellation is
 * immediate (`now`) or deferred (`end_of_billing_cycle`, the API default).
 *
 * Unlike most single-resource Admin routes, this one has **no trailing slash**.
 */
final class DeleteSubscriptionRequest extends AdminRequest
{
    protected Method $method = Method::DELETE;

    public function __construct(
        int|string $networkId,
        protected readonly int $subscriptionId,
        protected readonly CancelTiming|string|null $when = null,
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
     *
     * @return array<string, string>
     */
    #[\Override]
    protected function defaultQuery(): array
    {
        if ($this->when === null) {
            return [];
        }

        return [
            'when' => $this->when instanceof CancelTiming ? $this->when->value : $this->when,
        ];
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
