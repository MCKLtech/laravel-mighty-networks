<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Purchases;

use MCKLtech\MightyNetworks\DataTransferObjects\Purchase;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * `GET networks/{network_id}/purchases/{id}/`
 *
 * Note the trailing slash on single-resource Admin REST routes.
 */
final class GetPurchaseRequest extends AdminRequest
{
    protected Method $method = Method::GET;

    public function __construct(
        int|string $networkId,
        protected readonly int $purchaseId,
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
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): Purchase
    {
        $data = $response->json();

        return Purchase::fromArray(is_array($data) ? $data : []);
    }
}
