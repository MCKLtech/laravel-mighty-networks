<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Spaces;

use MCKLtech\MightyNetworks\DataTransferObjects\Coursework;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * `GET networks/{network_id}/spaces/{space_id}/courseworks/{id}/`
 *
 * Note the trailing slash on single-resource Admin REST routes.
 */
final class GetCourseworkRequest extends AdminRequest
{
    protected Method $method = Method::GET;

    public function __construct(
        int|string $networkId,
        protected readonly int $spaceId,
        protected readonly int $courseworkId,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('spaces/%d/courseworks/%d/', $this->spaceId, $this->courseworkId));
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): Coursework
    {
        $data = $response->json();

        return Coursework::fromArray(is_array($data) ? $data : []);
    }
}
