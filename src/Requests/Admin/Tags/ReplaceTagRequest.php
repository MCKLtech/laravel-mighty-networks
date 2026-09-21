<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Tags;

use MCKLtech\MightyNetworks\DataTransferObjects\Tag;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateTagData;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * `PUT networks/{network_id}/tags/{id}/`
 *
 * Replaces the tag with the supplied representation.
 */
final class ReplaceTagRequest extends AdminRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        int|string $networkId,
        protected readonly int $id,
        protected readonly UpdateTagData $data,
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
    public function createDtoFromResponse(Response $response): Tag
    {
        $data = $response->json();

        return Tag::fromArray(is_array($data) ? $data : []);
    }
}
