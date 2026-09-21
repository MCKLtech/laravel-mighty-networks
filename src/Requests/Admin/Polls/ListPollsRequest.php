<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Polls;

use MCKLtech\MightyNetworks\Collections\PollCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Poll;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\MapPaginatedResponseItems;
use Saloon\PaginationPlugin\Contracts\Paginatable;

/**
 * `GET networks/{network_id}/polls`
 *
 * Optionally filtered by `space_id`; paginated with `page` (default 1) and
 * `per_page` (default 25, max 100).
 */
final class ListPollsRequest extends AdminRequest implements MapPaginatedResponseItems, Paginatable
{
    protected Method $method = Method::GET;

    public function __construct(
        int|string $networkId,
        protected ?int $spaceId = null,
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
        return $this->networkEndpoint('polls');
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
                'space_id' => $this->spaceId,
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
    public function createDtoFromResponse(Response $response): PollCollection
    {
        return new PollCollection($this->mapPaginatedResponseItems($response));
    }

    /**
     * Map each raw item to a {@see Poll}, so paginators yield DTOs.
     *
     * @return array<int, Poll>
     */
    #[\Override]
    public function mapPaginatedResponseItems(Response $response): array
    {
        return array_map(
            static fn (mixed $item): Poll => Poll::fromArray(is_array($item) ? $item : []),
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
