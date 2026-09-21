<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Spaces;

use MCKLtech\MightyNetworks\DataTransferObjects\Space;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * `GET networks/{network_id}/spaces/{id}`
 *
 * Note: unlike most single-resource Admin REST routes, the spec documents this
 * path without a trailing slash.
 */
final class GetSpaceRequest extends AdminRequest
{
    protected Method $method = Method::GET;

    public function __construct(
        int|string $networkId,
        protected readonly int $spaceId,
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
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): Space
    {
        $data = $response->json();

        return Space::fromArray(is_array($data) ? $data : []);
    }
}
