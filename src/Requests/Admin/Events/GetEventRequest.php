<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Events;

use MCKLtech\MightyNetworks\DataTransferObjects\Event;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * `GET networks/{network_id}/events/{id}/`
 *
 * Note the trailing slash on single-resource Admin REST routes.
 */
final class GetEventRequest extends AdminRequest
{
    protected Method $method = Method::GET;

    public function __construct(
        int|string $networkId,
        protected readonly int $eventId,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('events/%s/', $this->eventId));
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): Event
    {
        $data = $response->json();

        return Event::fromArray(is_array($data) ? $data : []);
    }
}
