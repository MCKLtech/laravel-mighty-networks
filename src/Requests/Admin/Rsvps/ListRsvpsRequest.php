<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Rsvps;

use Carbon\CarbonImmutable;
use MCKLtech\MightyNetworks\Collections\RsvpCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Rsvp;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\MapPaginatedResponseItems;
use Saloon\PaginationPlugin\Contracts\Paginatable;

/**
 * `GET networks/{network_id}/events/{event_id}/rsvps`
 *
 * Paginated: `page` (default 1) and `per_page` (default 25, max 100).
 * `instance_at` narrows the list to a specific instance of a recurring event.
 */
final class ListRsvpsRequest extends AdminRequest implements MapPaginatedResponseItems, Paginatable
{
    protected Method $method = Method::GET;

    public function __construct(
        int|string $networkId,
        protected readonly int $eventId,
        protected readonly CarbonImmutable|string|null $instanceAt = null,
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
        return $this->networkEndpoint(sprintf('events/%s/rsvps', $this->eventId));
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
                'instance_at' => $this->instanceAt instanceof CarbonImmutable
                    ? $this->instanceAt->toIso8601String()
                    : $this->instanceAt,
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
    public function createDtoFromResponse(Response $response): RsvpCollection
    {
        return new RsvpCollection($this->mapPaginatedResponseItems($response));
    }

    /**
     * Map each raw item to an {@see Rsvp}, so paginators yield DTOs.
     *
     * @return array<int, Rsvp>
     */
    #[\Override]
    public function mapPaginatedResponseItems(Response $response): array
    {
        return array_map(
            static fn (mixed $item): Rsvp => Rsvp::fromArray(is_array($item) ? $item : []),
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
