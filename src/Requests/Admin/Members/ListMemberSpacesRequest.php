<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Members;

use MCKLtech\MightyNetworks\Collections\SpaceCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Space;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\MapPaginatedResponseItems;
use Saloon\PaginationPlugin\Contracts\Paginatable;

/**
 * `GET networks/{network_id}/members/{member_id}/spaces`
 *
 * Paginated: `page` (default 1) and `per_page` (default 25, max 100).
 * Note the spec documents this path without a trailing slash.
 */
final class ListMemberSpacesRequest extends AdminRequest implements MapPaginatedResponseItems, Paginatable
{
    protected Method $method = Method::GET;

    public function __construct(
        int|string $networkId,
        protected readonly int $memberId,
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
        return $this->networkEndpoint(sprintf('members/%d/spaces', $this->memberId));
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
    public function createDtoFromResponse(Response $response): SpaceCollection
    {
        return new SpaceCollection($this->mapPaginatedResponseItems($response));
    }

    /**
     * Map each raw item to a {@see Space}, so paginators yield DTOs.
     *
     * @return array<int, Space>
     */
    #[\Override]
    public function mapPaginatedResponseItems(Response $response): array
    {
        return array_map(
            static fn (mixed $item): Space => Space::fromArray(is_array($item) ? $item : []),
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
