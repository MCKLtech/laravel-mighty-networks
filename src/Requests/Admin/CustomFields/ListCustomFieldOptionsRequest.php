<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\CustomFields;

use MCKLtech\MightyNetworks\Collections\CustomFieldOptionCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\CustomFieldOption;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\MapPaginatedResponseItems;
use Saloon\PaginationPlugin\Contracts\Paginatable;

/**
 * `GET networks/{network_id}/custom_fields/{custom_field_id}/options`
 *
 * Optionally filtered by `term`; paginated with `page` (default 1) and
 * `per_page` (default 25, max 100).
 */
final class ListCustomFieldOptionsRequest extends AdminRequest implements MapPaginatedResponseItems, Paginatable
{
    protected Method $method = Method::GET;

    public function __construct(
        int|string $networkId,
        protected readonly int $customFieldId,
        protected ?string $term = null,
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
        return $this->networkEndpoint(sprintf('custom_fields/%d/options', $this->customFieldId));
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
                'term' => $this->term,
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
    public function createDtoFromResponse(Response $response): CustomFieldOptionCollection
    {
        return new CustomFieldOptionCollection($this->mapPaginatedResponseItems($response));
    }

    /**
     * Map each raw item to a {@see CustomFieldOption}, so paginators yield DTOs.
     *
     * @return array<int, CustomFieldOption>
     */
    #[\Override]
    public function mapPaginatedResponseItems(Response $response): array
    {
        return array_map(
            static fn (mixed $item): CustomFieldOption => CustomFieldOption::fromArray(is_array($item) ? $item : []),
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
