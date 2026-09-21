<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Invites;

use MCKLtech\MightyNetworks\Collections\InviteCollection;
use MCKLtech\MightyNetworks\DataTransferObjects\Invite;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\MapPaginatedResponseItems;
use Saloon\PaginationPlugin\Contracts\Paginatable;

/**
 * `GET networks/{network_id}/invites`
 *
 * Filter by `email`; paginated with `page` / `per_page` (default 25, max 100).
 */
final class ListInvitesRequest extends AdminRequest implements MapPaginatedResponseItems, Paginatable
{
    protected Method $method = Method::GET;

    public function __construct(
        int|string $networkId,
        protected readonly ?string $email = null,
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
        return $this->networkEndpoint('invites');
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
                'email' => $this->email,
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
    public function createDtoFromResponse(Response $response): InviteCollection
    {
        return new InviteCollection($this->mapPaginatedResponseItems($response));
    }

    /**
     * Map each raw item to an {@see Invite}, so paginators yield DTOs.
     *
     * @return array<int, Invite>
     */
    #[\Override]
    public function mapPaginatedResponseItems(Response $response): array
    {
        return array_map(
            static fn (mixed $item): Invite => Invite::fromArray(is_array($item) ? $item : []),
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
