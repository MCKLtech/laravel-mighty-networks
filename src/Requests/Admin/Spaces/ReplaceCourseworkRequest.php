<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Spaces;

use MCKLtech\MightyNetworks\DataTransferObjects\Coursework;
use MCKLtech\MightyNetworks\DataTransferObjects\UpdateCourseworkData;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Traits\Body\HasJsonBody;

/**
 * `PUT networks/{network_id}/spaces/{space_id}/courseworks/{id}/`
 *
 * Replaces the coursework item with the supplied representation.
 */
final class ReplaceCourseworkRequest extends AdminRequest implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        int|string $networkId,
        protected readonly int $spaceId,
        protected readonly int $courseworkId,
        protected readonly UpdateCourseworkData $data,
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
    public function createDtoFromResponse(Response $response): Coursework
    {
        $data = $response->json();

        return Coursework::fromArray(is_array($data) ? $data : []);
    }
}
