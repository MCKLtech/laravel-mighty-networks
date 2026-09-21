<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Spaces;

use MCKLtech\MightyNetworks\DataTransferObjects\Space;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateSpaceData;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * `PUT networks/{network_id}/spaces/{id}`
 *
 * Replaces the space with the supplied representation. Note: unlike most
 * single-resource Admin REST routes, the spec documents this path without a
 * trailing slash.
 */
final class ReplaceSpaceRequest extends AdminRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        int|string $networkId,
        protected readonly int $spaceId,
        protected readonly UpdateSpaceData $data,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('spaces/%d', $this->spaceId));
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
    public function createDtoFromResponse(Response $response): Space
    {
        $data = $response->json();

        return Space::fromArray(is_array($data) ? $data : []);
    }
}
