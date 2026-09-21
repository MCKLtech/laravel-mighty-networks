<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Spaces;

use MCKLtech\MightyNetworks\Collections\CourseworkCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Coursework;
use MCKLtech\MightyNetworks\Enums\CourseworkStatus;
use MCKLtech\MightyNetworks\Enums\CourseworkType;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\MapPaginatedResponseItems;
use Saloon\PaginationPlugin\Contracts\Paginatable;

/**
 * `GET networks/{network_id}/spaces/{space_id}/courseworks`
 *
 * Paginated: `page` (default 1) and `per_page` (default 25, max 100).
 * Optional filters: `type`, `status`, `parent_id`.
 */
final class ListCourseworksRequest extends AdminRequest implements MapPaginatedResponseItems, Paginatable
{
    protected Method $method = Method::GET;

    public function __construct(
        int|string $networkId,
        protected readonly int $spaceId,
        protected readonly CourseworkType|string|null $type = null,
        protected readonly CourseworkStatus|string|null $status = null,
        protected readonly ?int $parentId = null,
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
        return $this->networkEndpoint(sprintf('spaces/%d/courseworks', $this->spaceId));
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, int|string>
     */
    #[\Override]
    protected function defaultQuery(): array
    {
        return array_filter(
            [
                'type' => $this->type instanceof CourseworkType ? $this->type->value : $this->type,
                'status' => $this->status instanceof CourseworkStatus ? $this->status->value : $this->status,
                'parent_id' => $this->parentId,
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
    public function createDtoFromResponse(Response $response): CourseworkCollection
    {
        return new CourseworkCollection($this->mapPaginatedResponseItems($response));
    }

    /**
     * Map each raw item to a {@see Coursework}, so paginators yield DTOs.
     *
     * @return array<int, Coursework>
     */
    #[\Override]
    public function mapPaginatedResponseItems(Response $response): array
    {
        return array_map(
            static fn (mixed $item): Coursework => Coursework::fromArray(is_array($item) ? $item : []),
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
