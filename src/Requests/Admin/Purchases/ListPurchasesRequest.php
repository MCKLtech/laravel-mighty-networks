<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Purchases;

use MCKLtech\MightyNetworks\Collections\PurchaseCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Purchase;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\MapPaginatedResponseItems;
use Saloon\PaginationPlugin\Contracts\Paginatable;

/**
 * `GET networks/{network_id}/purchases`
 *
 * Returns purchases and subscriptions for the Network. Filter by `plan_id`
 * and/or `member_id`. Paginated with `page` / `per_page` (default 25, max 100).
 */
final class ListPurchasesRequest extends AdminRequest implements MapPaginatedResponseItems, Paginatable
{
    protected Method $method = Method::GET;

    public function __construct(
        int|string $networkId,
        protected readonly ?int $planId = null,
        protected readonly ?int $memberId = null,
        protected int $page = 1,
        protected int $perPage = 25,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint('purchases');
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
                'plan_id' => $this->planId,
                'member_id' => $this->memberId,
                'page' => $this->page,
                'per_page' => $this->perPage,
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): PurchaseCollection
    {
        return new PurchaseCollection($this->mapPaginatedResponseItems($response));
    }

    /**
     * Map each raw item to a {@see Purchase}, so paginators yield DTOs.
     *
     * @return array<int, Purchase>
     */
    #[\Override]
    public function mapPaginatedResponseItems(Response $response): array
    {
        return array_map(
            static fn (mixed $item): Purchase => Purchase::fromArray(is_array($item) ? $item : []),
            $this->pageItems($response),
        );
    }

    /**
     * Extract page items from either documented Admin REST envelope.
     *
     * @return array<int, mixed>
     */
    private function pageItems(Response $response): array
    {
        $data = $response->json();

        if (! is_array($data)) {
            return [];
        }

        foreach (['items', 'data'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                return array_values($data[$key]);
            }
        }

        return [];
    }
}
