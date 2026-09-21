<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Collections;

use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;

/**
 * `DELETE networks/{network_id}/collections/{id}/`
 */
final class DeleteCollectionGroupRequest extends AdminRequest
{
    protected Method $method = Method::DELETE;

    public function __construct(
        int|string $networkId,
        protected readonly int $collectionId,
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
}
