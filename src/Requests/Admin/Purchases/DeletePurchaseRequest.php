<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Purchases;

use MCKLtech\MightyNetworks\DataTransferObjects\Purchase;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * `DELETE networks/{network_id}/purchases/{id}/`
 *
 * Revokes purchase access. Members cannot be removed from Apple In-App
 * Purchases. Pass `immediate = true` to cancel without a grace period.
 *
 * Note the trailing slash on single-resource Admin REST routes.
 */
final class DeletePurchaseRequest extends AdminRequest
{
    protected Method $method = Method::DELETE;

    public function __construct(
        int|string $networkId,
        protected readonly int $purchaseId,
        protected readonly ?bool $immediate = null,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('purchases/%s/', $this->purchaseId));
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, bool>
     */
    #[\Override]
    protected function defaultQuery(): array
    {
        return $this->immediate === null ? [] : ['immediate' => $this->immediate];
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): Purchase
    {
        $data = $response->json();

        return Purchase::fromArray(is_array($data) ? $data : []);
    }
}
