<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Collections;

use MCKLtech\MightyNetworks\DataTransferObjects\CollectionGroup;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateCollectionGroupData;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * `PATCH networks/{network_id}/collections/{id}/`
 */
final class UpdateCollectionGroupRequest extends AdminRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PATCH;

    public function __construct(
        int|string $networkId,
        protected readonly int $collectionId,
        protected readonly UpdateCollectionGroupData $data,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('collections/%d/', $this->collectionId));
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return $this->data->toArray();
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): CollectionGroup
    {
        $data = $response->json();

        return CollectionGroup::fromArray(is_array($data) ? $data : []);
    }
}
