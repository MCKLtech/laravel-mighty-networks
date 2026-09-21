<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Badges;

use MCKLtech\MightyNetworks\Collections\BadgeCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Badge;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\MapPaginatedResponseItems;
use Saloon\PaginationPlugin\Contracts\Paginatable;

/**
 * `GET networks/{network_id}/badges`
 *
 * Paginated: `page` (default 1) and `per_page` (default 25, max 100).
 */
final class ListBadgesRequest extends AdminRequest implements MapPaginatedResponseItems, Paginatable
{
    protected Method $method = Method::GET;

    public function __construct(
        int|string $networkId,
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
        return $this->networkEndpoint('badges');
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, int>
     */
    #[\Override]
    protected function defaultQuery(): array
    {
        return [
            'page' => $this->page,
            'per_page' => $this->perPage,
        ];
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): BadgeCollection
    {
        return new BadgeCollection($this->mapPaginatedResponseItems($response));
    }

    /**
     * Map each raw item to a {@see Badge}, so paginators yield DTOs.
     *
     * @return array<int, Badge>
     */
    #[\Override]
    public function mapPaginatedResponseItems(Response $response): array
    {
        return array_map(
            static fn (mixed $item): Badge => Badge::fromArray(is_array($item) ? $item : []),
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
