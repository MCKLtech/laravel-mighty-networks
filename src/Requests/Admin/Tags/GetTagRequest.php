<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Tags;

use MCKLtech\MightyNetworks\DataTransferObjects\Tag;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * `GET networks/{network_id}/tags/{id}/`
 */
final class GetTagRequest extends AdminRequest
{
    protected Method $method = Method::GET;

    public function __construct(
        int|string $networkId,
        protected readonly int $id,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('tags/%d/', $this->id));
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): Tag
    {
        $data = $response->json();

        return Tag::fromArray(is_array($data) ? $data : []);
    }
}
