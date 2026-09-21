<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Collections;

use Illuminate\Support\Collection;
use MCKLtech\MightyNetworks\DataTransferObjects\CollectionOrderItem;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * `PUT networks/{network_id}/collections/{id}/order`
 *
 * Reorders spaces within a collection. The body carries explicit
 * `{space_id, position}` assignments (1-based positions).
 */
final class ReorderCollectionSpacesRequest extends AdminRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    /**
     * @param  array<int, array{space_id: int, position: int}>  $positions
     */
    public function __construct(
        int|string $networkId,
        protected readonly int $collectionId,
        protected readonly array $positions,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('collections/%d/order', $this->collectionId));
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return ['spaces' => array_values($this->positions)];
    }

    /**
     * {@inheritDoc}
     *
     * @return Collection<int, CollectionOrderItem>
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): Collection
    {
        return new Collection(array_map(
            static fn (mixed $item): CollectionOrderItem => CollectionOrderItem::fromArray(is_array($item) ? $item : []),
            $this->responseItems($response),
        ));
    }

    /**
     * Extract items from either documented Admin REST envelope, or a bare list.
     *
     * @return array<int, mixed>
     */
    private function responseItems(Response $response): array
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

        return array_is_list($data) ? $data : [];
    }
}
