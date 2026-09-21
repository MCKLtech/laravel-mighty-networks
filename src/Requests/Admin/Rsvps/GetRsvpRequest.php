<?php

declare(strict_types=1);

namespace MCKLtech\MightyNetworks\Requests\Admin\Rsvps;

use MCKLtech\MightyNetworks\DataTransferObjects\Rsvp;
use MCKLtech\MightyNetworks\Requests\Admin\AdminRequest;
use Saloon\Enums\Method;
use Saloon\Http\Response;

/**
 * `GET networks/{network_id}/events/{event_id}/rsvps/{id}/`
 *
 * Note the trailing slash on single-resource Admin REST routes.
 */
final class GetRsvpRequest extends AdminRequest
{
    protected Method $method = Method::GET;

    public function __construct(
        int|string $networkId,
        protected readonly int $eventId,
        protected readonly int $rsvpId,
    ) {
        parent::__construct($networkId);
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function resolveEndpoint(): string
    {
        return $this->networkEndpoint(sprintf('events/%s/rsvps/%s/', $this->eventId, $this->rsvpId));
    }

    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function createDtoFromResponse(Response $response): Rsvp
    {
        $data = $response->json();

        return Rsvp::fromArray(is_array($data) ? $data : []);
    }
}
