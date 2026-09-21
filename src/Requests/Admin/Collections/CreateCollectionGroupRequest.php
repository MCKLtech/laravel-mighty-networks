<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Collections;

use MCKLtech\MightyNetworks\DataTransferObjects\CollectionGroup;
use MCKLtech\MightyNetworks\DataTransferObjects\NewCollectionGroupData;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * `POST networks/{network_id}/collections`
 */
final class CreateCollectionGroupRequest extends AdminRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    public function __construct(
        int|string $networkId,
        protected readonly NewCollectionGroupData $data,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint('collections');
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
